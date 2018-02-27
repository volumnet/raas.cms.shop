<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material_Type as Material_Type;

class Controller_Ajax extends Abstract_Controller
{
    protected static $instance;

    protected function execute()
    {
        switch ($this->action) {
            case 'material_fields': case 'image_fields':
                $this->{$this->action}();
                break;
        }
    }


    protected function material_fields()
    {

        $Material_Type = new Material_Type((int)$this->id);
        $Set = array(
            (object)array('val' => 'urn', 'text' => $this->view->_('URN')),
            (object)array('val' => 'vis', 'text' => $this->view->_('VISIBILITY')),
            (object)array('val' => 'name', 'text' => $this->view->_('NAME')),
            (object)array('val' => 'description', 'text' => $this->view->_('DESCRIPTION')),
            (object)array('val' => 'meta_title', 'text' => $this->view->_('META_TITLE')),
            (object)array('val' => 'meta_description', 'text' => $this->view->_('META_DESCRIPTION')),
            (object)array('val' => 'meta_keywords', 'text' => $this->view->_('META_KEYWORDS')),
            (object)array('val' => 'priority', 'text' => $this->view->_('PRIORITY')),
        );
        // $Set = array_merge($Set, array_values($Material_Type->fields));
        foreach ((array)$Material_Type->fields as $row) {
            // 2017-02-27, AVS: убрали условие !$row->multiple, т.к. из прайсов могут загружаться и множественные поля
            // 2017-02-27, AVS: убрали ограничение !(in_array($row->datatype, array('file', 'image'))), т.к. был запрос на "хитрую" загрузку картинок из прайсов
            $Set[] = array('val' => (int)$row->id, 'text' => $row->name);
        }
        $OUT['Set'] = $Set;
        $this->view->show_page($OUT);
    }


    protected function image_fields()
    {

        $Material_Type = new Material_Type((int)$this->id);
        $Set = array(

        );
        $Set = array_merge(
            $Set, array_values(array_filter($Material_Type->fields, function($x) { return $x->datatype == 'image'; }))
        );
        $OUT['Set'] = array_map(function($x) { return array('val' => $x->id, 'text' => $x->name); }, $Set);
        $this->view->show_page($OUT);
    }
}
