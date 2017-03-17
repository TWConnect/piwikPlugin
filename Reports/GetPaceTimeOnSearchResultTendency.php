<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\SearchMonitor\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;

use Piwik\Plugins\SearchMonitor\Columns\DateDimension;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\View;


/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetPaceTimeOnSearchResultTendency extends Base
{
    protected function init()
    {
        parent::init();

        $this->name = Piwik::translate('SearchMonitor_PaceTimeOnSearchResultTendency');
        $this->dimension = new DateDimension();
        $documentation = Piwik::translate('SearchMonitor_PaceTimeOnSearchResultTendencyDocument') . '<br /><br />'
            . '<b>Time of Reading Search Result Content: </b>' . Piwik::translate('SearchMonitor_TimeOnSearchResultPageDocument') . '<br />'
            . '<b>Avg. Time: </b>' . Piwik::translate('SearchMonitor_AvgTimeOnSearchResultPageDocument') . '<br />';

        $this->documentation = $documentation;

        // This defines in which order your report appears in the mobile app, in the menu and in the list of widgets
        $this->order = 1;

        // By default standard metrics are defined but you can customize them by defining an array of metric names
         $this->metrics       = array('avg_time_on_page');

        // Uncomment the next line if your report does not contain any processed metrics, otherwise default
        // processed metrics will be assigned
        // $this->processedMetrics = array();

        // Uncomment the next line if your report defines goal metrics
        // $this->hasGoalMetrics = true;

        // Uncomment the next line if your report should be able to load subtables. You can define any action here
        // $this->actionToLoadSubTables = $this->action;

        // Uncomment the next line if your report always returns a constant count of rows, for instance always
        // 24 rows for 1-24hours
        // $this->constantRowsCount = true;

        // If a subcategory is specified, the report will be displayed in the menu under this menu item
        $this->subcategoryId = Piwik::translate('SearchMonitor_PaceTimeOnSearchResult');
    }

    public function getDefaultTypeViewDataTable(){
        return Evolution::ID;
    }

    public function alwaysUseDefaultViewDataTable()
    {
        return true;
    }


    /**
     * Here you can configure how your report should be displayed. For instance whether your report supports a search
     * etc. You can also change the default request config. For instance change how many rows are displayed by default.
     *
     * @param ViewDataTable $view
     */
    public function configureView(ViewDataTable $view)
    {
        if (!empty($this->dimension)) {
            $view->config->addTranslations(array('label' => $this->dimension->getName(), 'avg_time_on_page' => Piwik::translate('SearchMonitor_AvgTimeOnPage')));
        }

        // $view->config->show_search = false;
        // $view->requestConfig->filter_sort_column = 'nb_visits';
        // $view->requestConfig->filter_limit = 10';
        $view->requestConfig->disable_generic_filters=true;
        $view->config->disable_row_evolution = true;
        $view->config->hide_annotations_view = true;
        $view->config->show_series_picker = false;
        $view->config->columns_to_display = array_merge(array('label'), $this->metrics);
    }

    /**
     * Here you can define related reports that will be shown below the reports. Just return an array of related
     * report instances if there are any.
     *
     * @return \Piwik\Plugin\Report[]
     */
    public function getRelatedReports()
    {
        return array(); // eg return array(new XyzReport());
    }

    /**
     * A report is usually completely automatically rendered for you but you can render the report completely
     * customized if you wish. Just overwrite the method and make sure to return a string containing the content of the
     * report. Don't forget to create the defined twig template within the templates folder of your plugin in order to
     * make it work. Usually you should NOT have to overwrite this render method.
     *
     * @return string
    public function render()
     * {
     * $view = new View('@SearchMonitor/getPaceTimeOnSearchResultContent');
     * $view->myData = array();
     *
     * return $view->render();
     * }
     */

    /**
     * By default your report is available to all users having at least view access. If you do not want this, you can
     * limit the audience by overwriting this method.
     *
     * @return bool
    public function isEnabled()
     * {
     * return Piwik::hasUserSuperUserAccess()
     * }
     */
}
