<?php
/**
 * Таблица выбора загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\Table;

/**
 * Таблица выбора загрузчика прайсов
 */
class PriceLoadersSelectTable extends Table
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Priceloaders::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = [])
    {
        $defaultParams = [
            'caption' => $this->view->_('PRICELOADERS'),
            'columns' => [
                'name' => [
                    'caption' => $this->view->_('NAME'),
                    'callback' => function ($row) {
                        return '<a href="' . $this->view->url . '&id=' . (int)$row->id . '">
                                  ' . htmlspecialchars($row->name) . '
                                </a>';
                    }
                ],
                ' ' => [
                    'callback' => function ($loader) {
                        return rowContextMenu($this->view->getLoaderContextMenu($loader));
                    }
                ],
            ],
            'emptyString' => $this->view->_('NO_PRICELOADERS_FOUND'),
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
