<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\SearchMonitor;

class SearchMonitor extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles'
        );
    }

    /**
     * Adds css files for this plugin to the list in the event notification.
     */
    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/SearchMonitor/stylesheets/GetKeywordRelatedInfo.less";
    }
}
