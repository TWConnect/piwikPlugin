<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\SearchMonitor;

use Exception;
use Piwik\Common;
use Piwik\Date;
use Piwik\Db;
use Piwik\Period;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Plugins\CustomVariables\CustomVariables;
use Piwik\Segment;
use Piwik\Site;


class Model
{
    private static $rawPrefix = 'searchmonitor';
    private $table;

    public function __construct()
    {
        $this->table = Common::prefixTable(self::$rawPrefix);
    }

    /**
     * @param $idVisit
     * @param $actionsLimit
     * @return array
     * @throws \Exception
     */
    public function queryActionsForVisit($idVisit, $actionsLimit)
    {
        $maxCustomVariables = CustomVariables::getNumUsableCustomVariables();

        $sqlCustomVariables = '';
        for ($i = 1; $i <= $maxCustomVariables; $i++) {
            $sqlCustomVariables .= ', custom_var_k' . $i . ', custom_var_v' . $i;
        }
        // The second join is a LEFT join to allow returning records that don't have a matching page title
        // eg. Downloads, Outlinks. For these, idaction_name is set to 0
        $sql = "
				SELECT
					COALESCE(log_action_event_category.type, log_action.type, log_action_title.type) AS type,
					log_action.name AS url,
					log_action.url_prefix,
					log_action_title.name AS pageTitle,
					log_action.idaction AS pageIdAction,
					log_link_visit_action.idlink_va,
					log_link_visit_action.server_time as serverTimePretty,
					log_link_visit_action.time_spent_ref_action as timeSpentRef,
					log_link_visit_action.idlink_va AS pageId,
					log_link_visit_action.custom_float,
					log_link_visit_action.interaction_position
					" . $sqlCustomVariables . ",
					log_action_event_category.name AS eventCategory,
					log_action_event_action.name as eventAction
				FROM " . Common::prefixTable('log_link_visit_action') . " AS log_link_visit_action
					LEFT JOIN " . Common::prefixTable('log_action') . " AS log_action
					ON  log_link_visit_action.idaction_url = log_action.idaction
					LEFT JOIN " . Common::prefixTable('log_action') . " AS log_action_title
					ON  log_link_visit_action.idaction_name = log_action_title.idaction
					LEFT JOIN " . Common::prefixTable('log_action') . " AS log_action_event_category
					ON  log_link_visit_action.idaction_event_category = log_action_event_category.idaction
					LEFT JOIN " . Common::prefixTable('log_action') . " AS log_action_event_action
					ON  log_link_visit_action.idaction_event_action = log_action_event_action.idaction
				WHERE log_link_visit_action.idvisit = ?
				ORDER BY server_time ASC
				LIMIT 0, $actionsLimit
				 ";
        $actionDetails = Db::fetchAll($sql, array($idVisit));
        return $actionDetails;
    }

    /**
     * @param $idSite
     * @param $period
     * @param $date
     * @param $segment
     * @param $limit
     * @param $visitorId
     * @param $minTimestamp
     * @param $filterSortOrder
     * @return array
     * @throws Exception
     */
    public function queryLogVisits($idSite, $period, $date, $segment, $offset, $limit, $visitorId, $minTimestamp, $filterSortOrder)
    {
        list($sql, $bind) = $this->makeLogVisitsQueryString($idSite, $period, $date, $segment, $offset, $limit, $visitorId, $minTimestamp, $filterSortOrder);

        return Db::fetchAll($sql, $bind);
    }

    /**
     * @param $idSite
     * @param string $table
     * @return array
     */
    private function getIdSitesWhereClause($idSite, $table = 'log_visit')
    {
        $idSites = array($idSite);
        Piwik::postEvent('Live.API.getIdSitesString', array(&$idSites));

        $idSitesBind = Common::getSqlStringFieldsArray($idSites);
        $whereClause = $table . ".idsite in ($idSitesBind) ";
        return array($whereClause, $idSites);
    }


    /**
     * Returns the ID of a visitor that is adjacent to another visitor (by time of last action)
     * in the log_visit table.
     *
     * @param int $idSite The ID of the site whose visits should be looked at.
     * @param string $visitorId The ID of the visitor to get an adjacent visitor for.
     * @param string $visitLastActionTime The last action time of the latest visit for $visitorId.
     * @param string $segment
     * @param bool $getNext Whether to retrieve the next visitor or the previous visitor. The next
     *                      visitor will be the visitor that appears chronologically later in the
     *                      log_visit table. The previous visitor will be the visitor that appears
     *                      earlier.
     * @return string The hex visitor ID.
     * @throws Exception
     */
    public function queryAdjacentVisitorId($idSite, $visitorId, $visitLastActionTime, $segment, $getNext)
    {
        if ($getNext) {
            $visitLastActionTimeCondition = "sub.visit_last_action_time <= ?";
            $orderByDir = "DESC";
        } else {
            $visitLastActionTimeCondition = "sub.visit_last_action_time >= ?";
            $orderByDir = "ASC";
        }

        $visitLastActionDate = Date::factory($visitLastActionTime);
        $dateOneDayAgo = $visitLastActionDate->subDay(1);
        $dateOneDayInFuture = $visitLastActionDate->addDay(1);

        $select = "log_visit.idvisitor, MAX(log_visit.visit_last_action_time) as visit_last_action_time";
        $from = "log_visit";
        $where = "log_visit.idsite = ? AND log_visit.idvisitor <> ? AND visit_last_action_time >= ? and visit_last_action_time <= ?";
        $whereBind = array($idSite, @Common::hex2bin($visitorId), $dateOneDayAgo->toString('Y-m-d H:i:s'), $dateOneDayInFuture->toString('Y-m-d H:i:s'));
        $orderBy = "MAX(log_visit.visit_last_action_time) $orderByDir";
        $groupBy = "log_visit.idvisitor";

        $segment = new Segment($segment, $idSite);
        $queryInfo = $segment->getSelectQuery($select, $from, $where, $whereBind, $orderBy, $groupBy);

        $sql = "SELECT sub.idvisitor, sub.visit_last_action_time FROM ({$queryInfo['sql']}) as sub
                 WHERE $visitLastActionTimeCondition
                 LIMIT 1";
        $bind = array_merge($queryInfo['bind'], array($visitLastActionTime));

        $visitorId = Db::fetchOne($sql, $bind);
        if (!empty($visitorId)) {
            $visitorId = bin2hex($visitorId);
        }
        return $visitorId;
    }

    /**
     * @param $idSite
     * @param $period
     * @param $date
     * @param $segment
     * @param int $offset
     * @param int $limit
     * @param $visitorId
     * @param $minTimestamp
     * @param $filterSortOrder
     * @return array
     * @throws Exception
     */
    public function makeLogVisitsQueryString($idSite, $period, $date, $segment, $offset, $limit, $visitorId, $minTimestamp, $filterSortOrder)
    {
        // If no other filter, only look at the last 24 hours of stats
        if (empty($visitorId)
            && empty($limit)
            && empty($offset)
            && empty($period)
            && empty($date)
        ) {
            $period = 'day';
            $date = 'yesterdaySameTime';
        }


        list($whereClause, $bindIdSites) = $this->getIdSitesWhereClause($idSite);

        list($whereBind, $where) = $this->getWhereClauseAndBind($whereClause, $bindIdSites, $idSite, $period, $date, $visitorId, $minTimestamp);

        if (strtolower($filterSortOrder) !== 'asc') {
            $filterSortOrder = 'DESC';
        }

        $segment = new Segment($segment, $idSite);

        // Subquery to use the indexes for ORDER BY
        $select = "log_visit.*";
        $from = "log_visit";
        $groupBy = false;
        $limit = $limit >= 1 ? (int)$limit : 0;
        $offset = $offset >= 1 ? (int)$offset : 0;

        $orderBy = '';
        if (count($bindIdSites) <= 1) {
            $orderBy = 'idsite ' . $filterSortOrder . ', ';
        }

        $orderBy .= "visit_last_action_time " . $filterSortOrder;
        $orderByParent = "sub.visit_last_action_time " . $filterSortOrder;

        // this $innerLimit is a workaround (see https://github.com/piwik/piwik/issues/9200#issuecomment-183641293)
        $innerLimit = $limit;
        if (!$segment->isEmpty()) {
            $innerLimit = $limit * 10;
        }

        $innerQuery = $segment->getSelectQuery($select, $from, $where, $whereBind, $orderBy, $groupBy, $innerLimit, $offset);

        $bind = $innerQuery['bind'];
        // Group by idvisit so that a given visit appears only once, useful when for example:
        // 1) when a visitor converts 2 goals
        // 2) when an Action Segment is used, the inner query will return one row per action, but we want one row per visit
        $sql = "
			SELECT sub.* FROM (
				" . $innerQuery['sql'] . "
			) AS sub
			GROUP BY sub.idvisit
			ORDER BY $orderByParent
		";
        if ($limit) {
            $sql .= sprintf("LIMIT %d \n", $limit);
        }
        return array($sql, $bind);
    }

    /**
     * @param $idSite
     * @return Site
     */
    protected function makeSite($idSite)
    {
        return new Site($idSite);
    }

    /**
     * @param string $whereClause
     * @param array $bindIdSites
     * @param $idSite
     * @param $period
     * @param $date
     * @param $visitorId
     * @param $minTimestamp
     * @return array
     * @throws Exception
     */
    private function getWhereClauseAndBind($whereClause, $bindIdSites, $idSite, $period, $date, $visitorId, $minTimestamp)
    {
        $where = array();
        $where[] = $whereClause;
        $whereBind = $bindIdSites;

        if (!empty($visitorId)) {
            $where[] = "log_visit.idvisitor = ? ";
            $whereBind[] = @Common::hex2bin($visitorId);
        }

        if (!empty($minTimestamp)) {
            $where[] = "log_visit.visit_last_action_time > ? ";
            $whereBind[] = date("Y-m-d H:i:s", $minTimestamp);
        }

        // SQL Filter with provided period
        if (!empty($period) && !empty($date)) {
            $currentSite = $this->makeSite($idSite);
            $currentTimezone = $currentSite->getTimezone();

            $dateString = $date;
            if ($period == 'range') {
                $processedPeriod = new Range('range', $date);
                if ($parsedDate = Range::parseDateRange($date)) {
                    $dateString = $parsedDate[2];
                }
            } else {
                $processedDate = Date::factory($date);
                $processedPeriod = Period\Factory::build($period, $processedDate);
            }
            $dateStart = $processedPeriod->getDateStart()->setTimezone($currentTimezone);
            $where[] = "log_visit.visit_last_action_time >= ?";
            $whereBind[] = $dateStart->toString('Y-m-d H:i:s');

            if (!in_array($date, array('now', 'today', 'yesterdaySameTime'))
                && strpos($date, 'last') === false
                && strpos($date, 'previous') === false
                && Date::factory($dateString)->toString('Y-m-d') != Date::factory('now', $currentTimezone)->toString()
            ) {
                $dateEnd = $processedPeriod->getDateEnd()->setTimezone($currentTimezone);
                $where[] = " log_visit.visit_last_action_time <= ?";
                $dateEndString = $dateEnd->addDay(1)->toString('Y-m-d H:i:s');
                $whereBind[] = $dateEndString;
            }
        }

        if (count($where) > 0) {
            $where = join("
				AND ", $where);
        } else {
            $where = false;
        }
        return array($whereBind, $where);
    }

    public function addDayDataInSearchMonitor($perday, $bounceCount, $bounceTotal, $repeatCount, $repeatTotal,
                                              $sumPaceTime, $sumVisits, $timeLessFive, $timeBetFiveAndTen,
                                              $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty)
    {
        $query = "INSERT INTO " . $this->table .
            " (perday,bounceCount,bounceTotal,repeatCount,repeatTotal,sumPaceTime,sumVisits,timeLessFive,timeBetFiveAndTen,timeBetTenAndThirty,timeBetThirtyAndSixty,timeMoreSixty)" .
            " VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $bind = array($perday, $bounceCount, $bounceTotal, $repeatCount, $repeatTotal,
            $sumPaceTime, $sumVisits, $timeLessFive, $timeBetFiveAndTen,
            $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty);
        Db::query($query, $bind);

        return true;
    }

    public function getOneDayBounceDataFromDB($perDay)
    {
        return Db::fetchRow("SELECT * FROM " . $this->table . " WHERE perDay = '$perDay'");
    }

    public function getBounceDataFromDB($startDate, $endDate)
    {
        return Db::fetchRow("SELECT SUM(bounceCount),SUM(bounceTotal) FROM piwik_searchmonitor WHERE perday >= '$startDate' AND perday <= '$endDate'");
    }

    public function getRepeatDataFromDB($startDate, $endDate)
    {
        return Db::fetchRow("SELECT SUM(repeatCount),SUM(repeatTotal) FROM piwik_searchmonitor WHERE perday >= '$startDate' AND perday <= '$endDate'");
    }

    public function getPaceTimeDataFromDB($startDate, $endDate)
    {
        return Db::fetchRow("SELECT SUM(sumPaceTime),SUM(sumVisits) FROM piwik_searchmonitor WHERE perday >= '$startDate' AND perday <= '$endDate'");
    }

    public function getPaceTimeDistributionDataFromDB($startDate, $endDate)
    {
        return Db::fetchRow("SELECT SUM(timeLessFive),SUM(timeBetFiveAndTen),SUM(timeBetTenAndThirty),SUM(timeBetThirtyAndSixty),SUM(timeMoreSixty) FROM piwik_searchmonitor WHERE perday >= '$startDate' AND perday <= '$endDate'");
    }

    public function addBounceDataToDB($perDay, $bounceCount, $bounceTotal)
    {
        $perDayData = $this->getOneDayBounceDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->table . " (perday,bounceCount,bounceTotal) VALUES (?,?,?) ";
            $bind = array($perDay, $bounceCount, $bounceTotal);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->table . " SET bounceCount = ? , bounceTotal = ? WHERE perday = ? ";
            $bind = array($bounceCount, $bounceTotal, $perDay);
            Db::query($query, $bind);
        }

    }

    public function addRepeatDataToDB($perDay, $repeatCount, $repeatTotal)
    {
        $perDayData = $this->getOneDayBounceDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->table . " (perday,repeatCount,repeatTotal) VALUES (?,?,?) ";
            $bind = array($perDay, $repeatCount, $repeatTotal);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->table . " SET repeatCount = ? , repeatTotal = ? WHERE perday = ? ";
            $bind = array($repeatCount, $repeatTotal, $perDay);
            Db::query($query, $bind);
        }

    }

    public function addPaceTimeDataToDB($perDay, $sumPaceTime, $sumVisits)
    {
        $perDayData = $this->getOneDayBounceDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->table . " (perday,sumPaceTime,sumVisits) VALUES (?,?,?) ";
            $bind = array($perDay, $sumPaceTime, $sumVisits);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->table . " SET sumPaceTime = ? , sumVisits = ? WHERE perday = ? ";
            $bind = array($sumPaceTime, $sumVisits, $perDay);
            Db::query($query, $bind);
        }

    }

    public function addPaceTimeDistributionDataToDB($perDay, $timeLessFive, $timeBetFiveAndTen, $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty)
    {
        $perDayData = $this->getOneDayBounceDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->table . " (perday,timeLessFive,timeBetFiveAndTen,timeBetTenAndThirty,timeBetThirtyAndSixty,timeMoreSixty) VALUES (?,?,?,?,?,?) ";
            $bind = array($perDay, $timeLessFive, $timeBetFiveAndTen, $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->table . " SET timeLessFive = ? , timeBetFiveAndTen = ? , timeBetTenAndThirty = ? , timeBetThirtyAndSixty = ? , timeMoreSixty = ? WHERE perday = ? ";
            $bind = array($timeLessFive, $timeBetFiveAndTen, $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty, $perDay);
            Db::query($query, $bind);
        }

    }
}
