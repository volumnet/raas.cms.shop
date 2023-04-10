<?php
namespace RAAS\CMS\Shop;

class CartTypesTable extends \RAAS\Table
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
        $defaultParams = [
            'caption' => $this->view->_('CART_TYPES'),
            'columns' => [
                'id' => [
                    'caption' => $this->view->_('ID'),
                    'callback' => function ($row) use ($view) {
                        return '<a href="' . $view->url . '&action=edit_cart_type&id=' . (int)$row->id . '">' .
                                  (int)$row->id .
                               '</a>';
                    }
                ],
                'name' => [
                    'caption' => $this->view->_('NAME'),
                    'callback' => function ($row) use ($view) {
                        return '<a href="' . $view->url . '&action=edit_cart_type&id=' . (int)$row->id . '">
                                  ' . htmlspecialchars($row->name) . '
                                </a>';
                    }
                ],
                'urn' => [
                    'caption' => $this->view->_('URN'),
                    'callback' => function ($row) use ($view) {
                        return '<a href="' . $view->url . '&action=edit_cart_type&id=' . (int)$row->id . '">
                                  ' . htmlspecialchars($row->urn) . '
                                </a>';
                    }
                ],
                ' ' => [
                    'callback' => function (
                        $row,
                        $i
                    ) use (
                        $view,
                        $params
                    ) {
                        return rowContextMenu($view->getCartTypeContextMenu($row, $i, count((array)$params['Set'])));
                    }
                ]

            ],
            'emptyString' => $this->view->_('NO_CART_TYPES_FOUND'),
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
