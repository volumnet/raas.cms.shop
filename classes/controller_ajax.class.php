<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material_Type as Material_Type;

class Controller_Ajax extends Abstract_Controller
{
    protected static $instance;
    
    protected function execute()
    {
        switch ($this->action) {
            case 'material_fields':
                $this->{$this->action}();
                break;
        }
    }
    
    
    protected function material_fields()
    {
        
        $Material_Type = new Material_Type((int)$this->id);
        $Set = array(
            (object)array('id' => 'urn', 'name' => $this->view->_('URN')),
            (object)array('id' => 'name', 'name' => $this->view->_('NAME')),
            (object)array('id' => 'description', 'name' => $this->view->_('DESCRIPTION')),
        );
        $Set = array_merge(
            $Set, array_values(array_filter($Material_Type->fields, function($x) { return !($x->multiple || in_array($x->datatype, array('file', 'image'))); }))
        );
        $OUT['Set'] = array_map(function($x) { return array('val' => $x->id, 'text' => $x->name); }, $Set);
        $this->view->show_page($OUT);
    }
}