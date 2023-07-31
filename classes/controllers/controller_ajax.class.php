<?php
namespace RAAS\CMS\Shop;

use SOME\Text;
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
        if ((int)$this->id) {
            $Field = new Material_Field((int)$this->id);
            $Set = [];
            if ($Field->datatype == 'material') {
                $mtype = (int)$Field->source;
            }
        } elseif ((int)$this->nav['mtype']) {
            $mtype = (int)$this->nav['mtype'];
        }
        $cartType = new Cart_Type((int)$this->nav['cart_type']);
        $cart = new Cart($cartType);
        $Set = Package::i()->getMaterialsBySearch(
            isset($_GET['search_string']) ? $_GET['search_string'] : '',
            $mtype
        );
        $OUT['Set'] = array_map(
            function ($x) use ($cart) {
                $y = [
                    'id' => (int)$x->id,
                    'name' => $x->name,
                    'description' => Text::cuttext(html_entity_decode(
                        strip_tags($x->description),
                        ENT_COMPAT | ENT_HTML5,
                        'UTF-8'
                    ), 256, '...')
                ];
                if ($x->parents) {
                    $y['pid'] = (int)$x->parents_ids[0];
                }
                $price = $cart->getPrice($x);
                if ($price) {
                    $y['price'] = $price;
                }
                foreach ($x->fields as $row) {
                    if ($row->datatype == 'image') {
                        if ($val = $row->getValue()) {
                            if ($val->id) {
                                $y['img'] = '/' . $val->fileURL;
                            }
                        }
                    }
                }
                return $y;
            },
            $Set
        );
        $this->view->show_page($OUT);
    }
}
