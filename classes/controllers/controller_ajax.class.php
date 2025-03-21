<?php
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\CMS\Controller_Ajax as RAASControllerAJAX;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;

class Controller_Ajax extends Abstract_Controller
{
    protected static $instance;

    protected function execute()
    {
        switch ($this->action) {
            case 'material_fields':
            case 'image_fields':
            case 'get_materials_by_field':
                $this->{$this->action}();
                break;
        }
    }


    protected function material_fields()
    {

        $Material_Type = new Material_Type((int)$this->id);
        $Set = [
            (object)[
                'val' => 'urn',
                'text' => $this->view->_('URN')
            ],
            (object)[
                'val' => 'vis',
                'text' => $this->view->_('VISIBILITY')
            ],
            (object)[
                'val' => 'name',
                'text' => $this->view->_('NAME')
            ],
            (object)[
                'val' => 'description',
                'text' => $this->view->_('DESCRIPTION')
            ],
            (object)[
                'val' => 'meta_title',
                'text' => $this->view->_('META_TITLE')
            ],
            (object)[
                'val' => 'meta_description',
                'text' => $this->view->_('META_DESCRIPTION')
            ],
            (object)[
                'val' => 'meta_keywords',
                'text' => $this->view->_('META_KEYWORDS')
            ],
            (object)[
                'val' => 'priority',
                'text' => $this->view->_('PRIORITY')
            ],
            (object)[
                'val' => 'h1',
                'text' => $this->view->_('H1')
            ],
            (object)[
                'val' => 'menu_name',
                'text' => $this->view->_('MENU_NAME')
            ],
            (object)[
                'val' => 'breadcrumbs_name',
                'text' => $this->view->_('BREADCRUMBS_NAME')
            ],
        ];
        // $Set = array_merge($Set, array_values($Material_Type->fields));
        foreach ((array)$Material_Type->fields as $row) {
            // 2017-02-27, AVS: убрали условие !$row->multiple, т.к. из прайсов могут загружаться и множественные поля
            // 2017-02-27, AVS: убрали ограничение !(in_array($row->datatype, ['file', 'image'])), т.к. был запрос на "хитрую" загрузку картинок из прайсов
            $Set[] = ['val' => (int)$row->id, 'text' => $row->name];
        }
        $OUT['Set'] = $Set;
        $this->view->show_page($OUT);
    }


    protected function image_fields()
    {

        $Material_Type = new Material_Type((int)$this->id);
        $Set = [

        ];
        $Set = array_merge(
            $Set,
            array_values(array_filter(
                $Material_Type->fields,
                function ($x) {
                    return $x->datatype == 'image';
                }
            ))
        );
        $OUT['Set'] = array_map(function ($x) {
            return ['val' => $x->id, 'text' => $x->name];
        }, $Set);
        $this->view->show_page($OUT);
    }


    protected function get_materials_by_field()
    {
        $mtype = 0;
        if ((int)$this->id) {
            $Field = new Material_Field((int)$this->id);
            $Set = [];
            if ($Field->datatype == 'material') {
                $mtype = (int)$Field->source;
            }
        } elseif ((int)($this->nav['mtype'] ?? 0)) {
            $mtype = (int)$this->nav['mtype'];
        }
        $cartType = new Cart_Type((int)$this->nav['cart_type']);
        $searchString = $_GET['search_string'] ?? '';
        $Set = Package::i()->getMaterialsBySearch($searchString, $mtype, 10);
        $OUT['Set'] = array_map(fn($material) => $this->formatMaterial($material, $cartType), $Set);
        $this->view->show_page($OUT);
    }


    /**
     * Форматирует материал для вывода подсказок
     * @param Material $material Материал
     * @param Cart_Type $cartType Тип корзины, относительно которой рассчитывается цена
     * @return array <pre><code>[
     *     'id' => int ID# материала,
     *     'name' => string Наименование материала,
     *     'description' => string Описание материала,
     *     'pid' =>? int ID# основного родительского раздела,
     *     'img' =>? string URL картинки
     * ]</code></pre>
     */
    public function formatMaterial(Material $material, Cart_Type $cartType)
    {
        $cart = new Cart($cartType);
        $result = RAASControllerAJAX::i()->formatMaterial($material);
        $price = $cart->getPrice($material);
        if ($price) {
            $result['price'] = $price;
        }
        return $result;
    }
}
