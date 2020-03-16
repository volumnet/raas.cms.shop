<?php
namespace RAAS\CMS\Shop;

class PriceLoadersTable extends \RAAS\Table
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
            'caption' => $this->view->_('PRICELOADERS'),
            'columns' => [
                'id' => [
                    'caption' => $this->view->_('ID'),
                    'callback' => function ($row) use ($view) {
                        return '<a href="' . $view->url . '&action=edit_priceloader&id=' . (int)$row->id . '">' .
                                  (int)$row->id .
                               '</a>';
                    }
                ],
                'name' => [
                    'caption' => $this->view->_('NAME'),
                    'callback' => function ($row) use ($view) {
                        return '<a href="' . $view->url . '&action=edit_priceloader&id=' . (int)$row->id . '">
                                  ' . htmlspecialchars($row->name) . '
                                </a>';
                    }
                ],
                'urn' => [
                    'caption' => $this->view->_('URN'),
                    'callback' => function ($row) use ($view, $Item) {
                        return '<a href="' . $view->url . '&action=edit_priceloader&id=' . (int)$row->id . '">
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
                        $contextMenuName,
                        $IN
                    ) {
                        return rowContextMenu($view->getPriceLoaderContextMenu($row, $i, count($IN['Set'])));
                    }
                ]
            ],
            'emptyString' => $this->view->_('NO_PRICELOADERS_FOUND'),
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
