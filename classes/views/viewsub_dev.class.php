<?php
namespace RAAS\CMS\Shop;
use \RAAS\Table as Table;
use \RAAS\Column as Column;
use \RAAS\Row as Row;

class ViewSub_Dev extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    public function devMenu()
    {
        $submenu = array();
        $submenu[] = array(
            'href' => $this->url . '&action=cart_types', 
            'name' => $this->_('CART_TYPES'), 
            'active' => in_array($this->action, array('cart_types', 'edit_cart_type'))
        );
        $submenu[] = array(
            'href' => $this->url . '&action=order_statuses', 
            'name' => $this->_('ORDER_STATUSES'),
            'active' => in_array($this->action, array('order_statuses', 'edit_order_status')),
        );
        $submenu[] = array(
            'href' => $this->url . '&action=priceloaders', 
            'name' => $this->_('PRICELOADERS'),
            'active' => in_array($this->action, array('priceloaders', 'edit_priceloader'))
        );
        $submenu[] = array(
            'href' => $this->url . '&action=imageloaders', 
            'name' => $this->_('IMAGELOADERS'),
            'active' => in_array($this->action, array('imageloaders', 'edit_imageloader'))
        );
        return $submenu;
    }


    public function cart_types(array $IN = array())
    {
        return $this->stdDictionaryShowlist($IN, 'CART_TYPES', 'edit_cart_type', 'getCartTypeContextMenu', 'NO_CART_TYPES_FOUND', 'ADD_CART_TYPE');
    }


    public function order_statuses(array $IN = array())
    {
        $IN['Table'] = new OrdersStatusesTable($IN);
        $this->assignVars($IN);
        $this->title = $IN['Table']->caption;
        $this->path[] = array('name' => $this->_('DEVELOPMENT'), 'href' => $this->url);
        $this->contextmenu = array(array('name' => $this->_('ADD_ORDER_STATUS'), 'href' => $this->url . '&action=edit_order_status', 'icon' => 'plus'));
        $this->template = $IN['Table']->template;
    }


    public function priceloaders(array $IN = array())
    {
        return $this->stdDictionaryShowlist($IN, 'PRICELOADERS', 'edit_priceloader', 'getPriceLoaderContextMenu', 'NO_PRICELOADERS_FOUND', 'ADD_PRICELOADER');
    }


    public function imageloaders(array $IN = array())
    {
        return $this->stdDictionaryShowlist($IN, 'IMAGELOADERS', 'edit_imageloader', 'getImageLoaderContextMenu', 'NO_IMAGELOADERS_FOUND', 'ADD_IMAGELOADER');
    }


    public function edit_cart_type(array $IN = array())
    {
        $this->js[] = $this->publicURL . '/dev_edit_cart_type.js';
        return $this->stdDictionaryEdit($IN, 'CART_TYPES', 'cart_types', 'getCartTypeContextMenu');
    }


    public function edit_order_status(array $IN = array())
    {
        return $this->stdDictionaryEdit($IN, 'ORDER_STATUSES', 'order_statuses', 'getOrderStatusContextMenu');
    }


    public function edit_priceloader(array $IN = array())
    {
        $this->js[] = $this->publicURL . '/dev_edit_priceloader.js';
        return $this->stdDictionaryEdit($IN, 'PRICELOADERS', 'priceloaders', 'getPriceLoaderContextMenu');
    }


    public function edit_imageloader(array $IN = array())
    {
        $this->js[] = $this->publicURL . '/dev_edit_imageloader.js';
        return $this->stdDictionaryEdit($IN, 'IMAGELOADERS', 'imageloaders', 'getImageLoaderContextMenu');
    }


    public function getCartTypeContextMenu(Cart_Type $Item) 
    {
        return $this->stdView->stdContextMenu($Item, 0, 0, 'edit_cart_type', 'cart_types', 'delete_cart_type');
    }


    public function getOrderStatusContextMenu(Order_Status $Item, $i = 0, $c = 0) 
    {
        return $this->stdView->stdContextMenu($Item, $i, $c, 'edit_order_status', 'order_statuses', 'delete_order_status', 'move_up_order_status', 'move_down_order_status');
    }


    public function getPriceLoaderContextMenu(PriceLoader $Item) 
    {
        return $this->stdView->stdContextMenu($Item, 0, 0, 'edit_priceloader', 'priceloaders', 'delete_priceloader');
    }


    public function getImageLoaderContextMenu(ImageLoader $Item) 
    {
        return $this->stdView->stdContextMenu($Item, 0, 0, 'edit_imageloader', 'imageloaders', 'delete_imageloader');
    }


    private function stdDictionaryShowlist(array $IN, $title, $editAction, $contextMenuName, $emptyString, $addString)
    {
        $this->path[] = array('name' => $this->_('DEVELOPMENT'), 'href' => \RAAS\CMS\ViewSub_Dev::i()->url);
        $this->stdView->stdShowlist($IN, $title, $editAction, $contextMenuName, $emptyString, $addString);
    }


    private function stdDictionaryEdit(array $IN, $title, $showListAction, $contextMenuName)
    {
        $this->path[] = array('name' => $this->_('DEVELOPMENT'), 'href' => \RAAS\CMS\ViewSub_Dev::i()->url);
        $this->path[] = array('name' => $this->_($title), 'href' => $this->url . '&action=' . $showListAction);
        $this->stdView->stdEdit($IN, $contextMenuName);
    }
}