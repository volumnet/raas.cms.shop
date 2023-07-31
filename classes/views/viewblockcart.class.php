<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\ViewBlock;
use \RAAS\CMS\Page;
use \RAAS\CMS\Location;

class ViewBlockCart extends ViewBlock
{
    const BLOCK_LIST_ITEM_CLASS = 'cms-block_shop-cart';

    public function renderBlockTypeName()
    {
        return $this->view->_('BLOCK_LEGEND_CART');
    }


    public function locationContextMenu(Page $Page, Location $Location)
    {
        return parent::locationContextMenu($Page, $Location, $this->view->_('ADD_CART_BLOCK'), 'Shop\\Block_Cart');
    }
}
