<?php
namespace RAAS\CMS\Shop;
use \RAAS\IContext;
use \RAAS\CMS\Snippet;
use \RAAS\CMS\Snippet_Folder;

class Updater extends \RAAS\Updater
{
    public function __construct(IContext $Context)
    {
        parent::__construct($Context);
        $this->checkStdSnippets();
    }

    protected function checkStdSnippets()
    {
        $Item = Snippet::importByURN('__RAAS_shop_cart_interface');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_cart_interface', 'locked' => 1));
        }
        $Item->name = $this->view->_('CART_STANDARD_INTERFACE');
        $Item->description = $this->stdCartInterface;
        $Item->commit();

        $Item = Snippet::importByURN('__RAAS_shop_order_notify');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_order_notify', 'locked' => 1));
        }
        $Item->name = $this->view->_('ORDER_STANDARD_NOTIFICATION');
        $Item->description = $this->stdFormTemplate;
        $Item->commit();
    }
}