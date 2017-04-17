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
        $this->hourly('callSearchMonitor');   // method will be executed once every day
    }

    public function callSearchMonitor()
    {
        echo "call search monitor api";
        $idSite = 3;
        $period = 'day';
        $date = date('Y-m-d', strtotime("-1 days"));
        $day = date('Y-m-d', strtotime("-1 days"));
        $segment = false;
        $save = true;
        API::getInstance()->getRepeatingSearchInfo($idSite, $period, $date, $segment, $day, $save);
        API::getInstance()->getBounceSearchInfo($idSite, $period, $date, $segment, $day, $save);
        API::getInstance()->getDataOfPaceTimeOnSearchResultDistribution($idSite, $period, $date, $segment, $save);
        API::getInstance()->calculateAvgPaceTime($idSite, $period, $date, $segment, $day, $save);

    }
}
