<?php
namespace RAAS\CMS\Shop;
use \RAAS\Column;

class OrdersStatusesTable extends \RAAS\Table
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Dev::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = array())
    {
        $view = $this->view;
        $columns = array();
        $columns['name'] = array(
            'caption' => $this->view->_('NAME'), 
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=edit_order_status&id=' . (int)$row->id . '">' . htmlspecialchars($row->name) . '</a>'; 
            }
        );
        $columns['urn'] = array('caption' => $this->view->_('URN'));
        $columns[' '] = array('callback' => function ($row) use ($view) { return rowContextMenu($view->getOrderStatusContextMenu($row)); });
        $defaultParams = array(
            'caption' => $this->view->_('ORDER_STATUSES'),
            'emptyString' => $this->view->_('NO_ORDER_STATUSES_FOUND'),
            'Set' => $IN['Set']
        );
        $arr = array_merge($defaultParams, $params);
        $arr['columns'] = $columns;
        parent::__construct($arr);
    }
}