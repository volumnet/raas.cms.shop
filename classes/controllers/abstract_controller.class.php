<?php
namespace RAAS\CMS\Shop;

abstract class Abstract_Controller extends \RAAS\Abstract_Module_Controller
{
    protected static $instance;
    
    protected function execute()
    {
        $this->view->submenu = $this->view->shopMenu();
        switch ($this->sub) {
            case 'dev':
                parent::execute();
                break;
            default:
                Sub_Orders::i()->run();
                break;
        }
    }


}