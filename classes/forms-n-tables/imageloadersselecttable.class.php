<?php
/**
 * Таблица выбора загрузчика изображений
 */
namespace RAAS\CMS\Shop;

use RAAS\Table;

/**
 * Таблица выбора загрузчика изображений
 */
class ImageLoadersSelectTable extends Table
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Imageloaders::i();
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
            'caption' => $this->view->_('IMAGELOADERS'),
            'columns' => [
                'name' => [
                    'caption' => $this->view->_('NAME'),
                    'callback' => function ($row) use ($view) {
                        return '<a href="' . $view->url . '&id=' . (int)$row->id . '">
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
            'emptyString' => $this->view->_('NO_IMAGELOADERS_FOUND'),
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
