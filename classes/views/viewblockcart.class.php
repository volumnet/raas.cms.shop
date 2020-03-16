<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\ViewBlock;
use \RAAS\CMS\Page;
use \RAAS\CMS\Location;

class ViewBlockCart extends ViewBlock
{
    const blockListItemClass = 'cms-block-shop-cart';

    public function renderBlockTypeName()
    {
        return $this->view->_('BLOCK_LEGEND_CART');
    }


    public function locationContextMenu(Page $Page, Location $Location)
    {
        return parent::locationContextMenu($Page, $Location, $this->view->_('ADD_CART_BLOCK'), 'Shop\\Block_Cart');
    }
}
