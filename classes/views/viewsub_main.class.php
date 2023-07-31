<?php
namespace RAAS\CMS\Shop;
use \RAAS\Table as Table;
use \RAAS\Column as Column;
use \RAAS\Row as Row;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Page;
use \RAAS\CMS\ViewSub_Main as CMS_ViewSub_Main;

class ViewSub_Main extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    public function edit_yml_type(array $IN = array())
    {
        $v = CMS_ViewSub_Main::i();
        $this->assignVars($IN);
        $this->title = $IN['Form']->caption;
        $this->path[] = array('href' => $v->url, 'name' => $this->_('PAGES'));
        if ($IN['Parent']->id) {
            if ($IN['Parent']->parents) {
                foreach ($IN['Parent']->parents as $row) {
                    $this->path[] = array('href' => $v->url . '&id=' . (int)$row->id, 'name' => $row->name);
                }
            }
            $this->path[] = array('href' => $v->url . '&id=' . (int)$IN['Parent']->id, 'name' => $IN['Parent']->name);
        }
        $this->path[] = array('href' => $v->url . '&action=edit_block&id=' . (int)$IN['Item']->id, 'name' => $IN['Item']->name . ' — ' . $this->_('EDITING_BLOCK'));
        $this->submenu = $v->pagesMenu(new Page(), $IN['Parent']);
        $this->template = $IN['Form']->template;
    }


    public function getYMLMaterialTypeContextMenu(Material_Type $Item, Block_YML $Block)
    {
        $arr = array();
        if ($Item->id) {
            $showlist = ($this->action != 'edit_yml_type');
            $arr[] = array(
                'href' => $this->url . '&action=edit_yml_type&id=' . (int)$Block->id . '&mtype=' . (int)$Item->id, 'name' => $this->_('EDIT'), 'icon' => 'edit'
            );
            $arr[] = array(
                'href' => $this->url . '&action=delete_yml_type&id=' . (int)$Block->id . '&mtype=' . (int)$Item->id . ($showlist ? '&back=1' : ''), 
                'name' => $this->_('DELETE'), 
                'icon' => 'remove',
                'onclick' => 'return confirm(\'' . $this->_('DELETE_TEXT') . '\')'
            );
        }
        return $arr;
    }
    
    
    public function getAllYMLMaterialTypesContextMenu()
    {
        $arr = array();
        $arr[] = array(
            'name' => $this->_('DELETE'), 
            'href' => $this->url . '&action=delete_yml_type&back=1', 
            'icon' => 'remove', 
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')'
        );
        return $arr;
    }
}
