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

class EditOrderForm extends Form
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
        $view = $this->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;
        $Parent = isset($params['Parent']) ? $params['Parent'] : null;

        $defaultParams = array(
            'caption' => $Item->id ? $view->_('EDIT_ORDER') . ' #' . (int)$Item->id : $view->_('NEW_ORDER'),
            'parentUrl' => Sub_Orders::i()->url . '&action=orders&id=' . (int)$Parent->id,
            'children' => array(),
            'export' => function ($Form) {
                $Item = $Form->Item;
                $Form->exportDefault();
                $Item->vis = (int)Application::i()->user->id;
                if ($Item->id) {
                    $dataChanged = array();
                    foreach ($Item->fields as $field) {
                        $vals = $field->getValues();
                        if ($vals != $_POST[$field->urn]) {
                            $dataChanged[$field->name] = print_r($vals, true) . ' => ' . print_r($_POST[$field->urn], true);
                        }
                    }

                    $itemsOld = $itemsNew = array();
                    foreach ($Item->items as $row) {
                        $itemsOld[] = '#' . $row->id . ' ' . $row->name
                                    . ($row->meta ? ' (' . $row->meta . ')' : '')
                                    . ': ' . (float)$row->realprice . ' x ' . (int)$row->amount
                                    . ' = ' . (float)($row->realprice * $row->amount);
                    }
                    foreach ($Item->meta_items as $arr) {
                        $itemsNew[] = '#' . $arr['material_id'] . ' ' . $arr['name']
                                    . ($arr['meta'] ? ' (' . $arr['meta'] . ')' : '')
                                    . ': ' . (float)$arr['realprice'] . ' x ' . (int)$arr['amount']
                                    . ' = ' . (float)($arr['realprice'] * $arr['amount']);
                    }
                    if ($deletedItems = array_diff($itemsOld, $itemsNew)) {
                        $dataChanged['Удаленные товары'] = implode("\n", $deletedItems);
                    }
                    if ($addedItems = array_diff($itemsNew, $itemsOld)) {
                        $dataChanged['Новые товары'] = implode("\n", $addedItems);
                    }
                    if ($dataChanged) {
                        $newComment = array();
                        foreach ($dataChanged as $key => $val) {
                            $newComment[] = $key . ': ' . $val;
                        }
                        $Item->newComment = implode("\n", $newComment);
                    }
                }
            },
            'oncommit' => function ($Form) {
                $Form->oncommitDefault();
                $Item = $Form->Item;
                if ($Item->newComment) {
                    $comment = new Order_History(array(
                        'uid' => (int)Application::i()->user->id,
                        'order_id' => (int)$Item->id,
                        'post_date' => date('Y-m-d H:i:s'),
                        'status_id' => $Item->status_id,
                        'paid' => $Item->paid,
                        'description' => 'Заказ изменен: ' . "\n" . $Item->newComment
                    ));
                    $comment->commit();
                }
            }
        );
        foreach ($Item->fields as $field) {
            $defaultParams['children'][$field->urn] = $field->Field;
        }
        $defaultParams['children']['goods'] = new FieldSet(array(
            'children' => array(
                'material' => array(
                    'type' => 'material',
                    'name' => 'material',
                    'multiple' => true,
                    'caption' => $view->_('NAME')
                ),
                'meta' => array(
                    'type' => 'string',
                    'name' => 'meta',
                    'multiple' => 'true',
                    'caption' => $view->_('ADDITIONAL_INFO')
                ),
                'realprice' => array(
                    'type' => 'number',
                    'name' => 'realprice',
                    'multiple' => true,
                    'caption' => $view->_('PRICE'),
                    'step' => 0.01,
                    'min' => 0
                ),
                'amount' => array(
                    'type' => 'number',
                    'name' => 'amount',
                    'multiple' => true,
                    'caption' => $view->_('AMOUNT')
                ),
            ),
            'template' => 'order_edit.items.inc.php',
            'meta' => array(
                'Cart_Type' => $Parent,
            ),
            'import' => function ($FieldSet) {
                $DATA = array();
                if ($FieldSet->Form->Item->items) {
                    foreach ((array)$FieldSet->Form->Item->items as $row) {
                        $DATA['material'][] = (int)$row->id;
                        $DATA['meta'][] = trim($row->meta);
                        $DATA['realprice'][] = (float)$row->realprice;
                        $DATA['amount'][] = (int)$row->amount;
                    }
                }
                return $DATA;
            },
            'export' => function ($FieldSet) {
                $Item = $FieldSet->Form->Item;
                $items = array();
                foreach ($_POST['material'] as $key => $val) {
                    $row = new Material($val);
                    if ($row->id && ($_POST['amount'][$key] > 0)) {
                        $items[] = array(
                            'material_id' => (int)$val,
                            'name' => $row->name,
                            'meta' => $_POST['meta'][$key],
                            'realprice' => (float)$_POST['realprice'][$key],
                            'amount' => (int)$_POST['amount'][$key]
                        );
                    }
                }
                if ($items) {
                    $Item->meta_items = $items;
                }
            }
        ));
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
