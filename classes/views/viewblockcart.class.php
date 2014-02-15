<?php
namespace RAAS\CMS;

class ViewBlockForm extends ViewBlock
{
    const blockListItemClass = 'cms-block-shop-cart';

    public function renderLegend()
    {
        return parent::renderLegend($this->view->_('BLOCK_LEGEND_CART'));
    }


    public function locationContextMenu(Page $Page, Location $Location)
    {
        return parent::locationContextMenu($Page, $Location, $this->view->_('ADD_CART_BLOCK'), 'Shop\\Block_Cart');
    }
}