<?php
/**
 * Таблица материалов
 */
namespace RAAS\CMS\Shop;

use RAAS\Column;
use RAAS\Table;
use RAAS\CMS\MaterialsTable;
use RAAS\CMS\ViewSub_Main as PackageView;

/**
 * Класс таблицы материалов, не задействованных в загрузке прайса
 * @property-read ViewSub_Main $view Представление
 */
class PriceloaderUnaffectedMaterialsTable extends MaterialsTable
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
        parent::__construct($params);
        $this->meta['allContextMenu'] = ViewSub_Priceloaders::i()->getAllUnaffectedMaterialsContextMenu($params['loader']);
        $this->meta['allValue'] = $params['searchString'] ? 'ids' : 'all';
        $this->columns['id']->callback =  function ($row) {
            return '<a href="?p=cms&action=edit_material&id=' . (int)$row->id . '" ' . (!$row->vis ? 'class="muted"' : '') . ' target="_blank">'
                 .    (int)$row->id
                 . '</a>';
        };
        $this->columns[' ']->callback = function ($row) {
            return rowContextMenu($this->view->getUnaffectedMaterialContextMenu($row));
        };
        unset($this->columns['priority']);

        foreach (array_filter(
            $params['mtype']->fields,
            function ($x) {
                return ($x->datatype == 'image') && $x->show_in_table;
            }
        ) as $key => $col) {
            if (isset($this->columns[$col->urn])) {
                $this->columns[$col->urn]->callback = function ($row) use ($col) {
                    $v = $row->fields[$col->urn]->getValue();
                    if ($v && $v->id) {
                        return '<a href="?p=cms&action=edit_material&id=' . (int)$row->id . '" ' . (!$row->vis ? 'class="muted"' : '') . ' target="_blank">' .
                                 '<img src="/' . $v->tnURL . '" style="max-width: 48px;" />' .
                               '</a>';
                    }
                };
            }
        }
        $this->columns['name']->callback = function ($row) use ($params) {
            $text = '<a href="?p=cms&action=edit_material&id=' . (int)$row->id . '" ' . (!$row->vis ? 'class="muted"' : '') . '>'
                  .    htmlspecialchars($row->name)
                  . '</a>';
            if (!$params['mtype']->global_type) {
                $pagesCounter = (int)$row->pages_counter;
                if ($pagesCounter > 1) {
                    $text .= '<sup title="' . $this->view->_('ASSOCIATED_WITH_PAGES_COUNTER') . '">(' . $pagesCounter . ')</sup>';
                }
            }
            return $text;
        };
        foreach (array_filter(
            $params['mtype']->fields,
            function ($x) {
                return ($x->datatype != 'image') && $x->show_in_table;
            }
        ) as $key => $col) {
            if (($col->datatype == 'material') && isset($this->columns[$col->urn])) {
                $this->columns[$col->urn]->callback = function ($row) use ($col) {
                    $f = $row->fields[$col->urn];
                    $v = $f->getValue();
                    $m = new Material($v);
                    if ($m->id) {
                        return '<a href="?p=cms&action=edit_material&id=' . (int)$m->id . '" ' . (!$m->vis ? 'class="muted"' : '') . ' target="_blank">'
                             .    htmlspecialchars($m->name)
                             . '</a>';
                    }
                };
            }
        }
    }
}
