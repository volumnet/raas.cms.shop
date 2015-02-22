<?php
namespace RAAS\CMS\Shop;
use \RAAS\Form as RAASForm;
use \RAAS\FormTab;
use \RAAS\Field as RAASField;
use \RAAS\FieldSet;
use \RAAS\CMS\ViewFeedbackForm;
use \RAAS\Application;

class ViewOrderForm extends ViewFeedbackForm
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Orders::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }


    protected function getParams()
    {
        $arr = parent::getParams();
        $arr['caption'] = $this->view->_('ORDERS');
        $arr['commit'] = function(RAASForm $Form) {
            $history = new Order_History();
            $history->uid = Application::i()->user->id;
            $history->order_id = (int)$Form->Item->id;
            $history->status_id = (int)$_POST['status_id'];
            $history->paid = (int)$_POST['paid'];
            $history->post_date = date('Y-m-d H:i:s');
            $history->description = trim($_POST['description']);
            $history->commit();

            $Form->Item->status_id = (int)$_POST['status_id'];
            $Form->Item->paid = (int)$_POST['paid'];
            $Form->Item->commit();
        };
        return $arr;
    }


    protected function getChildren()
    {
        if (Order_Status::getSet()) {
            return $this->getChildrenWithStatuses();
        } else {
            return $this->getDetails();
        }
    }


    protected function getChildrenWithStatuses()
    {
        $arr = array();
        $arr['common'] = new FormTab(array(
            'name' => 'common',
            'caption' => $this->view->_('ORDER_DETAILS'),
            'children' => $this->getDetails()
        ));
        $arr['history'] = new FormTab(array(
            'name' => 'history',
            'caption' => $this->view->_('ORDER_HISTORY'),
            'meta' => array('Table' => new OrderHistoryTable(array('Item' => $this->Item))),
            'template' => 'order_view.history.inc.php',
            'children' => array(
                'status_id' => array(
                    'type' => 'select', 
                    'class' => 'span2',
                    'style' => 'margin: 0',
                    'name' => 'status_id', 
                    'caption' => $this->view->_('ORDER_STATUS'),
                    'placeholder' => $this->view->_('ORDER_STATUS_NEW'),
                    'children' => array('Set' => Order_Status::getSet()),
                    'default' => $this->Item->status_id,
                ),
                'paid' => array(
                    'type' => 'select', 
                    'class' => 'span2',
                    'style' => 'margin: 0',
                    'name' => 'paid', 
                    'caption' => $this->view->_('PAYMENT_STATUS'),
                    'children' => array(
                        array('value' => Order::PAYMENT_NOT_PAID, 'caption' => $this->view->_('PAYMENT_NOT_PAID')),
                        array('value' => Order::PAYMENT_PAID_NOT_CONFIRMED, 'caption' => $this->view->_('PAYMENT_PAID_NOT_CONFIRMED')),
                        array('value' => Order::PAYMENT_PAID_CONFIRMED, 'caption' => $this->view->_('PAYMENT_PAID_CONFIRMED')),
                    ),
                    'default' => $this->Item->paid,
                ),
                'description' => array('name' => 'description', 'caption' => $this->view->_('COMMENT'), 'required' => true, 'style' => 'margin: 0',),
            )
        ));
        return $arr;
    }


    protected function getDetails()
    {
        $arr = array();
        $arr['post_date'] = $this->getFeedbackField(array('name' => 'post_date', 'caption' => $this->view->_('POST_DATE')));
        if (Order_Status::getSet()) {
            $arr['status_id'] = array('name' => 'status_id', 'caption' => $this->view->_('ORDER_STATUS'), 'template' => 'order_view.add_field.inc.php');
            $arr['paid'] = array('name' => 'paid', 'caption' => $this->view->_('PAYMENT_STATUS'), 'template' => 'order_view.add_field.inc.php');
        }
        $arr = array_merge($arr, $this->getDetailsFields());
        $arr['items'] = new FieldSet(array(
            'name' => 'items', 
            'template' => 'order_view.items.inc.php', 
            'meta' => array('Table' => new OrderItemsTable(array('Item' => $this->Item)))
        ));
        $arr['pid'] = array('name' => 'pid', 'caption' => $this->view->_('CART_TYPE'), 'template' => 'order_view.add_field.inc.php');
        $arr = array_merge($arr, $this->getStat());
        return $arr;
    }


}