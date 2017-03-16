<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MyThoughtWorks\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\Plugins\MyThoughtWorks\Columns\DateDimension;
use Piwik\Plugins\MyThoughtWorks\Columns\Metrics\RepeatingRate;


/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetRepeatingSearchRate extends Base
{
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
            $view->config->addTranslations(array('label' => $this->dimension->getName(),
                'repeating_rate' => Piwik::translate('MyThoughtWorks_Percentage'),
                'repeating_search_count' => Piwik::translate('MyThoughtWorks_RepeatingSearchCount'),
                'total_search_count' => Piwik::translate('MyThoughtWorks_TotalSearchCount'),
                'bounce_rate' => 'NbVisitsBounced',));
        }

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

    protected function init()
    {
        parent::init();

        $this->name = Piwik::translate('MyThoughtWorks_RepeatingSearchRate');
        $this->dimension = new DateDimension();
        $this->processedMetrics = array(
            new RepeatingRate()
        );
        $this->documentation = Piwik::translate('MyThoughtWorks_RepeatingSearchRateDocument') . '<br /><br />'
            . '<b>Repeating Search Rate = </b>' . Piwik::translate('MyThoughtWorks_RepeatingSearchRateCalculate') . '<br />'
//            . '<b>Total Search Amount = </b>' . Piwik::translate('MyThoughtWorks_TotalSearchAmountCalculate') . '<br /><br />'
//            . '<b>Repeating Search: </b>' . Piwik::translate('MyThoughtWorks_RepeatingSearchDocument') . '<br />'
            . '<b>Success Search: </b>' . Piwik::translate('MyThoughtWorks_SuccessSearchDocument') . '<br />';

        // This defines in which order your report appears in the mobile app, in the menu and in the list of widgets
        $this->order = 3;

        // By default standard metrics are defined but you can customize them by defining an array of metric names
        $this->metrics = array(/*'repeating_search_count', 'total_search_count', 'bounce_rate', */
            'repeating_rate');


        // If a subcategory is specified, the report will be displayed in the menu under this menu item
        $this->subcategoryId = Piwik::translate('MyThoughtWorks_PaceTimeOnSearchResult');
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
     * $view = new View('@MyThoughtWorks/getPaceTimeOnSearchResultContent');
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
