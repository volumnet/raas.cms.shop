<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\ViewBlock;
use \RAAS\CMS\Page;
use \RAAS\CMS\Location;

class ViewBlockYML extends ViewBlock
{
    const blockListItemClass = 'cms-block-shop-yml';

    public function renderLegend()
    {
        return parent::renderLegend($this->view->_('BLOCK_LEGEND_YML'));
    }


    public function locationContextMenu(Page $Page, Location $Location)
    {
        return parent::locationContextMenu($Page, $Location, $this->view->_('ADD_YML_BLOCK'), 'Shop\\Block_YML');
    }
}