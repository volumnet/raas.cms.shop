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
        $menuItem = array(
            'href' => $this->url, 
            'name' => $this->_('ORDERS'), 
            'submenu' => array()
        );
        foreach (Cart_Type::getSet() as $row) {
            $menuItem['submenu'][] = array(
                'name' => $row->name . ($row->unreadOrders ? ' (' . (int)$row->unreadOrders . ')' : ''), 
                'href' => $this->url . '&id=' . (int)$row->id, 
                'active' => ($row->id == $this->id)
            );
        }
        $submenu[] = $menuItem;
        return $submenu;
    }
}