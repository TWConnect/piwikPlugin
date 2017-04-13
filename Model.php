<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\SearchMonitor;

use Piwik\Common;
use Piwik\Db;
use Piwik\Period;
use Piwik\Segment;


class Model
{
    private static $searchMonitor = 'searchmonitor';
    private $searchMonitorTable;

    public function __construct()
    {
        $this->searchMonitorTable = Common::prefixTable(self::$searchMonitor);
    }

    public function queryActionsByKeywordAndDate($keyword, $startDate, $endDate, $segment, $type)
    {
        $customVariable = "";
        if ($segment != "") {
            $spiltSegment = explode('%3D%3D', $segment);
            $spiltSegment[1] = str_replace("%2520", " ", $spiltSegment[1]);
            if ($spiltSegment[0] == "customVariableValue5") {
                $customVariable = "AND log_visit.custom_var_v5 = '$spiltSegment[1]'";
            } elseif ($spiltSegment[0] == "customVariableValue1") {
                $customVariable = "AND log_visit.custom_var_v1 = '$spiltSegment[1]'";
            }
        }

        $typeInfo = "";
        if ($type == "people") {
            $typeInfo = "AND  log_action_title.name LIKE '%/people/%' AND log_action_title.name NOT LIKE '%/people/%/%'";
        } elseif ($type == "group") {
            $typeInfo = "AND  log_action_title.name LIKE '%/group/%' AND log_action_title.name NOT LIKE '%/group/%/%'";
        } elseif ($type == "content") {
            $typeInfo = " AND
            (
                log_action_title.name NOT LIKE '%/groups/%' 
                AND  log_action_title.name != 'null'
                AND  log_action_title.name NOT LIKE '%/people/%'  
                OR log_action_title.name LIKE '%/groups/%/%' 
                OR log_action_title.name LIKE '%/people/%/%'
            )";
        }

        $sql = "SELECT count(*) AS searchTimes,log_action_title.name AS pageTitle
				FROM piwik_log_link_visit_action AS log_link_visit_action
				    LEFT JOIN piwik_log_visit AS log_visit
				    ON log_link_visit_action.idvisit = log_visit.idvisit 
					LEFT JOIN piwik_log_action AS log_action
					ON  log_link_visit_action.idaction_url = log_action.idaction
					LEFT JOIN piwik_log_action AS log_action_title
					ON  log_link_visit_action.idaction_name = log_action_title.idaction
					LEFT JOIN piwik_log_action AS log_action_event_category
					ON  log_link_visit_action.idaction_event_category = log_action_event_category.idaction
					LEFT JOIN piwik_log_action AS log_action_event_action
					ON  log_link_visit_action.idaction_event_action = log_action_event_action.idaction
				WHERE log_action_event_category.name = 'searchResult' "
            . $customVariable . " 
				AND log_link_visit_action.server_time >='$startDate' 
				AND log_link_visit_action.server_time <='$endDate'
				AND log_action_event_action.name = '$keyword' "
            . $typeInfo . " 
				GROUP BY log_action_title.name
				ORDER BY count(*) DESC 
				LIMIT 10";
        return Db::fetchAll($sql);

    }

    public function getOneDayDataFromDB($perDay)
    {
        return Db::fetchRow("SELECT * FROM " . $this->searchMonitorTable . " WHERE perDay = '$perDay'");
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
        $perDayData = $this->getOneDayDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->searchMonitorTable . " (perday,bounceCount,bounceTotal) VALUES (?,?,?) ";
            $bind = array($perDay, $bounceCount, $bounceTotal);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->searchMonitorTable . " SET bounceCount = ? , bounceTotal = ? WHERE perday = ? ";
            $bind = array($bounceCount, $bounceTotal, $perDay);
            Db::query($query, $bind);
        }

    }

    public function addRepeatDataToDB($perDay, $repeatCount, $repeatTotal)
    {
        $perDayData = $this->getOneDayDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->searchMonitorTable . " (perday,repeatCount,repeatTotal) VALUES (?,?,?) ";
            $bind = array($perDay, $repeatCount, $repeatTotal);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->searchMonitorTable . " SET repeatCount = ? , repeatTotal = ? WHERE perday = ? ";
            $bind = array($repeatCount, $repeatTotal, $perDay);
            Db::query($query, $bind);
        }

    }

    public function addPaceTimeDataToDB($perDay, $sumPaceTime, $sumVisits)
    {
        $perDayData = $this->getOneDayDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->searchMonitorTable . " (perday,sumPaceTime,sumVisits) VALUES (?,?,?) ";
            $bind = array($perDay, $sumPaceTime, $sumVisits);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->searchMonitorTable . " SET sumPaceTime = ? , sumVisits = ? WHERE perday = ? ";
            $bind = array($sumPaceTime, $sumVisits, $perDay);
            Db::query($query, $bind);
        }

    }

    public function addPaceTimeDistributionDataToDB($perDay, $timeLessFive, $timeBetFiveAndTen, $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty)
    {
        $perDayData = $this->getOneDayDataFromDB($perDay);

        if (empty($perDayData)) {
            $query = "INSERT INTO " . $this->searchMonitorTable . " (perday,timeLessFive,timeBetFiveAndTen,timeBetTenAndThirty,timeBetThirtyAndSixty,timeMoreSixty) VALUES (?,?,?,?,?,?) ";
            $bind = array($perDay, $timeLessFive, $timeBetFiveAndTen, $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty);
            Db::query($query, $bind);
        } else {
            $query = "UPDATE " . $this->searchMonitorTable . " SET timeLessFive = ? , timeBetFiveAndTen = ? , timeBetTenAndThirty = ? , timeBetThirtyAndSixty = ? , timeMoreSixty = ? WHERE perday = ? ";
            $bind = array($timeLessFive, $timeBetFiveAndTen, $timeBetTenAndThirty, $timeBetThirtyAndSixty, $timeMoreSixty, $perDay);
            Db::query($query, $bind);
        }

    }
}
