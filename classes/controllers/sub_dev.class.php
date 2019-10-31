<?php
namespace RAAS\CMS\Shop;

use RAAS\Redirector;
use RAAS\StdSub;
use RAAS\CMS\Package;
use RAAS\CMS\ViewSub_Dev as CMSViewSubDev;
use RAAS\Abstract_Sub_Controller as RAASAbstractSubController;

class Sub_Dev extends RAASAbstractSubController
{
    protected static $instance;

    public function run()
    {
        $this->view->submenu = CMSViewSubDev::i()->devMenu();
        switch ($this->action) {
            case 'edit_cart_type':
            case 'edit_order_status':
            case 'edit_priceloader':
            case 'copy_priceloader':
            case 'edit_imageloader':
            case 'copy_imageloader':
            case 'order_statuses':
                $this->{$this->action}();
                break;
            case 'cart_types':
                $this->view->{$this->action}(['Set' => Cart_Type::getSet()]);
                break;
            case 'priceloaders':
                $this->view->{$this->action}(['Set' => PriceLoader::getSet()]);
                break;
            case 'imageloaders':
                $this->view->{$this->action}(['Set' => ImageLoader::getSet()]);
                break;
            case 'delete_cart_type':
                $ids = (array)$_GET['id'];
                $items = array_map(function ($x) {
                    return new Cart_Type((int)$x);
                }, $ids);
                $items = array_filter($items, function ($x) {
                    return !$x->locked;
                });
                $items = array_values($items);
                StdSub::delete($items, $this->url . '&action=cart_types');
                break;
            case 'delete_order_status':
                $ids = (array)$_GET['id'];
                $items = array_map(function ($x) {
                    return new Order_Status((int)$x);
                }, $ids);
                $items = array_filter($items, function ($x) {
                    return !$x->locked;
                });
                $items = array_values($items);
                StdSub::delete($items, $this->url . '&action=order_statuses');
                break;
            case 'delete_priceloader':
                $ids = (array)$_GET['id'];
                $items = array_map(function ($x) {
                    return new PriceLoader((int)$x);
                }, $ids);
                $items = array_filter($items, function ($x) {
                    return !$x->locked;
                });
                $items = array_values($items);
                StdSub::delete($items, $this->url . '&action=priceloaders');
                break;
            case 'delete_imageloader':
                $ids = (array)$_GET['id'];
                $items = array_map(function ($x) {
                    return new ImageLoader((int)$x);
                }, $ids);
                $items = array_filter($items, function ($x) {
                    return !$x->locked;
                });
                $items = array_values($items);
                StdSub::delete($items, $this->url . '&action=imageloaders');
                break;
            default:
                new Redirector(CMSViewSubDev::i()->url);
                break;
        }
    }


    protected function edit_cart_type()
    {
        $item = new Cart_Type((int)$this->id);
        $form = new EditCartTypeForm(['Item' => $item]);
        $this->view->{__FUNCTION__}($form->process());
    }


    protected function order_statuses()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['priority']) && is_array($_POST['priority'])) {
                Package::i()->setEntitiesPriority(
                    Order_Status::class,
                    (array)$_POST['priority']
                );
            }
        }
        $this->view->{$this->action}(['Set' => Order_Status::getSet()]);
    }


    protected function edit_order_status()
    {
        $item = new Order_Status((int)$this->id);
        $form = new EditOrderStatusForm(['Item' => $item]);
        $this->view->{__FUNCTION__}($form->process());
    }


    protected function edit_priceloader()
    {
        $item = new PriceLoader((int)$this->id);
        $form = new EditPriceLoaderForm(['Item' => $item]);
        $this->view->edit_priceloader($form->process());
    }


    protected function copy_priceloader()
    {
        $original = $item = new PriceLoader((int)$this->id);
        $item = Package::i()->copyItem($item);
        $form = new CopyPriceLoaderForm([
            'Item' => $item,
            'Original' => $original
        ]);
        $this->view->edit_priceloader($form->process());
    }


    protected function edit_imageloader()
    {
        $item = new ImageLoader((int)$this->id);
        $form = new EditImageLoaderForm(['Item' => $item]);
        $this->view->edit_imageloader($form->process());
    }


    protected function copy_imageloader()
    {
        $original = $item = new ImageLoader((int)$this->id);
        $item = Package::i()->copyItem($item);
        $form = new CopyImageLoaderForm([
            'Item' => $item,
            'Original' => $original
        ]);
        $this->view->edit_imageloader($form->process());
    }
}
