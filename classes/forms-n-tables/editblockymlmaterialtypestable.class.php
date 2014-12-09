<?php
namespace RAAS\CMS\Shop;
use \RAAS\Column;

class EditBlockYMLMaterialTypesTable extends \RAAS\Table
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Main::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = array())
    {
        $view = $this->view;
        $Item = $params['Item'];
        $columns = array();
        $columns['name'] = array(
            'caption' => $this->view->_('MATERIAL_TYPE'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=edit_yml_type&id=' . (int)$Item->id . '&mtype=' . (int)$row->id . '">' . htmlspecialchars($row->name) . '</a>';
            }
        );
        $columns['type'] = array(
            'caption' => $this->view->_('YANDEX_MARKET_TYPE'),
            'callback' => function($row) use ($view) { 
                return '<a href="' . $view->url . '&action=edit_yml_type&id=' . (int)$Item->id . '&mtype=' . (int)$row->id . '">' . htmlspecialchars($row->ymlType) . '</a>';
            }
        );
        $columns[' '] = array('callback' => function ($row) use ($view) { return rowContextMenu($view->getYMLMaterialTypeContextMenu($row, $Item)); });

        $defaultParams = array(
            'caption' => $params['Item']->name ? $params['Item']->name : $this->view->_('ORDERS'),
            'columns' => $columns,
            'emptyString' => $this->view->_('NO_NOTES_FOUND'),
            'Set' => $params['Item']->types,
            'template' => 'feedback'
        );

        // $arr = array_merge($defaultParams, $params);
        $arr = $defaultParams;
        parent::__construct($arr);
    }
}