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
        API::getInstance()->getRepeatingSearchInfo(1, 'day', date('Y-m-d', strtotime("-1 days")), false, date('Y-m-d', strtotime("-1 days")), true);
        API::getInstance()->getBounceSearchInfo(1, 'day', date('Y-m-d', strtotime("-1 days")), false, date('Y-m-d', strtotime("-1 days")), true);
        API::getInstance()->getDataOfPaceTimeOnSearchResultDistribution(1, 'day', date('Y-m-d', strtotime("-1 days")), false, true);
        API::getInstance()->calculateAvgPaceTime(1, 'day', date('Y-m-d', strtotime("-1 days")), false, date('Y-m-d', strtotime("-1 days")), true);

    }
}
