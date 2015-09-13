<?php
namespace RAAS\CMS\Shop;
use \RAAS\Column;

class OrdersTable extends \RAAS\Table
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Orders::i();
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
        $columns['id'] = array(
            'caption' => '#',
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . (int)$row->id . '</a>';
            }
        );
        $columns['post_date'] = array(
            'caption' => $this->view->_('POST_DATE'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . date(DATETIMEFORMAT, strtotime($row->post_date)) . '</a>';
            }
        );
        if (!$params['Item']->id) {
            $columns['pid'] = array(
                'caption' => $this->view->_('CART_TYPE'),
                'callback' => function($row) use ($view) { 
                    return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . htmlspecialchars($row->parent->name) . '</a>';
                }
            );
        }
        $columns['name'] = array(
            'caption' => $this->view->_('PAGE'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' . htmlspecialchars($row->page->name) . '</a>';
            }
        );
        $columns['ip'] = array(
            'caption' => $this->view->_('IP_ADDRESS'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '" title="' . htmlspecialchars($row->description) . '">' 
                     .    htmlspecialchars($row->ip)
                     . '</a>';
            }
        );
        foreach ($params['columns'] as $key => $col) {
            $columns[$col->urn] = array(
                'caption' => $col->name,
                'callback' => function($row) use ($col) { if (isset($row->fields[$col->urn])) { $y = $row->fields[$col->urn]->doRich(); } return $y ? $y : ''; }
            );
        }
        $columns['status'] = array(
            'caption' => $this->view->_('STATUS'),
            'callback' => function($row) use ($view) { 
                $text = '<span class="text-' . ($row->paid ? 'success' : 'error') . '" title="' . $view->_($row->paid ? 'PAYMENT_PAID' : 'PAYMENT_NOT_PAID') . '">'
                      .    ($row->status->id ? $row->status->name : $view->_('ORDER_STATUS_NEW'))
                      . '</span>';
                return $text;
            }
        );
        $columns['c'] = array('caption' => $this->view->_('GOODS_COUNT'));
        $columns['total_sum'] = array('caption' => $this->view->_('SUM'));
        $columns[' '] = array('callback' => function ($row) use ($view) { return rowContextMenu($view->getOrderContextMenu($row)); });

        $defaultParams = array(
            'caption' => $params['Item']->name ? $params['Item']->name : $this->view->_('ORDERS'),
            'columns' => $columns,
            'emptyString' => $this->view->_('NO_NOTES_FOUND'),
            'callback' => function($Row) { if (!$Row->source->vis) { $Row->class = 'info'; } },
            'Set' => $params['Set'],
            'template' => 'feedback'
        );
        unset($params['columns']);

        // $arr = array_merge($defaultParams, $params);
        $arr = $defaultParams;
        parent::__construct($arr);
    }
}