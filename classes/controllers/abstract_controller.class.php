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
            case 'priceloaders':
                Sub_Priceloaders::i()->run();
                break;
            case 'imageloaders':
                Sub_Imageloaders::i()->run();
                break;
            default:
                if (in_array($this->action, array('edit_yml_type', 'delete_yml_type'))) {
                    Sub_Main::i()->run();
                } else {
                    Sub_Orders::i()->run();
                }
                break;
        }
    }


    public function config()
    {
        return array(
            array('type' => 'checkbox', 'name' => 'allow_order_edit', 'caption' => $this->view->_('ALLOW_ORDER_EDIT')),
        );
    }
}
