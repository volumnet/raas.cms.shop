<?php
/**
 * Представление подмодуля загрузчиков прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\Column;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\ViewSub_Main as PackageView;

/**
 * Представление подмодуля загрузчиков прайсов
 */
class ViewSub_Priceloaders extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    /**
     * Отображение параметров загрузки загрузчика
     * @param array $in Входные данные
     */
    public function upload(array $in = [])
    {
        $this->assignVars($in);
        $this->title = $in['Form']->caption;
        $this->path = [[
            'name' => $this->_('PRICELOADERS'),
            'href' => $this->url,
        ]];
        $this->contextmenu = $this->getLoaderContextMenu($in['loader']);
        $this->template = $in['Form']->template;
    }


    /**
     * Пошаговая загрузка
     * @param array $in Входные данные
     */
    public function stepUpload(array $in = [])
    {
        $this->assignVars($in);
        $this->title = $in['loader']->name;
        $this->path = [[
            'name' => $this->_('PRICELOADERS'),
            'href' => $this->url,
        ]];
        $this->subtitle = $this->_('MATERIAL_TYPE') . ': ' . htmlspecialchars($in['loader']->Material_Type->name);
        $this->contextmenu = $this->getLoaderContextMenu($in['loader']);
        $this->template = 'priceloader_step_upload.tmp.php';
    }


    /**
     * Отображение параметров выгрузки загрузчика
     * @param array $in Входные данные
     */
    public function download(array $in = [])
    {
        $this->assignVars($in);
        $this->title = $in['Form']->caption;
        $this->path = [
            [
                'name' => $this->_('PRICELOADERS'),
                'href' => $this->url,
            ],
            [
                'name' => $in['loader']->name,
                'href' => $this->url . '&id=' . (int)$in['loader']->id,
            ],
        ];
        $this->contextmenu = $this->getLoaderContextMenu($in['loader']);
        $this->template = $in['Form']->template;
    }


    /**
     * Отображение списка загрузчиков прайсов
     * @param array $in Входные данные
     */
    public function showlist(array $in = [])
    {
        $in['Table'] = new PriceLoadersSelectTable($in);
        $this->assignVars($in);
        $this->title = $in['Table']->caption;
        $this->template = $in['Table']->template;
    }


    /**
     * Отображение списков незадействованных материалов и страниц
     * @param array $in Входные данные
     */
    public function unaffected(array $in = [])
    {
        $in['Table'] = new PriceloaderUnaffectedMaterialsTable([
            'Item' => null,
            'mtype' => $in['mtype'],
            'loader' => $in['loader'],
            'searchString' => $in['searchString'],
            'Set' => $in['Set'],
            'Pages' => $in['Pages'],
            'sortVar' => 'sort',
            'orderVar' => 'order',
            'pagesVar' => 'page',
            'sort' => $in['sort'],
            'order' => (strtolower($in['order']) == 'desc') ? Column::SORT_DESC : Column::SORT_ASC,
        ]);
        $this->assignVars($in);
        $this->title = $in['loader']->name . ': ' . $this->_('WERE_NOT_AFFECTED');
        $this->path = [
            [
                'name' => $this->_('PRICELOADERS'),
                'href' => $this->url,
            ],
            [
                'name' => $in['loader']->name,
                'href' => $this->url . '&id=' . (int)$in['loader']->id . '&step=' . $in['step'],
            ],
        ];
        $this->template = 'priceloader_unaffected.tmp.php';
    }


    /**
     * Получает контекстное меню загрузчика
     * @param PriceLoader $loader Загрузчик
     * @return array
     */
    public function getLoaderContextMenu(PriceLoader $loader): array
    {
        $arr = [];
        if (($this->id != $loader->id) || ($this->action == 'download')) {
            $arr[] = [
                'href' => $this->url . '&id=' . (int)$loader->id,
                'name' => $this->_('UPLOAD'),
                'icon' => 'upload',
            ];
        }
        if ($this->action != 'download') {
            $arr[] = [
                'href' => $this->url . '&action=download&id=' . (int)$loader->id,
                'name' => $this->_('DOWNLOAD'),
                'icon' => 'download',
            ];
        }
        return $arr;
    }


    /**
     * Возвращает контекстное меню для незадействованного материала
     * @param Material $Item Материал
     * @return array<[
     *             'href' ?=> string Ссылка,
     *             'name' => string Заголовок пункта
     *             'icon' ?=> string Наименование иконки,
     *             'title' ?=> string Всплывающая подсказка
     *             'onclick' ?=> string JavaScript-команда при клике,
     *         ]>
     */
    public function getUnaffectedMaterialContextMenu(Material $item): array
    {
        $arr = [];
        if ($item->id) {
            if ($item->urlParent->id) {
                $arr[] = [
                    'name' => $this->_('BROWSE'),
                    'href' => $item->conditionalDomainURL,
                    'icon' => 'globe',
                    'target' => '_blank',
                    'active' => false,
                ];
            }

            $arr[] = [
                'href' => '?p=cms&action=edit_material&id=' . (int)$item->id,
                'name' => $this->_('EDIT'),
                'icon' => 'edit',
                'target' => '_blank',
            ];
            if ($item->vis) {
                $arr[] = [
                    'name' => $this->_('VISIBLE'),
                    'href' => '?p=cms&action=chvis_material&id=' . (int)$item->id . '&back=1',
                    'icon' => 'ok',
                    'title' => $this->_('HIDE')
                ];
            } else {
                $arr[] = [
                    'name' => '<span class="muted">' . $this->_('INVISIBLE') . '</span>',
                    'href' => '?p=cms&action=chvis_material&id=' . (int)$item->id . '&back=1',
                    'icon' => '',
                    'title' => $this->_('SHOW')
                ];
            }
            $arr[] = [
                'href' => '?p=cms&action=delete_material&id=' . (int)$item->id . '&back=1',
                'name' => $this->_('DELETE'),
                'icon' => 'remove',
                'onclick' => 'return confirm(\'' . $this->_('DELETE_TEXT') . '\')',
            ];
        }
        return $arr;
    }


    /**
     * Возвращает контекстное меню для списка незадействованных материалов
     * @param PriceLoader $loader Загрузчик
     * @return array<[
     *             'href' ?=> string Ссылка,
     *             'name' => string Заголовок пункта
     *             'icon' ?=> string Наименование иконки,
     *             'title' ?=> string Всплывающая подсказка
     *             'onclick' ?=> string JavaScript-команда при клике,
     *         ]>
     */
    public function getAllUnaffectedMaterialsContextMenu(PriceLoader $loader): array
    {
        $arr = [];
        $arr[] = [
            'name' => $this->_('SHOW'),
            'href' => $this->url . '&action=vis_unaffected_material&pid=' . (int)$loader->id . '&back=1',
            'icon' => 'eye-open',
            'title' => $this->_('SHOW')
        ];
        $arr[] = [
            'name' => $this->_('HIDE'),
            'href' => $this->url . '&action=invis_unaffected_material&pid=' . (int)$loader->id . '&back=1',
            'icon' => 'eye-close',
            'title' => $this->_('HIDE')
        ];
        $arr[] = [
            'name' => $this->_('DELETE'),
            'href' => $this->url . '&action=delete_unaffected_material&pid=' . (int)$loader->id . '&back=1',
            'icon' => 'remove',
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')',
        ];
        return $arr;
    }
}
