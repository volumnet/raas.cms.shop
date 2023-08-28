<?php
/**
 * Форма редактирования типа корзины
 */
namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\FieldSet;
use RAAS\Form as RAASForm;
use RAAS\Option;
use RAAS\CMS\Form as CMSForm;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Snippet;
use RAAS\CMS\Snippet_Folder;

class EditCartTypeForm extends RAASForm
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Dev::i();
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
        $CONTENT = [];
        $wf = function (Material_Type $x) use (&$wf) {
            static $level = 0;
            $temp = [];
            foreach ($x->children as $row) {
                $row->level = $level;
                $temp[] = $row;
                $level++;
                $temp = array_merge($temp, $wf($row));
                $level--;
            }
            return $temp;
        };
        $mt = new Material_Type();
        $CONTENT['material_types'] = $wf($mt);
        $CONTENT['forms'] = [
            'Set' => array_merge(
                [
                    new CMSForm([
                        'id' => '',
                        'name' => $view->_('_NONE')
                    ])
                ],
                CMSForm::getSet()
            )
        ];
        $CONTENT['fields'] = [
            '0' => new Material_Field([
                'id' => 0,
                'name' => $view->_('_NONE')
            ])
        ];
        foreach ($CONTENT['material_types'] as $row) {
            $CONTENT['fields'][$row->id][] = new Material_Field([
                'id' => 0,
                'name' => $view->_('_NONE')
            ]);
            if ($row->id) {
                foreach ($row->fields as $row2) {
                    if (!$row2->multiple &&
                        !in_array($row2->mtype, ['file', 'image'])
                    ) {
                        $CONTENT['fields'][$row->id][] = $row2;
                    }
                }
            }
        }
        $defaultParams = [
            'caption' => $Item->id ? $Item->name : $view->_('EDIT_CART_TYPE'),
            'parentUrl' => Sub_Dev::i()->url . '&action=cart_types',
            'meta' => ['CONTENT' => $CONTENT],
            'children' => [
                [
                    'name' => 'name',
                    'caption' => $view->_('NAME'),
                    'required' => 'required'
                ],
                [
                    'name' => 'urn',
                    'caption' => $view->_('URN')
                ],
                [
                    'type' => 'select',
                    'name' => 'form_id',
                    'caption' => $view->_('USE_FORM_FIELDS'),
                    'children' => $CONTENT['forms']
                ],
                [
                    'type' => 'textarea',
                    'name' => 'weight_callback',
                    'caption' => $view->_('WEIGHT_CALLBACK'),
                    'data-hint' => $view->_('WEIGHT_CALLBACK_HINT'),
                ],
                [
                    'type' => 'textarea',
                    'name' => 'sizes_callback',
                    'caption' => $view->_('SIZES_CALLBACK'),
                    'data-hint' => $view->_('SIZES_CALLBACK_HINT'),
                ],
                [
                    'type' => 'select',
                    'name' => 'amount_type',
                    'required' => true,
                    'caption' => $view->_('CHECK_AMOUNT_TYPE'),
                    'children' => [
                        [
                            'value' => -1,
                            'caption' => $view->_('FAVORITES_MODE')
                        ],
                        [
                            'value' => 0,
                            'caption' => $view->_('NORMAL_CHECK_AMOUNT_MODE'),
                        ],
                        [
                            'value' => 1,
                            'caption' => $view->_('STRICT_CHECK_AMOUNT_MODE'),
                        ],
                    ],
                    'import' => function ($field) {
                        $item = $field->Form->Item;
                        if ($item->no_amount) {
                            return -1;
                        } elseif ($item->check_amount) {
                            return 1;
                        }
                        return 0;
                    },
                    'export' => function ($field) {
                        $item = $field->Form->Item;
                        $val = (int)($_POST[$field->name] ?? 0);
                        $item->no_amount = (int)($val == -1);
                        $item->check_amount = (int)($val == 1);
                    },
                ],
                new FieldSet([
                    'template' => 'dev_edit_cart_type.mtypes.php',
                    'name' => 'mtypes',
                    'caption' => $view->_('MATERIAL_TYPES'),
                    'import' => function ($fieldSet) {
                        $DATA = [];
                        if ($fieldSet->Form->Item->material_types) {
                            foreach ((array)$fieldSet->Form->Item->material_types as $row) {
                                $DATA['price_id'][$row->id] = (int)$row->price_id;
                                $DATA['price_callback'][$row->id] = (string)$row->price_callback;
                            }
                        }
                        return $DATA;
                    },
                    'export' => function ($fieldSet) {
                        $temp = [];
                        if (isset($_POST['price_id'])) {
                            foreach ((array)$_POST['price_id'] as $key => $val) {
                                if ((int)$_POST['price_id'][$key] ||
                                    (
                                        isset($_POST['price_callback'][$key]) &&
                                        trim($_POST['price_callback'][$key])
                                    )
                                ) {
                                    $row = [
                                        'id' => (int)$key,
                                        'price_id' => (int)$_POST['price_id'][$key]
                                    ];
                                    if (isset($_POST['price_callback'][$key]) &&
                                        !(int)$_POST['price_id'][$key]
                                    ) {
                                        $row['price_callback'] = (string)$_POST['price_callback'][$key];
                                    }
                                    $temp[] = $row;
                                }
                            }
                        }
                        if ($temp) {
                            $fieldSet->Form->Item->mtypes = $temp;
                        }
                    },
                    'children' => [
                        'mtype' => [
                            'type' => 'select',
                            'multiple' => true,
                            'name' => 'mtype',
                            'children' => [
                                'Set' => $CONTENT['material_types']
                            ],
                            'class' => 'span2'
                        ],
                        'price_id' => [
                            'type' => 'select',
                            'multiple' => true,
                            'name' => 'price_id',
                            'children' => [
                                'Set' => $CONTENT['fields']
                            ],
                            'class' => 'span2',
                        ],
                        'price_callback' => [
                            'name' => 'price_callback',
                            'multiple' => true
                        ],
                    ]
                ])
            ]
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
