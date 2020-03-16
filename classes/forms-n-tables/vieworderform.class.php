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


    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->caption = sprintf($this->view->_('ORDER_N'), (int)$this->Item->id);
    }


    protected function getParams()
    {
        $arr = parent::getParams();
        $arr['action'] = '#history';
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
        $arr = [];
        $arr['common'] = new FormTab([
            'name' => 'common',
            'caption' => $this->view->_('ORDER_DETAILS'),
            'children' => $this->getDetails()
        ]);
        $arr['history'] = new FormTab([
            'name' => 'history',
            'caption' => $this->view->_('ORDER_HISTORY'),
            'meta' => [
                'Table' => new OrderHistoryTable([
                    'Item' => $this->Item
                ])
            ],
            'template' => 'order_view.history.inc.php',
            'children' => [
                'status_id' => [
                    'type' => 'select',
                    'class' => 'span2',
                    'style' => 'margin: 0',
                    'name' => 'status_id',
                    'caption' => $this->view->_('ORDER_STATUS'),
                    'placeholder' => $this->view->_('ORDER_STATUS_NEW'),
                    'children' => ['Set' => Order_Status::getSet()],
                    'default' => $this->Item->status_id,
                ],
                'paid' => [
                    'type' => 'checkbox',
                    'name' => 'paid',
                    'caption' => $this->view->_('PAYMENT_STATUS'),
                    'default' => $this->Item->paid,
                ],
                'description' => [
                    'name' => 'description',
                    'caption' => $this->view->_('COMMENT'),
                    'required' => true,
                    'style' => 'margin: 0',
                ],
            ]
        ]);
        return $arr;
    }


    protected function getDetails()
    {
        $arr = [];
        $arr['post_date'] = $this->getFeedbackField([
            'name' => 'post_date',
            'caption' => $this->view->_('POST_DATE')
        ]);
        if (Order_Status::getSet()) {
            $arr['status_id'] = [
                'name' => 'status_id',
                'caption' => $this->view->_('ORDER_STATUS'),
                'template' => 'order_view.add_field.inc.php'
            ];
            $arr['paid'] = [
                'name' => 'paid',
                'caption' => $this->view->_('PAYMENT_STATUS'),
                'template' => 'order_view.add_field.inc.php'
            ];
        }
        $arr = array_merge($arr, $this->getDetailsFields());
        $arr['items'] = new FieldSet([
            'name' => 'items',
            'template' => 'order_view.items.inc.php',
            'meta' => [
                'Table' => new OrderItemsTable([
                    'Item' => $this->Item,
                    'items' => $this->meta['items']
                ])
            ]
        ]);
        $arr['pid'] = [
            'name' => 'pid',
            'caption' => $this->view->_('CART_TYPE'),
            'template' => 'order_view.add_field.inc.php'
        ];
        $arr = array_merge($arr, $this->getStat());
        return $arr;
    }


    protected function getStat()
    {
        $arr = parent::getStat();
        if ($this->Item->paymentInterface->id) {
            $arr['payment_interface_id'] = [
                'name' => 'payment_interface_id',
                'caption' => $this->view->_('PAID_VIA'),
                'template' => 'order_view.add_field.inc.php',
            ];
            $arr['payment_id'] = [
                'name' => 'payment_id',
                'caption' => $this->view->_('PAYMENT_ID'),
                'template' => 'order_view.add_field.inc.php',
            ];
        }
        return $arr;
    }
}
