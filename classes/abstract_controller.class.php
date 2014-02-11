<?php
namespace RAAS\Cms\Shop;

abstract class Abstract_Controller extends \RAAS\Abstract_Module_Controller
{
    protected static $instance;
    
    protected function execute()
    {
        switch ($this->sub) {
            case 'dev':
                parent::execute();
                break;
            default:
                break;
        }
    }
}