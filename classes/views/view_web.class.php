<?php
namespace RAAS\CMS\Shop;

class View_Web extends \RAAS\Module_View_Web
{
    protected static $instance;

    public function header()
    {
        $this->css[] = $this->publicURL . '/style.css';
        $c = Order::unreadFeedbacks();
        $menuItem = array(array(
            'href' => '?p=' . $this->package->alias . '&m=' . $this->module->alias,
            'name' => $this->_('__NAME') . ($c ? ' (' . $c . ')' : ''),
            'active' => ($this->moduleName == 'shop') && ($this->sub != 'dev')
        ));
        $menu = $this->menu->getArrayCopy();
        array_splice($menu, -1, 0, $menuItem);
        $this->menu = new \ArrayObject($menu);
    }


    public function shopMenu()
    {
        $submenu = array();
        $c = Order::unreadFeedbacks();
        $menuItem = array(
            'href' => $this->url,
            'name' => $this->_('ORDERS') . ($c ? ' (' . $c . ')' : ''),
            'submenu' => array(),
            'active' => !$this->sub
        );
        foreach (Cart_Type::getSet() as $row) {
            if ($row->form_id) {
                $menuItem['submenu'][] = array(
                    'name' => $row->name . ($row->unreadOrders ? ' (' . (int)$row->unreadOrders . ')' : ''),
                    'href' => $this->url . '&id=' . (int)$row->id,
                    'active' => ($row->id == $this->id)
                );
            }
        }
        $submenu[] = $menuItem;
        $submenu[] = array('href' => $this->url . '&sub=priceloaders', 'name' => $this->_('PRICELOADERS'));
        $submenu[] = array('href' => $this->url . '&sub=imageloaders', 'name' => $this->_('IMAGELOADERS'));
        return $submenu;
    }


    public function getAllOrderGoodsContextMenu(Order $order)
    {
        $arr = array();
        $arr[] = array(
            'name' => $this->_('MOVE_TO_ANOTHER_ORDER'),
            'href' => $this->url . '&action=move_order_goods&order_id=' . (int)$order->id,
            'icon' => 'share-alt',
        );
        $arr[] = array(
            'name' => $this->_('MOVE_TO_NEW_ORDER'),
            'href' => $this->url . '&action=move_order_goods&new=1&order_id=' . (int)$order->id,
            'icon' => 'asterisk',
        );
        return $arr;
    }
}
