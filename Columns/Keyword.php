<?php

namespace Piwik\Plugins\SearchMonitor\Columns;

use Piwik\Columns\Dimension;
use Piwik\Piwik;

class Keyword extends Dimension
{
    public function getName()
    {
        return Piwik::translate('SearchMonitor_SearchKeywordTitle');
    }
}