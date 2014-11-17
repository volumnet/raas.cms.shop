<?php
namespace RAAS\CMS\Shop;
use \RAAS\Redirector as Redirector;
use \RAAS\Attachment as Attachment;
use \ArrayObject as ArrayObject;
use \RAAS\Field as Field;
use \RAAS\FieldSet as FieldSet;
use \RAAS\FieldContainer as FieldContainer;
use \RAAS\Form as RAASForm;
use \RAAS\FormTab as FormTab;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\OptGroup as OptGroup;
use \RAAS\Option as Option;
use \RAAS\StdSub as StdSub;
use \RAAS\CMS\Material_Type as Material_Type;
use \RAAS\CMS\Material_Field as Material_Field;

class Sub_Dev extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        $this->view->submenu = \RAAS\CMS\ViewSub_Dev::i()->devMenu();
        switch ($this->action) {
            case 'edit_cart_type': case 'edit_order_status': case 'edit_priceloader': case 'edit_imageloader':
                $this->{$this->action}();
                break;
            case 'cart_types':
                $this->view->{$this->action}(array('Set' => Cart_Type::getSet()));
                break;
            case 'order_statuses':
                $this->view->{$this->action}(array('Set' => Order_Status::getSet()));
                break;
            case 'priceloaders':
                $this->view->{$this->action}(array('Set' => PriceLoader::getSet()));
                break;
            case 'imageloaders':
                $this->view->{$this->action}(array('Set' => ImageLoader::getSet()));
                break;
            case 'move_up_order_status': case 'move_down_order_status':
                $Item = new Order_Status((int)$this->id);
                $f = str_replace('_order_status', '', $this->action);
                StdSub::$f($Item, $this->url . '&action=order_statuses');
                break;
            case 'delete_cart_type':
                $Item = new Cart_Type((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=cart_types');
                break;
            case 'delete_order_status':
                $Item = new Order_Status((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=order_statuses');
                break;
            case 'delete_priceloader':
                $Item = new PriceLoader((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=priceloaders');
                break;
            case 'delete_imageloader':
                $Item = new ImageLoader((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=imageloaders');
                break;
            default:
                new Redirector(\RAAS\CMS\ViewSub_Dev::i()->url);
                break;
        }
    }


    protected function edit_cart_type()
    {
        $Item = new Cart_Type((int)$this->id);
        $Form = new EditCartTypeForm(array('Item' => $Item));
        $this->view->{__FUNCTION__}($Form->process());
    }


    protected function edit_order_status()
    {
        $Item = new Order_Status((int)$this->id);
        $Form = new EditOrderStatusForm(array('Item' => $Item));
        $this->view->{__FUNCTION__}($Form->process());
    }


    protected function edit_priceloader()
    {
        $Item = new PriceLoader((int)$this->id);
        $Form = new EditPriceLoaderForm(array('Item' => $Item));
        $this->view->{__FUNCTION__}($Form->process());
    }


    protected function edit_imageloader()
    {
        $Item = new ImageLoader((int)$this->id);
        $Form = new EditImageLoaderForm(array('Item' => $Item));
        $this->view->{__FUNCTION__}($Form->process());
    }
}