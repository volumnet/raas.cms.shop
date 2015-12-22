<?php
namespace RAAS\CMS\Shop;
use \RAAS\Redirector as Redirector;
use \RAAS\Attachment as Attachment;
use \ArrayObject as ArrayObject;
use \RAAS\Field as Field;
use \RAAS\FieldSet as FieldSet;
use \RAAS\FieldContainer as FieldContainer;
use \RAAS\FormTab as FormTab;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\OptGroup as OptGroup;
use \RAAS\Option as Option;
use \RAAS\StdSub as StdSub;

class Sub_Orders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        switch ($this->action) {
            case 'view':
                $this->{$this->action}();
                break;
            case 'chvis': case 'delete': case 'vis': case 'invis':
                $ids = (array)$_GET['id'];
                if (in_array('all', $ids, true)) {
                    $pids = (array)$_GET['pid'];
                    $pids = array_filter($pids, 'trim');
                    $pids = array_map('intval', $pids);
                    if ($pids) {
                        $items = Order::getSet(array('where' => "pid IN (" . implode(", ", $pids) . ")", 'orderBy' => "id"));
                    }
                } else {
                    $items = array_map(function($x) { return new Order((int)$x); }, $ids);
                }
                $items = array_values($items);
                $f = $this->action;
                StdSub::$f($items, $this->url);
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
        $this->view->orders($OUT);
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
        $OUT['items'] = Module::i()->getOrderItems($Item);
        $OUT['Cart_Types'] = $Cart_Types;
        $Form = new ViewOrderForm(array('Item' => $Item, 'meta' => array('items' => $OUT['items'])));
        $this->view->view(array_merge($Form->process(), $OUT));
    }
}