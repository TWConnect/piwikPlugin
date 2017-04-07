<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\SearchMonitor;

use Exception;
use Piwik\Common;
use Piwik\Db;

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

    public function install()
    {
        try {
            $sql = "CREATE TABLE " . Common::prefixTable('searchmonitor') . " (
                        perday VARCHAR( 50 ) NOT NULL ,
                        bounceCount INT ,
                        bounceTotal INT ,
                        repeatCount INT ,
                        repeatTotal INT ,
                        sumPaceTime INT ,
                        sumVisits INT ,
                        timeLessFive INT ,
                        timeBetFiveAndTen INT ,
                        timeBetTenAndThirty INT ,
                        timeBetThirtyAndSixty INT ,
                        timeMoreSixty INT ,
                        PRIMARY KEY ( perday )
                    )  DEFAULT CHARSET=utf8 ";
            Db::exec($sql);
        } catch (Exception $e) {
            // ignore error if table already exists (1050 code is for 'table already exists')
            if (!Db::get()->isErrNo($e, '1050')) {
                throw $e;
            }
        }
    }
}
