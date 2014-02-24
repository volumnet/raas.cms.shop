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
        $columns = array();
        $columns['post_date'] = array(
            'caption' => $this->_('POST_DATE'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . date(DATETIMEFORMAT, strtotime($row->post_date)) . '</a>';
            }
        );
        if (!$IN['Item']->id) {
            $columns['pid'] = array(
                'caption' => $this->_('CART_TYPE'),
                'callback' => function($row) use ($view) { 
                    return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . htmlspecialchars($row->parent->name) . '</a>';
                }
            );
        }
        $columns['name'] = array(
            'caption' => $this->_('PAGE'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . htmlspecialchars($row->page->name) . '</a>';
            }
        );
        $columns['ip'] = array(
            'caption' => $this->_('IP_ADDRESS'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '" title="' . htmlspecialchars($row->description) . '">' 
                     .    htmlspecialchars($row->ip)
                     . '</a>';
            }
        );
        foreach ($IN['columns'] as $key => $col) {
            $columns[$col->urn] = array(
                'caption' => $col->name,
                'callback' => function($row) use ($col) { $y = $row->fields[$col->urn]->doRich(); return $y ? $y : ''; }
            );
        }
        $columns['c'] = array('caption' => $this->_('GOODS_COUNT'));
        $columns['total_sum'] = array('caption' => $this->_('SUM'));
        $columns[' '] = array('callback' => function ($row) use ($view) { return rowContextMenu($view->getOrderContextMenu($row)); });
        $IN['Table'] = new Table(array(
            'columns' => $columns, 
            'Set' => $IN['Set'], 
            'Pages' => $IN['Pages'],
            'callback' => function($Row) { if (!$Row->source->vis) { $Row->class = 'info'; } },
        ));
        
        $this->assignVars($IN);
        if ($IN['Item']->id) {
            $this->path[] = array('name' => $this->_('ORDERS'), 'href' => $this->url);
        }
        $this->title = $IN['Item']->name ? $IN['Item']->name : $this->_('ORDERS');
        $this->template = 'orders';

    }
}