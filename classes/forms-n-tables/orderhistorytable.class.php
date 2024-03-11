<?php
namespace RAAS\CMS\Shop;

use \RAAS\Column;
use \RAAS\CMS\Sub_Main as PackageSubMain;

class OrderHistoryTable extends \RAAS\Table
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
        $columns['post_date'] = array(
            'caption' => $this->view->_('POST_DATE'),
            'callback' => function ($row) use ($view) {
                return date($view->_('DATETIMEFORMAT'), strtotime($row->post_date));
            }
        );
        $columns['status_id'] = array(
            'caption' => $this->view->_('ORDER_STATUS'),
            'callback' => function ($row) use ($view) {
                return htmlspecialchars($row->status->id ? $row->status->name : $view->_('ORDER_STATUS_NEW'));
            }
        );
        $columns['paid'] = array(
            'caption' => $this->view->_('PAYMENT_STATUS'),
            'callback' => function ($row) use ($view) {
                return $view->_($row->paid ? 'PAYMENT_PAID' : 'PAYMENT_NOT_PAID');
            }
        );
        $columns['description'] = array(
            'caption' => $this->view->_('COMMENT'),
            'callback' => function ($row) use ($view) {
                return nl2br(htmlspecialchars($row->description));
            }
        );
        $columns['uid'] = array(
            'caption' => $this->view->_('AUTHOR'),
            'callback' => function ($row) use ($view) {
                return htmlspecialchars((string)($row->user->full_name ?: $row->user->login));
            }
        );
        $defaultParams = array(
            'columns' => $columns,
            'Set' => $params['Item']->history,
        );
        $arr = $defaultParams;
        parent::__construct($arr);
    }
}
