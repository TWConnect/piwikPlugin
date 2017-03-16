<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MyThoughtWorks;

use Piwik\DataTable;
use Piwik\DataTable\Row;

/**
 * API for plugin MyThoughtWorks
 *
 * @method static \Piwik\Plugins\MyThoughtWorks\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
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

        foreach ($dateArray as $day) {
            if (strpos($date, ',') !== false && $period == 'day') {
                $data = $this->getVisitDetailsFromApi($idSite, 'day', $day, $segment);
            } else {
                $data = $this->getVisitDetailsFromApi($idSite, $period, $day, $segment);
            }

            $sumPaceTime = 0;
            $sumVisits = 0;

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

            $avgTimeOnPage = 0;
            if ($sumVisits > 0) {
                $avgTimeOnPage = $sumPaceTime / $sumVisits;
            }

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $day, 'avg_time_on_page' => $avgTimeOnPage)));
        }

        return $metatable;
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

            if ($period == 'day') {
                $timeIncrease = ' + 1 days';
            } elseif ($period == 'week') {
                $timeIncrease = ' + 7 days';
            } elseif ($period == 'month') {
                $timeIncrease = ' + 1 month';
            }

            for ($i = $startDate; $i <= $endDate; $i = date('Y-m-d', strtotime($i . $timeIncrease))) {
                array_push($dateArray, $i);
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
    public function getVisitDetailsFromApi($idSite, $period, $day, $segment)
    {
        return \Piwik\API\Request::processRequest('Live.getLastVisitsDetails', array(
            'idSite' => $idSite,
            'period' => $period,
            'date' => $day,
            'segment' => $segment
        ));
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

        $data = $this->getVisitDetailsFromApi($idSite, $period, $date, $segment);

        $metatable = new DataTable();

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
                    $metatable->addRowFromArray(array(Row::COLUMNS => array('avg_time_on_page' => $visitTime)));
                    unset($isResult[$key]);
                }
            }
        }

        foreach ($metatable->getRows() as $row) {
            $value = $row->getColumn('avg_time_on_page');
            $resultRow = null;

            if (0 <= $value && $value < 5) {
                $resultRow = $table->getRowFromLabel('0-5s');
            } elseif (5 <= $value && $value < 10) {
                $resultRow = $table->getRowFromLabel('5-10s');
            } elseif (10 <= $value && $value < 30) {
                $resultRow = $table->getRowFromLabel('10-30s');
            } elseif (30 <= $value && $value < 60) {
                $resultRow = $table->getRowFromLabel('30-60s');
            } elseif (60 <= $value) {
                $resultRow = $table->getRowFromLabel('60s above');
            }

            if ($resultRow != null) {
                $counter = $resultRow->getColumn('Count');
                $resultRow->setColumn('Count', $counter + 1);
            }
        }


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

        foreach ($dateArray as $day) {
            list($repeatingSearchCount, $totalSearchCount) = $this->getRepeatingSearchInfo($idSite, $period, $date, $segment, $day);

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $day,
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
    private function getRepeatingSearchInfo($idSite, $period, $date, $segment, $day)
    {
        if (strpos($date, ',') !== false && $period == 'day') {
            $data = $this->getVisitDetailsFromApi($idSite, 'day', $day, $segment);
        } else {
            $data = $this->getVisitDetailsFromApi($idSite, $period, $day, $segment);
        }
        $repeatSearchRecords = array();
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
        return array($repeatingSearchCount, $totalSearchCount);
    }

    /**
     * @param $repeatSearchTimes
     * @param $repeatSearchRecords
     * @return mixed
     */
    private function addRepeatSearchTimes($repeatSearchTimes, $repeatSearchRecords)
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

        foreach ($dateArray as $day) {
            list($repeatingSearchCount, $totalSearchCount) = $this->getRepeatingSearchInfo($idSite, $period, $date, $segment, $day);
            if ($totalSearchCount == 0) {
                $repeatingRate = 0;
            } else {
                $repeatingRate = $repeatingSearchCount / $totalSearchCount;
            }

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $day,
                'repeating_rate' => $repeatingRate
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
    private function getBounceSearchInfo($idSite, $period, $date, $segment, $day)
    {
        if (strpos($date, ',') !== false && $period == 'day') {
            $data = $this->getVisitDetailsFromApi($idSite, 'day', $day, $segment);
        } else {
            $data = $this->getVisitDetailsFromApi($idSite, $period, $day, $segment);
        }

        $bouncedSearchCount = 0;
        $totalSearchCount = 0;
        foreach ($data as $row) {
            $detail = $row->getColumn('actionDetails');
            for ($index = 0; $index < count($detail); ++$index) {
                if ($detail[$index]['type'] == 'search') {
                    $totalSearchCount++;
                    $nextIndex = $index + 1;
                    if($index == count($detail) - 1){
                        $bouncedSearchCount++;
                    }else {
                        if($detail[$nextIndex]['type'] !== 'event') {
                            $bouncedSearchCount++;
                        }elseif($detail[$nextIndex]['eventCategory'] !== 'searchResult'){
                            $bouncedSearchCount++;
                        }
                    }
                }
            }
        }

        return array($bouncedSearchCount, $totalSearchCount);
    }

    public function getBounceSearchRate($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();

        foreach ($dateArray as $day) {
            list($bouncedSearchCount, $totalSearchCount) = $this->getBounceSearchInfo($idSite, $period, $date, $segment, $day);
            $bounceRate = ($bouncedSearchCount / $totalSearchCount) * 100;

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $day,
                'bounce_search_rate' => $bounceRate
            )));
        }

        return $metatable;
    }

    public function getBounceSearchCount($idSite, $period, $date, $segment = false)
    {
        $dateArray = $this->getDateArrayForEvolution($period, $date);
        $metatable = new DataTable();

        foreach ($dateArray as $day) {
            list($bouncedSearchCount, $totalSearchCount) = $this->getBounceSearchInfo($idSite, $period, $date, $segment, $day);

            $metatable->addRowFromArray(array(Row::COLUMNS => array(
                'label' => $day,
                'bounce_search_count' => $bouncedSearchCount,
                'total_search_count' => $totalSearchCount
            )));
        }

        return $metatable;
    }

    public function getKeywordRelatedInfo($idSite, $period, $date, $segment = false, $reqKeyword = null)
    {
        $data = $this->getVisitDetailsFromApi($idSite, $period, $date, $segment);
        $table = new DataTable();
        foreach ($data as $row) {
            $detail = $row->getColumn('actionDetails');
            foreach ($detail as $action) {

                if ($action['type'] == 'event' && $action['eventCategory'] == 'searchResult'
                    && $action['eventName'] != "null"
                ) {

                    $keyword = $action['eventAction'];
                    if ($reqKeyword != null && $reqKeyword == $keyword) {
                        $srcURL = $action['eventName'];
                        $type = 'content';
                        if (preg_match("/^[^\/]+\/\/[^\/]+\/groups\/[^\/]+$/", $srcURL)) {
                            $type = 'group';
                        } else if (preg_match("/^[^\/]+\/\/[^\/]+\/people\/[^\/]+$/", $srcURL)) {
                            $type = 'people';
                        }
                        $table->addRowFromArray(array(Row::COLUMNS => array(
                            'url' => $srcURL, 'type' => $type)));
                    }
                }
            }
        }
        return $table;
    }

    /**
     * @param $period
     * @param $date
     * @return array
     */
    public function getDateArray($period, $date)
    {
        if ($date == 'yesterday') {
            $date = date('Y-m-d', strtotime("-1 days"));
        } elseif ($date == 'today') {
            $date = date('Y-m-d');
        }

        $dateArray = array();

        if (strpos($date, ',') !== false && $period == 'day') {
            $spiltDate = explode(',', $date);
            $startDate = date('Y-m-d', strtotime($spiltDate[0]));
            $endDate = date('Y-m-d', strtotime($spiltDate[1]));
            for ($i = $startDate; $i < $endDate; $i = date('Y-m-d', strtotime($i . ' + 1 days'))) {
                array_push($dateArray, $i);
            }
            return $dateArray;
        }
        return $dateArray;
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
        return \Piwik\Plugins\Actions\API::getInstance()->getSiteSearchKeywords($idSite, $period, $date, $segment);
    }

}
