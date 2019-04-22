<?php
namespace RAAS\CMS\Shop;

use RAAS\Redirector as Redirector;
use RAAS\Attachment as Attachment;
use ArrayObject as ArrayObject;
use RAAS\Field as Field;
use RAAS\FieldSet as FieldSet;
use RAAS\FieldContainer as FieldContainer;
use RAAS\FormTab as FormTab;
use RAAS\CMS\Form as CMSForm;
use RAAS\OptGroup as OptGroup;
use RAAS\Option as Option;
use RAAS\StdSub as StdSub;

class Sub_Orders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;

    public function run()
    {
        switch ($this->action) {
            case 'view':
                $this->{$this->action}();
                break;
            case 'chvis':
            case 'delete':
            case 'vis':
            case 'invis':
                $ids = (array)$_GET['id'];
                if (in_array('all', $ids, true)) {
                    $pids = (array)$_GET['pid'];
                    $pids = array_filter($pids, 'trim');
                    $pids = array_map('intval', $pids);
                    if ($pids) {
                        $items = Order::getSet(array('where' => "pid IN (" . implode(", ", $pids) . ")", 'orderBy' => "id"));
                    }
                } else {
                    $items = array_map(function ($x) {
                        return new Order((int)$x);
                    }, $ids);
                }
                $items = array_values($items);
                $f = $this->action;
                StdSub::$f($items, $this->url);
                break;
            case 'edit':
                $this->edit();
                break;
            case 'move_order_goods':
                $this->moveOrderGoods();
                break;
            default:
                $this->orders();
                break;
        }
    }


    protected function orders()
    {
        $IN = $this->model->orders();
        $Set = $IN['Set'];
        $Pages = $IN['Pages'];
        $Item = $IN['Parent'];
        $Cart_Types = Cart_Type::getSet();

        $OUT['Item'] = $Item;
        $OUT['columns'] = $IN['columns'];
        $OUT['Set'] = $Set;
        $OUT['Pages'] = $Pages;
        $OUT['Cart_Types'] = $Cart_Types;
        $OUT['search_string'] = isset($_GET['search_string']) ? (string)$_GET['search_string'] : '';
        $OUT['statuses'] = Order_Status::getSet();
        $this->view->orders($OUT);
    }


    protected function edit()
    {
        $Item =  new Order($this->id);
        $Parent = $Item->id ? $Item->parent : new Cart_Type((int)$this->nav['pid']);
        if (!Module::i()->registryGet('allow_order_edit') || !$Parent->id) {
            new Redirector($this->url);
        }
        if (!$Item->id) {
            $Item->pid = $Parent->id;
        }
        $Form = new EditOrderForm(array('Item' => $Item, 'Parent' => $Parent));
        $this->view->edit($Form->process());
    }


    /**
     * Перенос товаров в новый заказ
     */
    protected function moveOrderGoods()
    {
        $order =  new Order($_GET['order_id']);
        $goods = [];
        $idsMetas = [];
        if ($_GET['id'] == 'all') {
            $goods = $order->items;
        } else {
            $idsMetas = array_map(function ($x) {
                $y = explode('_', $x, 2);
                $result = ['id' => (int)$y[0], 'meta' => trim($y[1])];
                return $result;
            }, (array)$_GET['id']);
            foreach ($order->items as $cartItem) {
                foreach ($idsMetas as $idMeta) {
                    if (($cartItem->id == $idMeta['id']) && ($cartItem->meta == $idMeta['meta'])) {
                        $goods[] = $cartItem;
                    }
                }
            }
        }
        if (!Module::i()->registryGet('allow_order_edit') || !$order->id || !$goods) {
            new Redirector($this->url);
        }
        $form = new MoveOrderGoodsForm(array(
            'order' => $order,
            'goods' => $goods,
            'new' => (bool)$_GET['new']
        ));
        $this->view->moveOrderGoods(array_merge($form->process(), array('Item' => $order)));
    }


    protected function view()
    {
        $Item = new Order($this->id);
        $Cart_Types = Cart_Type::getSet();
        if (!$Item->id) {
            new Redirector(\SOME\HTTP::queryString('id=&action='));
        }
        $Item->vis = (int)$this->application->user->id;
        $Item->commit();
        $OUT['Item'] = $Item;
        $OUT['items'] = $Item->items;
        $OUT['Cart_Types'] = $Cart_Types;
        $Form = new ViewOrderForm(array('Item' => $Item, 'meta' => array('items' => $OUT['items'])));
        $this->view->view(array_merge($Form->process(), $OUT));
    }
}
