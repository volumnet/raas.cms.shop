<?php
/**
 * Таблица товаров заказа
 */
namespace RAAS\CMS\Shop;

use RAAS\Column;
use RAAS\Table;
use RAAS\CMS\Sub_Main as PackageSubMain;

class OrderItemsTable extends Table
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
        $columns['name'] = [
            'caption' => $this->view->_('NAME'),
            'callback' => function ($row) use ($view) {
                if ($row->id) {
                    return '<a href="' . PackageSubMain::i()->url . '&action=edit_material&id=' . (int)$row->id . ($row->cache_url_parent_id ? ('&pid=' . (int)$row->cache_url_parent_id) : '') . '" title="' . htmlspecialchars($row->originalName) . '">' .
                              htmlspecialchars($row->name) .
                           '</a>';
                } else {
                    return htmlspecialchars($row->name);
                }
            }
        ];
        $columns['meta'] = [
            'caption' => $this->view->_('ADDITIONAL_INFO'),
            'callback' => function ($row) use ($view) {
                return htmlspecialchars($row->meta);
            }
        ];
        $columns['price'] = [
            'caption' => $this->view->_('PRICE'),
            'callback' => function ($row) use ($view) {
                return number_format($row->realprice, 2, '.', ' ');
            }
        ];
        $columns['amount'] = [
            'caption' => $this->view->_('AMOUNT'),
            'callback' => function ($row) use ($view) {
                return (int)$row->amount;
            }
        ];
        $columns['sum'] = [
            'caption' => $this->view->_('SUM'),
            'style' => 'white-space: nowrap',
            'callback' => function ($row) use ($view) {
                return number_format($row->amount * $row->realprice, 2, '.', ' ');
            }
        ];
        $defaultParams = [
            'columns' => $columns,
            'Set' => $params['items'] ?? [],
        ];
        $arr = $defaultParams;
        parent::__construct($arr);
    }
}
