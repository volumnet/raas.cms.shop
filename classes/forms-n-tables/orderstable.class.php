<?php
/**
 * Таблица заказов
 */
namespace RAAS\CMS\Shop;

use RAAS\Table;
use RAAS\CMS\Material;

class OrdersTable extends Table
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
        $columns['post_date'] = [
            'caption' => $this->view->_('POST_DATE'),
            'callback' => function ($row) use ($view) {
                return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' .
                          date(DATETIMEFORMAT, strtotime($row->post_date)) .
                       '</a>';
            }
        ];
        if (!$params['Item']->id) {
            $columns['pid'] = [
                'caption' => $this->view->_('CART_TYPE'),
                'callback' => function ($row) use ($view) {
                    return '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '">' .
                              htmlspecialchars($row->parent->name) .
                           '</a>';
                }
            ];
        }
        foreach ($params['columns'] as $key => $col) {
            $columns[$col->urn] = [
                'caption' => $col->name,
                'callback' => function ($row) use ($col, $view) {
                    $text = '<a href="' . $view->url . '&action=view&id=' . (int)$row->id . '" title="' . htmlspecialchars($row->description) . '">';
                    $f = $row->fields[$col->urn];
                    switch ($f->datatype) {
                        case 'color':
                            $v = $f->getValue();
                            return '<span style="color: ' . htmlspecialchars($v) . '">' .
                                      htmlspecialchars($v) .
                                   '</span>';
                            break;
                        case 'htmlarea':
                            $text .= strip_tags($f->doRich());
                            break;
                        case 'file':
                            $v = $f->getValue();
                            $text .= $v->name;
                            break;
                        case 'image':
                            $v = $f->getValue();
                            $text .= '<img src="/' . $v->tnURL . '" style="max-width: 48px;" />';
                            break;
                        case 'material':
                            $v = $f->getValue();
                            $m = new Material($v);
                            if ($m->id) {
                                $text .= htmlspecialchars($m->name);
                            }
                            break;
                        case 'checkbox':
                            if ($f->multiple) {
                                $text .= $f->doRich();
                            } else {
                                if ((int)$f->getValue()) {
                                    $text .= '<span class="icon icon-ok"></span>';
                                }
                            }
                            break;
                        default:
                            if (isset($f)) {
                                $y = htmlspecialchars((string)$f->doRich());
                            }
                            $text .= $y ? $y : '';
                            break;
                    }
                    $text .= '</a>';
                    return $text;
                }
            ];
        }
        $columns['status'] = [
            'caption' => $this->view->_('STATUS'),
            'callback' => function ($row) use ($view) {
                $text = '<span class="text-' . ($row->paid ? 'success' : 'error') . '" title="' . $view->_($row->paid ? 'PAYMENT_PAID' : 'PAYMENT_NOT_PAID') . '">'
                      .    ($row->status->id ? $row->status->name : $view->_('ORDER_STATUS_NEW'))
                      . '</span>';
                return $text;
            }
        ];
        $columns['c'] = ['caption' => $this->view->_('GOODS_COUNT')];
        $columns['total_sum'] = ['caption' => $this->view->_('SUM')];
        $columns[' '] = [
            'callback' => function ($row) use ($view) {
                return rowContextMenu($view->getOrderContextMenu($row));
            }
        ];

        $defaultParams = [
            'caption' => $params['Item']->name ? $params['Item']->name : $this->view->_('ORDERS'),
            'columns' => $columns,
            'emptyString' => $this->view->_('NO_NOTES_FOUND'),
            'callback' => function ($Row) {
                if (!$Row->source->vis) {
                    $Row->class = 'info';
                }
            },
            'Set' => $params['Set'] ?? [],
            'Pages' => $params['Pages'] ?? null,
            'data-role' => 'multitable',
            'meta' => [
                'allContextMenu' => $view->getAllOrdersContextMenu(),
                'allValue' => 'all&pid=' . (int)($params['Item']->id ?? 0),
            ],
        ];
        unset($params['columns']);

        // $arr = array_merge($defaultParams, $params);
        $arr = $defaultParams;
        parent::__construct($arr);
    }
}
