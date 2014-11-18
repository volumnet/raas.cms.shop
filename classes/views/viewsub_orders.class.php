<?php
namespace RAAS\CMS\Shop;
use \RAAS\Table as Table;
use \RAAS\Column as Column;
use \RAAS\Row as Row;

class ViewSub_Orders extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    public function view(array $IN = array())
    {
        $this->assignVars($IN);
        $this->title = $this->_('ORDERS');
        $this->path[] = array('name' => $this->_('ORDERS'), 'href' => $this->url);
        $this->path[] = array('name' => $IN['Item']->parent->name, 'href' => $this->url . '&id=' . $IN['Item']->pid);
        $this->contextmenu = $this->getOrderContextMenu($IN['Item']);
        $this->template = 'order_view';
    }


    public function getOrderContextMenu(Order $Item) 
    {
        $arr = array();
        if ($Item->id) {
            $edit = ($this->action == 'view');
            if (!$edit) {
                $arr[] = array('href' => $this->url . '&action=view&id=' . (int)$Item->id, 'name' => $this->_('VIEW'), 'icon' => 'edit');
                if ($Item->vis) {
                    $arr[] = array('href' => $this->url . '&action=chvis&id=' . (int)$Item->id, 'name' => $this->_('MARK_AS_UNREAD'), 'icon' => 'eye-close');
                }
            }
            $arr[] = array(
                'href' => $this->url . '&action=delete&id=' . (int)$Item->id . ($edit ? '' : '&back=1'), 
                'name' => $this->_('DELETE'), 
                'icon' => 'remove',
                'onclick' => 'return confirm(\'' . $this->_('DELETE_TEXT') . '\')'
            );
        }
        return $arr;
    }
    
    
    public function orders(array $IN = array())
    {
        $view = $this;
        $IN['Table'] = new OrdersTable($IN);
        
        $this->assignVars($IN);
        if ($IN['Item']->id) {
            $this->path[] = array('name' => $this->_('ORDERS'), 'href' => $this->url);
        }
        $this->title = $IN['Item']->name ? $IN['Item']->name : $this->_('ORDERS');
        $this->template = 'orders';

    }
}