<?php
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Attachment;
use RAAS\Field as RAASField;
use RAAS\FieldSet;
use RAAS\Form;
use RAAS\Option;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Field as CMSField;
use RAAS\CMS\Form as CMSForm;
use RAAS\CMS\Snippet_Folder;
use RAAS\CMS\Snippet;
use RAAS\CMS\Material;

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


    public function __construct(array $params = [])
    {
        $view = $this->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;
        $Parent = isset($params['Parent']) ? $params['Parent'] : null;

        $defaultParams = [
            'caption' => $Item->id ? $view->_('EDIT_ORDER') . ' #' . (int)$Item->id : $view->_('NEW_ORDER'),
            'parentUrl' => Sub_Orders::i()->url . '&id=' . (int)$Parent->id,
            'meta' => [
                'allContextMenu' => $view->getAllOrderGoodsContextMenu($Item),
            ],
            'children' => [],
            'export' => function ($Form) use ($view) {
                $Item = $Form->Item;
                $Form->exportDefault();
                $Item->vis = (int)Application::i()->user->id;
                if ($Item->id) {
                    $dataChanged = [];
                    foreach ($Item->fields as $field) {
                        if (in_array($field->datatype, ['file', 'image'])) {
                            $newVals = $this->formatFieldValue($field, $_FILES[$field->urn]['name'] ?? null);
                            if ($newVals) {
                                $dataChanged[$field->name] = $newVals;
                            }
                        } else {
                            $oldVals = $field->getValues();
                            $oldVals = $this->formatFieldValue($field, $oldVals);
                            $newVals = $_POST[$field->urn] ?? null;
                            $newVals = $this->formatFieldValue($field, $newVals);
                            if ($oldVals != $newVals) {
                                $dataChanged[$field->name] = $oldVals . ' => ' . $newVals;
                            }
                        }
                    }

                    $itemsOld = Order::getItemsTextArr($Item->items);
                    $itemsNew = Order::getItemsTextArr($Item->meta_items);
                    if ($deletedItems = array_diff($itemsOld, $itemsNew)) {
                        $dataChanged[$view->_('DELETED_GOODS')] = implode("\n", $deletedItems);
                    }
                    if ($addedItems = array_diff($itemsNew, $itemsOld)) {
                        $dataChanged[$view->_('NEW_GOODS')] = implode("\n", $addedItems);
                    }
                    if ($dataChanged) {
                        $newComment = [];
                        foreach ($dataChanged as $key => $val) {
                            $newComment[] = $key . ': ' . $val;
                        }
                        $Item->newComment = implode("\n", $newComment);
                    }
                }
            },
            'oncommit' => function ($Form) use ($view) {
                $Form->oncommitDefault();
                $Item = $Form->Item;
                if ($Item->newComment) {
                    $comment = new Order_History([
                        'uid' => (int)Application::i()->user->id,
                        'order_id' => (int)$Item->id,
                        'post_date' => date('Y-m-d H:i:s'),
                        'status_id' => $Item->status_id,
                        'paid' => $Item->paid,
                        'description' => $view->_('ORDER_CHANGED') . ": \n" . $Item->newComment
                    ]);
                    $comment->commit();
                }
            }
        ];
        foreach ($Item->fields as $field) {
            $defaultParams['children'][$field->urn] = $field->Field;
        }
        $defaultParams['children']['goods'] = new FieldSet([
            'children' => [
                'material' => [
                    'type' => 'material',
                    'name' => 'material',
                    'multiple' => true,
                    'caption' => $view->_('MATERIAL'),
                ],
                'material_name' => [
                    'name' => 'material_name',
                    'multiple' => true,
                    'caption' => $view->_('NAME'),
                ],
                'meta' => [
                    'type' => 'string',
                    'name' => 'meta',
                    'multiple' => 'true',
                    'caption' => $view->_('ADDITIONAL_INFO'),
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
                    'caption' => $view->_('AMOUNT'),
                ],
            ],
            'template' => 'order_edit.items.inc.php',
            'meta' => [
                'Cart_Type' => $Parent,
            ],
            'import' => function ($FieldSet) {
                $DATA = [];
                if ($FieldSet->Form->Item->items) {
                    foreach ((array)$FieldSet->Form->Item->items as $row) {
                        $DATA['material'][] = (int)$row->id;
                        $DATA['material_name'][] = $row->name;
                        $DATA['meta'][] = trim($row->meta);
                        $DATA['realprice'][] = (float)$row->realprice;
                        $DATA['amount'][] = (int)$row->amount;
                    }
                }
                return $DATA;
            },
            'export' => function ($FieldSet) {
                $Item = $FieldSet->Form->Item;
                $items = [];
                foreach ($_POST['material'] as $key => $val) {
                    $row = new Material($val);
                    if (($_POST['amount'][$key] > 0)) {
                        $items[] = [
                            'material_id' => (int)$val,
                            'name' => trim($_POST['material_name'][$key]) ?: $row->name,
                            'meta' => $_POST['meta'][$key],
                            'realprice' => (float)$_POST['realprice'][$key],
                            'amount' => (int)$_POST['amount'][$key]
                        ];
                    }
                }
                if ($items) {
                    $Item->meta_items = $items;
                }
            }
        ]);
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }


    /**
     * Форматирует значение поля
     * @param CMSField $field Поле
     * @param mixed $value Значение
     * @return mixed;
     */
    public function formatFieldValue(CMSField $field, $value)
    {
        if (is_array($value)) {
            $result = array_map(function ($x) use ($field) {
                return $this->formatFieldValue($field, $x);
            }, $value);
            $result = array_values(array_filter($result));
            if (count($result) > 1) {
                $result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif ($result) {
                $result = $result[0];
            } else {
                $result = '';
            }
            return $result;
        }
        if ($value instanceof Material) {
            if ($value->id) {
                return $value->name . ' (#' . (int)$value->id . ')';
            }
        } elseif ($value && is_scalar($value)) {
            if (in_array($field->datatype, ['file', 'image'])) {
                return 'Загружен: ' . $value;
            } elseif ($field->datatype == 'material') {
                $material = new Material($value);
                if ($material->id) {
                    return $material->name . ' (#' . (int)$material->id . ')';
                }
            } else {
                return $field->doRich($value);
            }
        }
        return '';
    }
}
