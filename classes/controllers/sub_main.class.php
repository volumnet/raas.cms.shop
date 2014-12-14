<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Page;
use \RAAS\CMS\Block;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Package;
use \RAAS\Redirector;

class Sub_Main extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        switch ($this->action) {
            case 'edit_yml_type': case 'delete_yml_type':
                $this->{$this->action}();
                break;
            default:
                break;
        }
    }
    
    
    protected function edit_yml_type()
    {
        $Item =  Block::spawn($this->id);
        $Parent = new Page((int)$this->nav['pid']);
        $MType = new Material_Type((int)$this->nav['mtype']);
        $type = isset(Block_YML::$ymlTypes[strtolower($_GET['yml_type'])]) ? strtolower($_GET['yml_type']) : '';
        $Form = new EditYMLTypeForm(array('Block' => $Item, 'MType' => $MType, 'type' => $type, 'Page' => $Parent));

        $OUT = array('Parent' => $Parent, 'Item' => $Item);
        $this->view->edit_yml_type(array_merge($Form->process(), $OUT));
    }
    
    
    protected function delete_yml_type()
    {
        $Item =  Block::spawn($this->id);
        $Parent = new Page((int)$this->nav['pid']);
        $MType = new Material_Type((int)$this->nav['mtype']);
        
        if (($Item instanceof Block_YML) && $MType->id) {
            $Item->removeType($MType);
        }
        $url = Package::i()->controller->url . '&action=edit_block&id=' . (int)$Item->id;
        new Redirector(isset($_GET['back']) ? 'history:back' : $url);
    }

}