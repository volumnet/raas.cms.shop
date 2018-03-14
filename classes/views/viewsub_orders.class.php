<?php
namespace RAAS\CMS\Shop;

use \RAAS\Table as Table;
use \RAAS\Column as Column;
use \RAAS\Row as Row;
use RAAS\CMS\Package;

class ViewSub_Orders extends \RAAS\Abstract_Sub_View
{
    protected static $instance;

    public function view(array $IN = array())
    {
        $this->assignVars($IN);
        $this->title = $IN['Form']->caption;
        $this->path[] = array('name' => $this->_('ORDERS'), 'href' => $this->url);
        $this->path[] = array('name' => $IN['Item']->parent->name, 'href' => $this->url . '&id=' . $IN['Item']->pid);
        $this->contextmenu = $this->getOrderContextMenu($IN['Item']);
        $this->template = $IN['Form']->template;
    }


    /**
     * Редактирование заказа
     * @param array $IN Входные данные
     */
    public function edit(array $IN = array())
    {
        $this->assignVars($IN);
        $this->title = $IN['Form']->caption;
        $this->path[] = array('name' => $this->_('ORDERS'), 'href' => $this->url);
        $this->path[] = array('name' => $IN['Item']->parent->name, 'href' => $this->url . '&id=' . $IN['Item']->pid);
        // $this->path[] = array('href' => $v->url . '&action=edit_block&id=' . (int)$IN['Item']->id, 'name' => $IN['Item']->name . ' вЂ” ' . $this->_('EDITING_BLOCK'));
        // $this->submenu = $v->pagesMenu(new Page(), $IN['Parent']);
        $this->js[] = $this->publicURL . '/field.inc.js';
        $this->js[] = $this->publicURL . '/edit_order.js';
        $this->contextmenu = $this->getOrderContextMenu($IN['Item']);
        $this->template = $IN['Form']->template;
    }


    public function getOrderContextMenu(Order $Item)
    {
        $arr = array();
        if ($Item->id) {
            $view = ($this->action == 'view');
            $edit = ($this->action == 'edit');
            if (!$view) {
                $arr[] = array('href' => $this->url . '&action=view&id=' . (int)$Item->id, 'name' => $this->_('VIEW'), 'icon' => 'edit');
            }
            if (!$edit && Module::i()->registryGet('allow_order_edit')) {
                $arr[] = array('href' => $this->url . '&action=edit&id=' . (int)$Item->id, 'name' => $this->_('EDIT'), 'icon' => 'edit');
            }
            if ($Item->vis && !$edit && !$view) {
                $arr[] = array('href' => $this->url . '&action=chvis&id=' . (int)$Item->id, 'name' => $this->_('MARK_AS_UNREAD'), 'icon' => 'eye-close');
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


    /**
     * Контекстное меню страницы отзывов
     */
    public function getOrdersContextMenu()
    {
        $arr = array();
        if (Module::i()->registryGet('allow_order_edit') && $this->id) {
            $arr[] = array(
                'name' => $this->_('ADD_ORDER'),
                'href' => $this->url . '&action=edit&pid=' . (int)$this->id,
                'icon' => 'plus',
            );
        }
        return $arr;
    }


    public function getAllOrdersContextMenu()
    {
        $arr = array();
        $arr[] = array(
            'name' => $this->_('MARK_AS_UNREAD'),
            'href' => $this->url . '&action=invis&back=1',
            'icon' => 'eye-close',
            'title' => $this->_('MARK_AS_UNREAD')
        );
        $arr[] = array(
            'name' => $this->_('DELETE'),
            'href' => $this->url . '&action=delete&back=1',
            'icon' => 'remove',
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')'
        );
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
        $this->contextmenu = $this->getOrdersContextMenu($IN['Item']);
    }
}
