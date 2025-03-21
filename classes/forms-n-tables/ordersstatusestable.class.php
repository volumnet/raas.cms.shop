<?php
namespace RAAS\CMS\Shop;

use RAAS\Column;

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


    public function __construct(array $params = [])
    {
        $view = $this->view;
        $columns = [];
        $columns['id'] = [
            'caption' => $this->view->_('ID'),
            'callback' => function ($row) use ($view) {
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' .
                          (int)$row->id .
                       '</a>';
            }
        ];
        $columns['name'] = [
            'caption' => $this->view->_('NAME'),
            'callback' => function ($row) use ($view) {
                return '<a href="' . $view->url . '&action=edit_order_status&id=' . (int)$row->id . '">' . htmlspecialchars($row->name) . '</a>';
            }
        ];
        $columns['urn'] = ['caption' => $this->view->_('URN')];
        $columns['priority'] = [
            'caption' => $this->view->_('PRIORITY'),
            'callback' => function ($row, $i) {
                return '<input type="number" name="priority[' . (int)$row->id . ']" value="' . (($i + 1) * 10) . '" class="span1" min="0" />';
            }
        ];
        $columns[' '] = [
            'callback' => function ($row) use ($view) {
                return rowContextMenu($view->getOrderStatusContextMenu($row));
            }
        ];
        $defaultParams = [
            'caption' => $this->view->_('ORDER_STATUSES'),
            'emptyString' => $this->view->_('NO_ORDER_STATUSES_FOUND'),
            'Set' => $IN['Set'] ?? [],
            'data-role' => 'multitable',
            'meta' => [
                'allContextMenu' => $view->getAllOrderStatusesContextMenu(),
                'priorityColumn' => 'priority',
            ],
        ];
        $arr = array_merge($defaultParams, $params);
        $arr['columns'] = $columns;
        parent::__construct($arr);
    }
}
