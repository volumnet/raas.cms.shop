<?php
/**
 * Форма просмотра заказа
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Form as RAASForm;
use RAAS\FormTab;
use RAAS\Field as RAASField;
use RAAS\FieldSet;
use RAAS\CMS\ViewFeedbackForm;
use RAAS\Application;

/**
 * Форма просмотра заказа
 */
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
        $this->caption = sprintf($this->view->_('ORDER_N'), (int)($this->Item ? $this->Item->id : 0));
    }


    protected function getParams(array $params = []): array
    {
        $arr = parent::getParams();
        $arr['action'] = '#history';
        $arr['commit'] = function (RAASForm $Form) {
            $history = new Order_History();
            $history->uid = Application::i()->user ? Application::i()->user->id : 0;
            $history->order_id = (int)$Form->Item->id;
            $history->status_id = (int)$_POST['status_id'];
            if ((int)$_POST['paid']) {
                $Form->Item->paid = ((int)$_POST['paid'] + 1) / 2;
            }
            $history->paid = (int)$Form->Item->paid;
            $history->post_date = date('Y-m-d H:i:s');
            $history->description = trim($_POST['description']);
            $history->commit();

            $Form->Item->status_id = (int)$_POST['status_id'];
            $Form->Item->commit();
        };
        return $arr;
    }


    protected function getChildren(): array
    {
        return $this->getChildrenWithStatuses();
    }


    protected function getChildrenWithStatuses(): array
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
                    'default' => $this->Item ? $this->Item->status_id : 0,
                ],
                // 2021-01-04, AVS: сделал выпадающее меню вместо галочки, чтобы
                // не сбрасывался статус оплаты при сохранении, когда фоном
                // прошел онлайн-платеж
                'paid' => [
                    'type' => 'select',
                    'name' => 'paid',
                    'caption' => $this->view->_('PAYMENT_STATUS'),
                    'default' => '',
                    'style' => 'margin: 0',
                    'children' => [
                        ['value' => '', 'caption' => $this->view->_('DONT_CHANGE')],
                        ['value' => '1', 'caption' => $this->view->_('_YES')],
                        ['value' => '-1', 'caption' => $this->view->_('_NO')],
                    ],
                    'import' => function ($field) {
                        return '';
                    },
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


    protected function getDetails(): array
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
                'template' => 'order_view.add_field.inc.php',
                'import' => 'is_null',
            ];
        }
        $arr = array_merge($arr, $this->getDetailsFields());
        $arr['items'] = new FieldSet([
            'name' => 'items',
            'template' => 'order_view.items.inc.php',
            'meta' => [
                'Table' => new OrderItemsTable([
                    'Item' => $this->Item,
                    'items' => $this->meta['items'] ?? [],
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


    protected function getStat(): array
    {
        $arr = parent::getStat();
        if ($this->Item && $this->Item->paymentInterface && $this->Item->paymentInterface->id) {
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
