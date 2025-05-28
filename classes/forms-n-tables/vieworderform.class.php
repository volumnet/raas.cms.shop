<?php
/**
 * Форма просмотра заказа
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Field as RAASField;
use RAAS\FieldSet;
use RAAS\Form as RAASForm;
use RAAS\FormTab;
use RAAS\CMS\Feedback;
use RAAS\CMS\ViewFeedbackForm;

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
        $item = $params['Item'] ?? new Order();
        $params['Item'] ??= $item;
        parent::__construct($params);
        $this->caption = sprintf($this->view->_('ORDER_N'), (int)($item->id ?? 0));
    }


    protected function getParams(array $params = []): array
    {
        $arr = parent::getParams($params);
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


    protected function getChildren(Feedback $item): array
    {
        return $this->getChildrenWithStatuses($item);
    }


    protected function getChildrenWithStatuses(Feedback $item): array
    {
        $arr = [];
        $arr['common'] = new FormTab([
            'name' => 'common',
            'caption' => $this->view->_('ORDER_DETAILS'),
            'children' => $this->getDetails($item)
        ]);
        $arr['history'] = new FormTab([
            'name' => 'history',
            'caption' => $this->view->_('ORDER_HISTORY'),
            'meta' => [
                'Table' => new OrderHistoryTable([
                    'Item' => $item
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
                    'default' => $item ? $item->status_id : 0,
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

    /**
     * Получает список дочерних узлов
     * @param Feedback $item Заявка для получения
     * @return array <pre><code>array<FormTab|FieldSet|RAASField></code></pre>
     */
    protected function getDetails(Feedback $item): array
    {
        $fieldGroups = $item->parent->Form->fieldGroups;
        $result = [];
        $itemsFieldSet = new FieldSet([
            'name' => 'items',
            'template' => 'order_view.items.inc.php',
            'caption' => (count($fieldGroups) > 1) ? $this->view->_('GOODS') : '',
            'meta' => [
                'Table' => new OrderItemsTable([
                    'Item' => $item,
                    'items' => $item->items ?? [],
                ])
            ]
        ]);
        if (count($fieldGroups) > 1) {
            foreach ($fieldGroups as $fieldGroupURN => $fieldGroup) {
                $fieldSetURN = ($fieldGroupURN ? ('fieldset.' . $fieldGroupURN) : 'common');
                $fieldSetData = [
                    'name' => $fieldSetURN,
                    'caption' => $fieldGroup->name ?: $this->view->_('GENERAL'),
                    'children' => [],
                ];
                if (!$fieldGroupURN) {
                    $fieldSetData['children'] = $this->getPreStat($item);
                }
                $fieldSetData['children'] = array_merge(
                    $fieldSetData['children'],
                    $this->getDetailsFields($item, $fieldGroup)
                );
                $result[$fieldSetURN] = new FieldSet($fieldSetData);
            }
            $result['items'] = $itemsFieldSet;
            $result['stat'] = new FieldSet([
                'name' => 'stat',
                'caption' => $this->view->_('SERVICE'),
                'children' => $this->getStat($item),
            ]);
        } else {
            $result = array_merge(
                $this->getPreStat($item),
                $this->getDetailsFields($item),
                ['items' => $itemsFieldSet],
                $this->getStat($item),
            );
        }
        return $result;
    }


    protected function getPreStat(Feedback $item): array
    {
        $result = parent::getPreStat($item);
        if (Order_Status::getSet()) {
            $result['status_id'] = [
                'name' => 'status_id',
                'caption' => $this->view->_('ORDER_STATUS'),
                'template' => 'order_view.add_field.inc.php'
            ];
            $result['paid'] = [
                'name' => 'paid',
                'caption' => $this->view->_('PAYMENT_STATUS'),
                'template' => 'order_view.add_field.inc.php',
                'import' => 'is_null',
            ];
        }
        return $result;
    }


    protected function getStat(Feedback $item): array
    {
        $arr = parent::getStat($item);
        $arr['pid'] = [
            'name' => 'pid',
            'caption' => $this->view->_('CART_TYPE'),
            'template' => 'order_view.add_field.inc.php'
        ];
        if ($item && $item->paymentInterface && $item->paymentInterface->id) {
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
