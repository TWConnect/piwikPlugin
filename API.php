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
use Piwik\Config;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Piwik;
use Piwik\Site;


/**
 * API for plugin SearchMonitor
 *
 * @method static \Piwik\Plugins\SearchMonitor\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    private function getModel()
    {
        return new Model();
    }

    public function getDateArrayForEvolution($period, $date)
    {
        if ($date == 'yesterday') {
            $date = date('Y-m-d', strtotime("-1 days"));
        } elseif ($date == 'today') {
            $date = date('Y-m-d');
        }

        $dateArray = array();
        $timeIncrease = '';

        if (strpos($date, ',') !== false) {
            $spiltDate = explode(',', $date);
            $startDate = date('Y-m-d', strtotime($spiltDate[0]));
            $endDate = date('Y-m-d', strtotime($spiltDate[1]));

            if ($period == 'week') {
                $startDate = date('Y-m-d', strtotime($endDate . ' - 70 days'));
            } elseif ($period == 'month') {
                $startDate = date('Y-m-01', strtotime($endDate . ' - 180 days'));
            }

            if ($period == 'day' || $period == 'range') {
                $timeIncrease = ' + 1 days';
            } elseif ($period == 'week') {
                $timeIncrease = ' + 7 days';
            } elseif ($period == 'month') {
                $timeIncrease = ' + 1 month';
            }

            $mons = array(1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec");

            for ($i = $startDate; $i <= $endDate; $i = date('Y-m-d', strtotime($i . $timeIncrease))) {
                if ($period == 'day') {
                    $dateArray[$i] = $i;
                } elseif ($period == 'week') {
                    $start = date('Y/m/d', strtotime($i));
                    $end = date('Y/m/d', strtotime($i . ' + 6 days'));
                    $label = $start . ' - ' . $end;
                    $dateArray[$i] = $label;
                } elseif ($period == 'month') {
                    $d = date_parse_from_format('Y-m-d', $i);
                    $label = $mons[$d['month']] . ' ' . $d['year'];
                    $dateArray[$i] = $label;
                }
            }
            return $dateArray;
        }

        return $dateArray;
    }

    /**
     * @param $idSite
     * @param $day
     * @return mixed
     */
    public function getVisitDetailsFromApiByPage($idSite, $period, $date, $segment = false, $filter_offset = 0)
    {
        $filter_limit = 100;
        return \Piwik\API\Request::processRequest('Live.getLastVisitsDetails', array(
            'idSite' => $idSite,
            'period' => $period,
            'date' => $date,
            'segment' => $segment,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit
        ));
    }

    /**
     * Another example method that returns a data table.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @return DataTable
     * @internal param bool|string $segment
     */
    public function getPaceTimeOnSearchResultTendency($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();

        foreach ($dateArray as $day => $label) {
            list($startDate, $endDate) = $this->getStartDateAndEndDate($period, $day);
            $periodData = $this->getModel()->getPaceTimeDataFromDB($startDate, $endDate);
            $sumPaceTime = $periodData['SUM(sumPaceTime)'];
            $sumVisits = $periodData['SUM(sumVisits)'];

            if ($period == 'day' && ($sumPaceTime == null || $sumVisits == null)) {
                $sumPaceTime = 0;
                $sumVisits = 0;
                if (strpos($date, ',') !== false) {
                    $filter_offset = 0;
                    $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $day, $segment, $filter_offset);
                    list($sumVisits, $sumPaceTime) = $this->getAvgTimeOnPage($data, $sumVisits, $sumPaceTime);
                    while ($data->getRowsCount() >= 100) {
                        $filter_offset = $filter_offset + 100;
                        $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $day, $segment, $filter_offset);
                        list($sumVisits, $sumPaceTime) = $this->getAvgTimeOnPage($data, $sumVisits, $sumPaceTime);
                    }
                }
                $this->getModel()->addPaceTimeDataToDB($day, $sumPaceTime, $sumVisits);
            }

            $avgTimeOnPage = 0;
            if ($sumVisits > 0) {
                $avgTimeOnPage = $sumPaceTime / $sumVisits;
            }

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $label, 'avg_time_on_page' => $avgTimeOnPage)));
        }

        return $metatable;
    }

    public function getDataOfPaceTimeOnSearchResultDistribution($idSite, $period, $date, $segment = false)
    {
        list($startDate, $endDate) = $this->getStartDateAndEndDate($period, $date);

        $distributionData = $this->getPaceTimeDistributionFromDB($startDate, $endDate);
        if ($period == 'range') {
            for ($day = $startDate; $day <= $endDate; $day = date('Y-m-d', strtotime($day . "+1 days"))) {
                $this->calculateDailyDistributionData($idSite, 'day', $day, $segment);
            }
            $distributionData = $this->getPaceTimeDistributionFromDB($startDate, $endDate);
        }
        if ($period == 'day') {
            $distributionData = $this->calculateDailyDistributionData($idSite, $period, $date, $segment);
        }

        return $distributionData;
    }

    /**
     * Another example method that returns a data table.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @return DataTable
     * @throws \Exception
     * @internal param bool|string $segment
     */
    public function getPaceTimeOnSearchResultDistribution($idSite, $period, $date, $segment = false)
    {
        $table = new DataTable();
        $table->addRowsFromSimpleArray(array(
            array('label' => '0-5s', 'Count' => 0),
            array('label' => '5-10s', 'Count' => 0),
            array('label' => '10-30s', 'Count' => 0),
            array('label' => '30-60s', 'Count' => 0),
            array('label' => '60s above', 'Count' => 0)
        ));

        $distributionData = $this->getDataOfPaceTimeOnSearchResultDistribution($idSite, $period, $date, $segment);

        $resultRow = $table->getRowFromLabel('0-5s');
        $resultRow->setColumn('Count', $distributionData[0]);
        $resultRow = $table->getRowFromLabel('5-10s');
        $resultRow->setColumn('Count', $distributionData[1]);
        $resultRow = $table->getRowFromLabel('10-30s');
        $resultRow->setColumn('Count', $distributionData[2]);
        $resultRow = $table->getRowFromLabel('30-60s');
        $resultRow->setColumn('Count', $distributionData[3]);
        $resultRow = $table->getRowFromLabel('60s above');
        $resultRow->setColumn('Count', $distributionData[4]);

        return $table;
    }

    /**
     * Another example method that returns a data table.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @return DataTable
     * @internal param bool|string $segment
     */
    public function getRepeatingSearchCount($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();

        foreach ($dateArray as $day => $label) {
            list($repeatingSearchCount, $totalSearchCount) = $this->getRepeatingSearchInfo($idSite, $period, $date, $segment, $day);

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $label,
                'repeating_search_count' => $repeatingSearchCount,
                'total_search_count' => $totalSearchCount,
            )));
        }

        return $metatable;
    }

    /**
     * @param $idSite
     * @param $period
     * @param $date
     * @param $segment
     * @param $day
     * @return array
     */
    public function getRepeatingSearchInfo($idSite, $period, $date, $segment = false, $day)
    {
        list($startDate, $endDate) = $this->getStartDateAndEndDate($period, $day);
        $periodData = $this->getModel()->getRepeatDataFromDB($startDate, $endDate);
        $repeatingSearchCount = $periodData['SUM(repeatCount)'];
        $totalSearchCount = $periodData['SUM(repeatTotal)'];

        if ($period == 'day' && ($repeatingSearchCount == null || $totalSearchCount == null)) {
            $repeatSearchRecords = array();
            if (strpos($date, ',') !== false) {
                $filter_offset = 0;
                $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $day, $segment, $filter_offset);
                $repeatSearchRecords = $this->getRepeatSearchData($data, $repeatSearchRecords);
                while ($data->getRowsCount() >= 100) {
                    $filter_offset = $filter_offset + 100;
                    $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $day, $segment, $filter_offset);
                    $repeatSearchRecords = $this->getRepeatSearchData($data, $repeatSearchRecords);
                }
            }

            if (array_key_exists(1, $repeatSearchRecords)) {
                $successSearchCount = $repeatSearchRecords[1];
            } else {
                $successSearchCount = 0;
            }
            $repeatingSearchCount = 0;

            foreach ($repeatSearchRecords as $key => $value) {
                if ($key > 1) { //only if repeat search larger than 1, see it as a repeating search
                    $repeatingSearchCount += $repeatSearchRecords[$key];
                }
            }
            $totalSearchCount = $successSearchCount + $repeatingSearchCount;
            $this->getModel()->addRepeatDataToDB($day, $repeatingSearchCount, $totalSearchCount);
        }

        return array($repeatingSearchCount, $totalSearchCount);
    }

    /**
     * @param $repeatSearchTimes
     * @param $repeatSearchRecords
     * @return mixed
     */
    public function addRepeatSearchTimes($repeatSearchTimes, $repeatSearchRecords)
    {
        if (array_key_exists($repeatSearchTimes, $repeatSearchRecords) && $repeatSearchRecords[$repeatSearchTimes] > 0) {
            $repeatSearchRecords[$repeatSearchTimes]++;
        } else {
            $repeatSearchRecords[$repeatSearchTimes] = 1;
        }
        return $repeatSearchRecords; //value represent the search count for this kind of repeat search
    }

    public function getRepeatingSearchRate($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();

        foreach ($dateArray as $day => $label) {
            list($repeatingSearchCount, $totalSearchCount) = $this->getRepeatingSearchInfo($idSite, $period, $date, $segment, $day);
            if ($totalSearchCount == 0) {
                $repeatingRate = 0;
            } else {
                $repeatingRate = ($repeatingSearchCount * 100) / ($totalSearchCount * 1.0);
            }

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $label,
                'repeating_rate' => $repeatingRate
            )));
        }

        return $metatable;
    }


    /**
     * @param $data
     * @param $totalSearchCount
     * @param $bouncedSearchCount
     * @return array
     */
    private function getBounceSearchData($data, $totalSearchCount, $bouncedSearchCount)
    {
        foreach ($data as $row) {
            $detail = $row->getColumn('actionDetails');
            for ($index = 0; $index < count($detail); ++$index) {
                if ($detail[$index]['type'] == 'search') {
                    $searchWord = $detail[$index]['siteSearchKeyword'];
                    $totalSearchCount++;
                    $searchSuccess = false;
                    if ($index == count($detail) - 1) {
                        $bouncedSearchCount++;
                    } else {
                        if ($detail[$index - 1]['type'] === 'event' &&
                            $detail[$index - 1]['eventCategory'] === 'searchResult' &&
                            $detail[$index - 1]['eventAction'] === $searchWord
                        ) {
                            $searchSuccess = true;
                        }

                        $checkSearchSuccess = $index + 1;
                        while ($checkSearchSuccess < count($detail)) {
                            if ($detail[$checkSearchSuccess]['type'] === 'event' &&
                                $detail[$checkSearchSuccess]['eventCategory'] === 'searchResult' &&
                                $detail[$checkSearchSuccess]['eventAction'] === $searchWord
                            ) {
                                $searchSuccess = true;
                                break;
                            }
                            if ($detail[$checkSearchSuccess]['type'] === 'search') {
                                $searchSuccess = false;
                                break;
                            }
                            $checkSearchSuccess++;
                        }

                        if (!$searchSuccess) {
                            $bouncedSearchCount++;
                        }
                    }
                }
            }
        }

        return array($totalSearchCount, $bouncedSearchCount);
    }

    /**
     * @param $idSite
     * @param $period
     * @param $date
     * @param $segment
     * @param $day
     * @return array
     */
    public function getBounceSearchInfo($idSite, $period, $date, $segment = false, $day)
    {
        list($startDate, $endDate) = $this->getStartDateAndEndDate($period, $day);
        $periodData = $this->getModel()->getBounceDataFromDB($startDate, $endDate);
        $bouncedSearchCount = $periodData['SUM(bounceCount)'];
        $totalSearchCount = $periodData['SUM(bounceTotal)'];

        if ($period == 'day' && ($bouncedSearchCount == null || $totalSearchCount == null)) {
            $bouncedSearchCount = 0;
            $totalSearchCount = 0;
            if (strpos($date, ',') !== false) {
                $filter_offset = 0;
                $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $day, $segment, $filter_offset);
                list($totalSearchCount, $bouncedSearchCount) = $this->getBounceSearchData($data, $totalSearchCount, $bouncedSearchCount);

                while ($data->getRowsCount() >= 100) {
                    $filter_offset = $filter_offset + 100;
                    $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $day, $segment, $filter_offset);
                    list($totalSearchCount, $bouncedSearchCount) = $this->getBounceSearchData($data, $totalSearchCount, $bouncedSearchCount);
                }
            }

            $this->getModel()->addBounceDataToDB($day, $bouncedSearchCount, $totalSearchCount);
        }

        return array($bouncedSearchCount, $totalSearchCount);
    }

    public function getBounceSearchRate($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();

        foreach ($dateArray as $day => $label) {
            list($bouncedSearchCount, $totalSearchCount) = $this->getBounceSearchInfo($idSite, $period, $date, $segment, $day);
            if ($totalSearchCount == 0) {
                $bounceRate = 0;
            } else {
                $bounceRate = ($bouncedSearchCount * 100) / ($totalSearchCount * 1.0);
            }
            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $label,
                'bounce_search_rate' => $bounceRate
            )));
        }

        return $metatable;
    }

    public function getBounceSearchCount($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();
        foreach ($dateArray as $day => $label) {
            list($bouncedSearchCount, $totalSearchCount) = $this->getBounceSearchInfo($idSite, $period, $date, $segment, $day);
            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $label,
                'bounce_search_count' => $bouncedSearchCount,
                'total_search_count' => $totalSearchCount
            )));
        }

        return $metatable;
    }

    public function getKeywordRelatedInfo($idSite, $period, $date, $segment = false, $reqKeyword = null)
    {
        $table = new DataTable();
        list($startDate, $endDate) = $this->getStartDateAndEndDate($period, $date);
        $endDate = date('Y-m-d H:i:s e', strtotime($endDate."+1 days") - 1);
        $peopleInfo = $this->getModel()->queryActionsByKeywordAndDate($reqKeyword, $startDate, $endDate, $segment, "people");
        $groupInfo = $this->getModel()->queryActionsByKeywordAndDate($reqKeyword, $startDate, $endDate, $segment, "group");
        $contentInfo = $this->getModel()->queryActionsByKeywordAndDate($reqKeyword, $startDate, $endDate, $segment, "content");
        foreach ($peopleInfo as $item) {
            $table->addRowFromArray(array(Row::COLUMNS => array(
                'url' => $item['pageTitle'], 'type' => 'people', 'count' => $item['searchTimes'])));
        }
        foreach ($groupInfo as $item) {
            $table->addRowFromArray(array(Row::COLUMNS => array(
                'url' => $item['pageTitle'], 'type' => 'group', 'count' => $item['searchTimes'])));
        }
        foreach ($contentInfo as $item) {
            $table->addRowFromArray(array(Row::COLUMNS => array(
                'url' => $item['pageTitle'], 'type' => 'content', 'count' => $item['searchTimes'])));
        }

        return $table;
    }


    /**
     * Another example method that returns a data table.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getSearchKeywords($idSite, $period, $date, $segment = false)
    {
        return \Piwik\API\Request::processRequest('Actions.getSiteSearchKeywords', array(
            'idSite' => $idSite,
            'period' => $period,
            'date' => $date,
            'segment' => $segment,
            'filter_limit' => -1
        ));
    }

    /**
     * @param $data
     * @param $metatable
     */
    private function getAvgTimeOnPageDistribution($data, $distributionData)
    {
        foreach ($data as $row) {
            $detail = $row->getColumn('actionDetails');
            $isResult = array();
            foreach ($detail as $action) {
                if ($action['type'] == 'event' && $action['eventCategory'] == 'searchResult') {
                    $isResult[] = $action['eventName'];
                }
                $key = array_search($action['url'], $isResult);
                if ($action['type'] == 'action' && $key !== FALSE) {
                    $visitTime = $action['timeSpent'];

                    if (0 <= $visitTime && $visitTime < 5) {
                        $distributionData[0]++;
                    } elseif (5 <= $visitTime && $visitTime < 10) {
                        $distributionData[1]++;
                    } elseif (10 <= $visitTime && $visitTime < 30) {
                        $distributionData[2]++;
                    } elseif (30 <= $visitTime && $visitTime < 60) {
                        $distributionData[3]++;
                    } elseif (60 <= $visitTime) {
                        $distributionData[4]++;
                    }
                    unset($isResult[$key]);
                }
            }
        }
        return $distributionData;
    }

    /**
     * @param $data
     * @param $repeatSearchRecords
     * @return mixed
     */
    private function getRepeatSearchData($data, $repeatSearchRecords)
    {
        foreach ($data as $row) {
            $detail = $row->getColumn('actionDetails');
            $isFirstSearch = true;
            $repeatSearchTimes = 0;
            $previousSearchTimeStamp = -1;
            for ($index = 0; $index < count($detail); ++$index) {
                if ($detail[$index]['type'] == 'search') {
                    if ($isFirstSearch) {
                        $repeatSearchTimes = 1;
                        $previousSearchTimeStamp = $detail[$index]['timestamp'];
                        $isFirstSearch = false;
                    } else {
                        $timeInterval = $detail[$index]['timestamp'] - $previousSearchTimeStamp;

                        if ($timeInterval <= 180 && ($detail[$index]['timestamp'] - $previousSearchTimeStamp) >= 0) {
                            $repeatSearchTimes++; //within specific time range, another repeat search
                        } else {
                            $repeatSearchRecords = $this->addRepeatSearchTimes($repeatSearchTimes, $repeatSearchRecords);
                            $repeatSearchTimes = 1;  //reset the repeatSearchTime. Because we'll start another round calculate
                        }
                        $previousSearchTimeStamp = $detail[$index]['timestamp'];
                    }
                }
                if ($index == (count($detail) - 1)) { //the last item in this action detail
                    $repeatSearchRecords = $this->addRepeatSearchTimes($repeatSearchTimes, $repeatSearchRecords);
                }
            }
        }
        return $repeatSearchRecords;
    }

    /**
     * @param $data
     * @param $sumVisits
     * @param $sumPaceTime
     * @return array
     */
    private function getAvgTimeOnPage($data, $sumVisits, $sumPaceTime)
    {
        foreach ($data as $row) {

            $detail = $row->getColumn('actionDetails');
            $isResult = array();
            foreach ($detail as $action) {
                if ($action['type'] == 'event' && $action['eventCategory'] == 'searchResult') {
                    $isResult[] = $action['eventName'];
                }
                $key = array_search($action['url'], $isResult);
                if ($action['type'] == 'action' && $key !== FALSE) {
                    $visitTime = $action['timeSpent'];
                    $sumVisits++;
                    $sumPaceTime += $visitTime;
                    unset($isResult[$key]);
                }
            }
        }
        return array($sumVisits, $sumPaceTime);
    }
    
    /**
     * @param $period
     * @param $day
     * @return array
     */
    private function getStartDateAndEndDate($period, $day)
    {
        if ($day == 'yesterday') {
            $day = date('Y-m-d', strtotime("-1 days"));
        } elseif ($day == 'today') {
            $day = date('Y-m-d');
        }
        $startDate = $day;
        $endDate = $day;
        if ($period == 'week') {
            $startDate = date('Y-m-d', strtotime($day . ' - 6 days'));
            $endDate = date('Y-m-d', strtotime($day));
            return array($startDate, $endDate);
        } elseif ($period == 'month') {
            $startDate = date('Y-m-01', strtotime($day));
            $endDate = date('Y-m-t', strtotime($day));
            return array($startDate, $endDate);
        } elseif ($period == 'range') {
            $spiltDate = explode(',', $day);
            $startDate = date('Y-m-d', strtotime($spiltDate[0]));
            $endDate = date('Y-m-d', strtotime($spiltDate[1]));
        }
        return array($startDate, $endDate);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $distributionData
     * @return mixed
     */
    private function getPaceTimeDistributionFromDB($startDate, $endDate)
    {
        $distributionData = array();
        $periodData = $this->getModel()->getPaceTimeDistributionDataFromDB($startDate, $endDate);

        $distributionData[0] = $periodData['SUM(timeLessFive)'];
        $distributionData[1] = $periodData['SUM(timeBetFiveAndTen)'];
        $distributionData[2] = $periodData['SUM(timeBetTenAndThirty)'];
        $distributionData[3] = $periodData['SUM(timeBetThirtyAndSixty)'];
        $distributionData[4] = $periodData['SUM(timeMoreSixty)'];
        return $distributionData;
    }

    /**
     * @param $idSite
     * @param $period
     * @param $date
     * @param $segment
     * @param $distributionData
     * @return mixed
     */
    private function calculateDailyDistributionData($idSite, $period, $date, $segment)
    {
        $distributionData = $this->getPaceTimeDistributionFromDB($date, $date);
        if ($distributionData[0] == null || $distributionData[1] == null ||
            $distributionData[2] == null || $distributionData[3] == null ||
            $distributionData[4] == null
        ) {
            $distributionData [0] = 0;
            $distributionData [1] = 0;
            $distributionData [2] = 0;
            $distributionData [3] = 0;
            $distributionData [4] = 0;

            $filter_offset = 0;

            $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $date, $segment, $filter_offset);
            $distributionData = $this->getAvgTimeOnPageDistribution($data, $distributionData);
            while ($data->getRowsCount() >= 100) {
                $filter_offset = $filter_offset + 100;
                $data = $this->getVisitDetailsFromApiByPage($idSite, $period, $date, $segment, $filter_offset);
                $distributionData = $this->getAvgTimeOnPageDistribution($data, $distributionData);
            }

            $this->getModel()->addPaceTimeDistributionDataToDB($date, $distributionData[0], $distributionData[1],
                $distributionData[2], $distributionData[3], $distributionData[4]);

        }

        return $distributionData;
    }

}
