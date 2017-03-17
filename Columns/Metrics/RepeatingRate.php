<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\SearchMonitor\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\ProcessedMetric;

/**
 * The percentage of visits that leave the site without visiting another page. Calculated
 * as:
 *
 *     bounce_count / nb_visits
 *
 * bounce_count & nb_visits are calculated by an Archiver.
 */
class RepeatingRate extends ProcessedMetric
{
    public function getName()
    {
        return 'repeating_rate';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('SearchMonitor_RepeatingSearchRate');
    }

    public function getDependentMetrics()
    {
        return array('repeating_search_count', 'total_search_count');
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyPercentFromQuotient($value);
    }

    public function compute(Row $row)
    {
        $repeatingSearchCount = $this->getMetric($row, 'repeating_search_count');
        $totalSearchCount = $this->getMetric($row, 'total_search_count');

        return Piwik::getQuotientSafe($repeatingSearchCount, $totalSearchCount, $precision = 2);
    }
}