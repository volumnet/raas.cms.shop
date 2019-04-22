<?php
namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\Option;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Form as CMSForm;
use RAAS\FieldSet;
use RAAS\CMS\Snippet_Folder;
use RAAS\CMS\Snippet;
use RAAS\Form;
use RAAS\CMS\Material;
use RAAS\Application;
use RAAS\Redirector;

class MoveOrderGoodsForm extends Form
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
        $view = $this->view;
        $t = Module::i();
        $order = isset($params['order']) ? $params['order'] : null;
        $goods = (array)$params['goods'];

        $defaultParams = [
            'caption' => $view->_(
                $params['new'] ?
                'MOVE_TO_NEW_ORDER' :
                'MOVE_TO_ANOTHER_ORDER'
            ),
            'template' => 'moveordergoodsform.tmp.php',
            'parentUrl' => $this->url . '&action=edit&id=' . $order->id,
            'children' => [
                'goods' => new FieldSet([
                    'children' => [
                        'material' => [
                            'type' => 'material',
                            'name' => 'material',
                            'multiple' => true,
                            'caption' => $view->_('NAME')
                        ],
                        'meta' => [
                            'type' => 'string',
                            'name' => 'meta',
                            'multiple' => 'true',
                            'caption' => $view->_('ADDITIONAL_INFO')
                        ],
                        'realprice' => [
                            'type' => 'number',
                            'name' => 'realprice',
                            'multiple' => true,
                            'caption' => $view->_('PRICE'),
                            'step' => 0.01,
                            'min' => 0
                        ],
                        'amount' => [
                            'type' => 'number',
                            'name' => 'amount',
                            'multiple' => true,
                            'caption' => $view->_('AMOUNT')
                        ],
                    ],
                    'template' => 'order_view.items.inc.php',
                    'meta' => [
                        'Cart_Type' => $order->parent,
                        'Table' => new OrderItemsTable([
                            'Item' => $order,
                            'items' => $goods
                        ]),
                    ],
                ]),
            ],
            'check' => function ($Field) use ($t, $params, $order) {
                if (!$params['new']) {
                    if (!isset($_POST['new_order_id']) || !$_POST['new_order_id']) {
                        return [
                            'name' => 'MISSED',
                            'value' => 'new_order_id',
                            'description' => sprintf($t->view->_('ERR_CUSTOM_FIELD_REQUIRED'), $t->view->_('NEW_ORDER_ID'))
                        ];
                    } else {
                        $newOrder = new Order($_POST['new_order_id']);
                        if ($newOrder->id != $_POST['new_order_id']) {
                            return [
                                'name' => 'INVALID',
                                'value' => 'new_order_id',
                                'description' => sprintf($t->view->_('ERR_ORDER_DOESNOT_EXIST'), $_POST['new_order_id'])
                            ];
                        } elseif ($newOrder->id == $order->id) {
                            return [
                                'name' => 'INVALID',
                                'value' => 'new_order_id',
                                'description' => $t->view->_('ERR_NEW_ORDER_MUST_DIFFER_FROM_OLD')
                            ];
                        } elseif ($newOrder->pid != $order->pid) {
                            return [
                                'name' => 'INVALID',
                                'value' => 'new_order_id',
                                'description' => sprintf($t->view->_('ERR_ORDER_RELATES_TO_ANOTHER_CART'), $_POST['new_order_id'], $order->id)
                            ];
                        }
                    }
                }
            },
            'commit' => function ($Form) use ($params, $order, $view, $goods) {
                if ($params['new']) {
                    $newOrder = clone $order;
                    $newOrder->post_date = date('Y-m-d H:i:s');
                    if (!$_POST['copy_status']) {
                        $newOrder->status_id = 0;
                    }
                    if (!$_POST['copy_paid']) {
                        $newOrder->paid = 0;
                    }
                    $newOrder->commit();
                    $fieldsIds = array_map(function ($x) {
                        return (int)$x->id;
                    }, $newOrder->fields);
                    if ($fieldsIds) {
                        $sqlQuery = "INSERT INTO cms_data (pid, fid, fii, value, inherited)
                                     SELECT " . (int)$newOrder->id . " AS pid, fid, fii, value, inherited
                                       FROM cms_data
                                      WHERE pid = " . (int)$order->id
                                  . "   AND fid IN (" . implode(", ", $fieldsIds) . ")";
                        Order::_SQL()->query($sqlQuery);
                    }
                } else {
                    $newOrder = new Order($_POST['new_order_id']);
                }

                if ($_POST['add_comment_to_old_order']) {
                    $comment = new Order_History(array(
                        'uid' => (int)Application::i()->user->id,
                        'order_id' => (int)$order->id,
                        'post_date' => date('Y-m-d H:i:s'),
                        'status_id' => $order->status_id,
                        'paid' => $order->paid,
                        'description' => sprintf(
                            $view->_('COMMENT_TO_OLD_ORDER_TEMPLATE'),
                            (int)$newOrder->id
                        ) . ": \n" . implode("\n", Order::getItemsTextArr($goods))
                    ));
                    $comment->commit();
                }
                if ($_POST['add_comment_to_new_order']) {
                    $comment = new Order_History(array(
                        'uid' => (int)Application::i()->user->id,
                        'order_id' => (int)$newOrder->id,
                        'post_date' => date('Y-m-d H:i:s'),
                        'status_id' => $newOrder->status_id,
                        'paid' => $newOrder->paid,
                        'description' => sprintf(
                            $view->_('COMMENT_TO_NEW_ORDER_TEMPLATE'),
                            (int)$order->id
                        ) . ": \n" . implode("\n", Order::getItemsTextArr($goods))
                    ));
                    $comment->commit();
                }

                foreach ($goods as $itemToMove) {
                    $itemFound = false;
                    foreach ($newOrder->items as $newOrderItem) {
                        if (($newOrderItem->id == $itemToMove->id) &&
                            ($newOrderItem->meta == $itemToMove->meta)
                        ) {
                            // Уже есть такой в заказе
                            $sqlQuery = "UPDATE " . Order::_dbprefix() . "cms_shop_orders_goods
                                            SET realprice = GREATEST(realprice, ?),
                                                amount = amount + ?
                                          WHERE order_id = ?
                                            AND material_id = ?
                                            AND meta = ?";
                            Order::_SQL()->query([
                                $sqlQuery,
                                [
                                    (float)$itemToMove->realprice,
                                    (int)$itemToMove->amount,
                                    (int)$newOrder->id,
                                    (int)$itemToMove->id,
                                    trim($itemToMove->meta)
                                ]
                            ]);
                            // Удалим из старого заказа
                            $sqlQuery = "DELETE FROM " . Order::_dbprefix() . "cms_shop_orders_goods
                                          WHERE order_id = ?
                                            AND material_id = ?
                                            AND meta = ?";
                            Order::_SQL()->query([
                                $sqlQuery,
                                [
                                    (int)$order->id,
                                    (int)$itemToMove->id,
                                    trim($itemToMove->meta)
                                ]
                            ]);
                            $itemFound = true;
                            break;
                        }
                    }
                    if (!$itemFound) {
                        // Товар в новом заказе не найден, просто перенесем
                        $sqlQuery = "UPDATE " . Order::_dbprefix() . "cms_shop_orders_goods
                                        SET order_id = ?
                                      WHERE order_id = ?
                                        AND material_id = ?
                                        AND meta = ?";
                        Order::_SQL()->query([
                            $sqlQuery,
                            [
                                (int)$newOrder->id,
                                (int)$order->id,
                                (int)$itemToMove->id,
                                trim($itemToMove->meta)
                            ]
                        ]);
                    }
                }
                new Redirector(
                    Sub_Orders::i()->url . '&action=edit&id=' . (int)(
                        in_array(
                            $_POST['@oncommit'],
                            [Form::ONCOMMIT_EDIT, Form::ONCOMMIT_NEW]
                        ) ?
                        $newOrder->id :
                        $order->id
                    )
                );
            },
        ];
        if (!$params['new']) {
            $defaultParams['children']['new_order_id'] = [
                'name' => 'new_order_id',
                'type' => 'number',
                'caption' => $view->_('NEW_ORDER_ID'),
            ];
        }
        $defaultParams['children']['add_comment_to_old_order'] = [
            'name' => 'add_comment_to_old_order',
            'type' => 'checkbox',
            'caption' => $view->_('ADD_MOVING_COMMENT_TO_OLD_ORDER'),
            'default' => 1,
        ];
        $defaultParams['children']['add_comment_to_new_order'] = [
            'name' => 'add_comment_to_new_order',
            'type' => 'checkbox',
            'caption' => $view->_('ADD_MOVING_COMMENT_TO_NEW_ORDER'),
            'default' => 1,
        ];
        if ($params['new']) {
            $defaultParams['children']['copy_status'] = [
                'name' => 'copy_status',
                'type' => 'checkbox',
                'caption' => $view->_('COPY_ORDER_STATUS'),
            ];
            $defaultParams['children']['copy_paid'] = [
                'name' => 'copy_paid',
                'type' => 'checkbox',
                'caption' => $view->_('COPY_ORDER_PAID_STATUS'),
            ];
        }

        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }


    public function process()
    {
        $OUT = parent::process();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->DATA['@oncommit'] = $_POST['@oncommit'];
        } else {
            $this->DATA['@oncommit'] = static::ONCOMMIT_EDIT;
        }
        return $OUT;
    }
}
