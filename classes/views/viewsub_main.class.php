<?php
namespace RAAS\CMS\Shop;
use \RAAS\Table as Table;
use \RAAS\Column as Column;
use \RAAS\Row as Row;
use \RAAS\CMS\Material_Type;

class ViewSub_Main extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    public function getYMLMaterialTypeContextMenu(Material_Type $Item, Block_YML $Block)
    {
        $arr = array();
        if ($Item->id) {
            $showlist = ($this->view->action != 'edit_yml_type');
            $arr[] = array(
                'href' => $this->url . '&action=edit_yml_type&id=' . (int)$Block->id . '&mtype=' . (int)$Item->id, 'name' => $this->_('EDIT'), 'icon' => 'edit'
            );
            $arr[] = array(
                'href' => $this->url . '&action=delete_yml_type&id=' . (int)$Block->id . '&mtype=' . (int)$Item->id . ($showlist ? '&back=1' : ''), 
                'name' => $this->_('DELETE'), 
                'icon' => 'remove',
                'onclick' => 'return confirm(\'' . $this->view->_('DELETE_TEXT') . '\')'
            );
        }
        return $arr;

    }
}