<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\ViewBlock;
use \RAAS\CMS\Page;
use \RAAS\CMS\Location;

class ViewBlockYML extends ViewBlock
{
    const BLOCK_LIST_ITEM_CLASS = 'cms-block_shop-yml';

    public function renderBlockTypeName()
    {
        return $this->view->_('BLOCK_LEGEND_YML');
    }


    public function locationContextMenu(Page $Page, Location $Location)
    {
        return parent::locationContextMenu($Page, $Location, $this->view->_('ADD_YML_BLOCK'), 'Shop\\Block_YML');
    }
}
