<?php

namespace Piwik\Plugins\MyThoughtWorks\Columns;

use Piwik\Columns\Dimension;
use Piwik\Piwik;

class Keyword extends Dimension
{
    public function getName()
    {
        return Piwik::translate('MyThoughtWorks_SearchKeywordTitle');
    }
}