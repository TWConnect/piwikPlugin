<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\SearchMonitor;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        $this->hourly('updateTodayData');   // method will be executed once every hour
        $this->daily('updateYesterdayData');   // method will be executed once every day
    }

    public function updateYesterdayData()
    {
        echo "update yesterday data every day";
        $idSite = 3;    // mythoughtworks id site is 3 in production piwik.
        $period = 'day';
        $day = date('Y-m-d', strtotime("-1 days"));     // just add or update yesterday data into DB.
        $segment = false;
        $save = true;   // force add data into DB.
        API::getInstance()->getRepeatingSearchInfo($idSite, $period, $segment, $day, $save);
        API::getInstance()->getBounceSearchInfo($idSite, $period, $segment, $day, $save);
        API::getInstance()->getDataOfPaceTimeOnSearchResultDistribution($idSite, $period, $day, $segment, $save);
        API::getInstance()->calculateAvgPaceTime($idSite, $period, $segment, $day, $save);
    }

    public function updateTodayData()
    {
        echo "update today data every hour";
        $idSite = 3;    // mythoughtworks id site is 3 in production piwik.
        $period = 'day';
        $day = date('Y-m-d');     // just add or update today data into DB.
        $segment = false;
        $save = true;   // force add data into DB.
        API::getInstance()->getRepeatingSearchInfo($idSite, $period, $segment, $day, $save);
        API::getInstance()->getBounceSearchInfo($idSite, $period, $segment, $day, $save);
        API::getInstance()->getDataOfPaceTimeOnSearchResultDistribution($idSite, $period, $day, $segment, $save);
        API::getInstance()->calculateAvgPaceTime($idSite, $period, $segment, $day, $save);
    }
}
