<?php
/**
 * Форма редактирования типа в блоке Яндекс-Маркета
 */
namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\Form as RAASForm;
use RAAS\FieldSet;
use RAAS\FormTab;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;

/**
 * Форма редактирования типа в блоке Яндекс-Маркета
 */
class EditYMLTypeForm extends RAASForm
{
    /**
     * Таблица совместимости типов полей
     * (поля каких типов можно использовать для данного типа YML-поля)
     * @var array <pre>array<
     *     string[] тип YML-поля => string[] типы полей материала
     * ></pre>
     */
    protected static $typesCompatibility = [
        'text' => [
            'color',
            'date',
            'datetime-local',
            'email',
            'number',
            'range',
            'tel',
            'time',
            'url',
            'month',
            'radio',
            'select',
            'material',
        ],
        'date' => [
            'datetime-local'
        ],
        'datetime-local' => [
            'date'
        ],
        'number' => [
            'text',
            'range',
            'checkbox',
            'radio',
            'select'
        ],
        'url' => [
            'text',
            'email'
        ],
        'checkbox' => [
            'number',
            'radio',
            'select'
        ],
        'image' => [
            'file'
        ],
        'material' => [
            'material'
        ],
    ];

    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Main::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = [])
    {
        $this->view->js[] = $this->view->publicURL . '/edit_yml_type.js';
        $Block = isset($params['Block']) ? $params['Block'] : null;
        $MType = isset($params['MType']) ? $params['MType'] : null;
        $Page = isset($params['Page']) ? $params['Page'] : null;

        $temp = [];
        foreach (Block_YML::$ymlTypes as $key => $val) {
            if ($key) {
                $temp[] = [
                    'value' => $key,
                    'caption' => $this->view->_(
                        'YML_TYPE_' . strtoupper(preg_replace(
                            '/\\W+/i',
                            '_',
                            $key
                        ))
                    )
                ];
            } else {
                $temp[] = [
                    'value' => $key,
                    'caption' => $this->view->_('YML_TYPE_SIMPLE')
                ];
            }
        }
        $defaultParams = [
            'actionMenu' => false,
            'parentUrl' => $this->view->parent->parent->url
                        .  '&action=edit_block&id=' . (int)$Block->id
                        . '&pid=' . $Page->id,
            'caption' => $MType->name,
            'meta' => [
                'MType' => $MType,
                'Block' => $Block,
            ],
            'children' => [
                'common' => new FormTab([
                    'name' => 'common',
                    'caption' => $this->view->_('COMMON_FIELDS'),
                    'children' => [
                        'type' => [
                            'type' => 'select',
                            'caption' => $this->view->_('MATERIAL_TYPE'),
                            'children' => $temp,
                            'name' => 'type'
                        ],
                        'fields' => new FieldSet([
                            'children' => [],
                            'template' => 'edit_yml_type_fields.inc.php',
                        ]),
                    ]
                ]),
                'additional' => new FormTab([
                    'name' => 'additional',
                    'caption' => $this->view->_('ADDITIONAL_FIELDS'),
                    'children' => [
                        'addfields' => new FieldSet([
                            'template' => 'edit_yml_type_param.inc.php'
                        ]),
                        'param_exceptions' => [
                            'type' => 'checkbox',
                            'name' => 'param_exceptions',
                            'caption' => $this->view->_('USE_ALL_UNUSED_FIELDS')
                        ],
                        'ignore_param' => [
                            'type' => 'select',
                            'name' => 'ignore_param',
                            'multiple' => true,
                            'children' => $this->filterFieldsByType($MType),
                            'caption' => $this->view->_('EXCEPT')
                        ],
                        'params_callback' => [
                            'name' => 'params_callback',
                            'caption' => $this->view->_(
                                'GLOBAL_PARAMS_CALLBACK'
                            ),
                            'data-hint' => $this->view->_(
                                'FIELDS_CALLBACK_HINT'
                            )
                        ]
                    ]
                ]),
            ],
            'commit' => function ($Form) use ($Block, $MType) {
                $type = '';
                if (isset($_POST['type']) &&
                    trim($_POST['type']) &&
                    isset(Block_YML::$ymlTypes[trim($_POST['type'])])
                ) {
                    $type = trim($_POST['type']);
                }
                $fields = [];
                if (isset($_POST['field_id'])) {
                    foreach ($_POST['field_id'] as $key => $val) {
                        if (in_array($key, Block_YML::$defaultFields[0]) ||
                            in_array($key, Block_YML::$defaultFields[1]) ||
                            in_array($key, Block_YML::$ymlTypes[$type])
                        ) {
                            $row = [];
                            if (isset($_POST['field_id'][$key]) &&
                                trim($_POST['field_id'][$key])
                            ) {
                                $row['field_id'] = trim(
                                    $_POST['field_id'][$key]
                                );
                            }
                            if (isset($_POST['field_value'][$key]) &&
                                trim($_POST['field_value'][$key])
                            ) {
                                $row['field_static_value'] = trim(
                                    $_POST['field_value'][$key]
                                );
                            }
                            if (isset($_POST['field_callback'][$key]) &&
                                trim($_POST['field_callback'][$key])
                            ) {
                                $row['field_callback'] = trim(
                                    $_POST['field_callback'][$key]
                                );
                            }
                            if ($row) {
                                $fields[$key] = $row;
                            }
                        }
                    }
                }
                $params = [];
                if (isset($_POST['add_param_name'])) {
                    foreach ($_POST['add_param_name'] as $key => $val) {
                        if (trim($val) ||
                            (
                                isset($_POST['add_param_field'][$key]) &&
                                trim($_POST['add_param_field'][$key])
                            )
                        ) {
                            if ((
                                    isset($_POST['add_param_field'][$key]) &&
                                    trim($_POST['add_param_field'][$key])
                                ) || (
                                    isset($_POST['add_param_value'][$key]) &&
                                    $_POST['add_param_value'][$key]
                                )
                            ) {
                                $row = [];
                                if (trim($val)) {
                                    $row['param_name'] = trim($val);
                                }
                                if (isset($_POST['add_param_field'][$key]) &&
                                    trim($_POST['add_param_field'][$key])
                                ) {
                                    $row['field_id'] = trim(
                                        $_POST['add_param_field'][$key]
                                    );
                                }
                                if (isset($_POST['add_param_value'][$key]) &&
                                    trim($_POST['add_param_value'][$key])
                                ) {
                                    $row['param_static_value'] = trim(
                                        $_POST['add_param_value'][$key]
                                    );
                                }
                                if (isset($_POST['add_param_callback'][$key]) &&
                                    trim($_POST['add_param_callback'][$key])
                                ) {
                                    $row['field_callback'] = trim(
                                        $_POST['add_param_callback'][$key]
                                    );
                                }
                                if (isset($_POST['add_param_unit'][$key]) &&
                                    trim($_POST['add_param_unit'][$key])
                                ) {
                                    $row['param_unit'] = trim(
                                        $_POST['add_param_unit'][$key]
                                    );
                                }
                                if ($row) {
                                    $params[] = $row;
                                }
                            }
                        }
                    }
                }
                $paramExceptions = isset($_POST['param_exceptions']);
                $ignoreParam = [];
                if ($paramExceptions) {
                    $paramsCallback = trim(
                        isset($_POST['params_callback']) ?
                        $_POST['params_callback'] :
                        ''
                    );
                    if (isset($_POST['ignore_param'])) {
                        $ignoreParam = (array)$_POST['ignore_param'];
                    }
                } else {
                    $paramsCallback = '';
                }
                $Block->addType(
                    $MType,
                    $type,
                    $fields,
                    $params,
                    $ignoreParam,
                    $paramExceptions,
                    $paramsCallback
                );
            }
        ];
        $temp = [];
        foreach (Block_YML::$defaultFields[0] as $key) {
            $temp[$key] = [];
        }
        foreach (Block_YML::$ymlTypes as $t => $arr) {
            foreach ($arr as $key) {
                $temp[$key][] = $t;
            }
        }
        foreach (Block_YML::$defaultFields[1] as $key) {
            $temp[$key] = [];
        }

        foreach ($temp as $key => $types) {
            $fieldSet = $this->getYMLFieldFieldSet($key, $MType, $Block, $types);
            if ($fieldSet) {
                $defaultParams['children']['common']->children['fields']->children[$key] = $fieldSet;
            }
        }
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }


    /**
     * Получает строку параметров для поля YML
     * @todo
     * @param string $ymlURN URN поля в системе Яндекс.Маркет
     * @param Material_Type $mType Тип материалов для связки
     * @param Block_YML $block Текущий блок
     * @param string[] $types Типы выгрузки, для которых подходит поле
     * @return FieldSet|null null, если не найдено поле
     */
    public function getYMLFieldFieldSet(
        $ymlURN,
        Material_Type $mType,
        Block_YML $block,
        array $types = []
    ) {
        if (!isset(Block_YML::$ymlFields[$ymlURN])) {
            return null;
        }
        $ymlField = Block_YML::$ymlFields[$ymlURN];
        $fieldFieldData = [
            'type' => 'select',
            'placeholder' => '--',
            'name' => 'field_id[' . $ymlURN . ']',
            'children' => $this->filterFieldsByType(
                $mType,
                (
                    (isset($ymlField['type']) && $ymlField['type']) ?
                    $ymlField['type'] :
                    'text'
                )
            ),
        ];
        if (in_array($ymlURN, ['name', 'description'])) {
            $fieldFieldData['default'] = $ymlURN;
        } elseif (isset($mType->fields[$ymlURN])) {
            $fieldFieldData['default'] = $mType->fields[$ymlURN]->id;
        }
        $callbackFieldData = [
            'type' => 'textarea',
            'name' => 'field_callback[' . $ymlURN . ']'
        ];
        if ($ymlField['callback']) {
            $callbackFieldData['placeholder'] = $ymlField['callback'];
        }
        $valueFieldData = ['name' => 'field_value[' . $ymlURN . ']'];
        if ($ymlURN == 'currencyId') {
            $valueFieldData['type'] = 'select';
            $valueFieldData['default'] = $block->default_currency;
            $valueFieldData['placeholder'] = '--';
            $valueFieldData['children'] = [[
                'value' => $block->default_currency,
                'caption' => $this->view->_(
                    'CURRENCY_' . $block->default_currency
                )
            ]];
            foreach ($block->currencies as $c => $cur) {
                $valueFieldData['children'][] = [
                    'value' => $c,
                    'caption' => $this->view->_('CURRENCY_' . $c)
                ];
            }
        } else {
            if (isset($ymlField['type']) &&
                $ymlField['type'] &&
                !in_array(
                    $ymlField['type'],
                    [/*'material', */'file', 'image']
                )
            ) {
                $valueFieldData['type'] = $ymlField['type'];
            }
        }

        $fieldSetData = [
            'name' => $ymlURN,
            'caption' => $this->view->_(
                'YML_FIELD_' . strtoupper($ymlURN)
            ),
            'children' => [
                'field_id' => $fieldFieldData,
                'field_callback' => $callbackFieldData,
                'field_value' => $valueFieldData
            ],
            'import' => function ($FieldSet) {
                $DATA = [];
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    if (isset($_POST['field_id'][$FieldSet->name])) {
                        $DATA['field_id[' . $FieldSet->name . ']'] = $_POST['field_id'][$FieldSet->name];
                    }
                    if (isset($_POST['field_callback'][$FieldSet->name])) {
                        $DATA['field_callback[' . $FieldSet->name . ']'] = $_POST['field_callback'][$FieldSet->name];
                    }
                    if (isset($_POST['field_value'][$FieldSet->name])) {
                        $DATA['field_value[' . $FieldSet->name . ']'] = $_POST['field_value'][$FieldSet->name];
                    }
                }
                return $DATA;
            }
        ];
        if ($types) {
            $fieldSetData['data-types'] = implode(';', $types);
        }
        if (isset($ymlField['required']) && $ymlField['required']) {
            $fieldSetData['data-required'] = 1;
            $fieldSetData['check'] = function ($FieldSet) use ($ymlURN) {
                if ((
                        in_array($ymlURN, Block_YML::$defaultFields[0]) ||
                        in_array($ymlURN, Block_YML::$defaultFields[1]) ||
                        in_array($ymlURN, Block_YML::$ymlTypes[$_POST['type']])
                    ) &&
                    !trim($_POST['field_id'][$ymlURN]) &&
                    !trim($_POST['field_value'][$ymlURN])
                ) {
                    return [
                        'name' => 'MISSED',
                        'value' => $ymlURN,
                        'description' => sprintf(
                            $this->view->_('ERR_FIELD_REQUIRED'),
                            $this->view->_('YML_FIELD_' . strtoupper($ymlURN))
                        )
                    ];
                }
            };
        }
        $fieldSet = new FieldSet($fieldSetData);
        return $fieldSet;
    }


    /**
     * Фильтрует доступные поля типа материалов по типу данных для YML-поля
     * (в виде данных для children)
     * @param Material_Type $mType Тип материалов, используемый в YML-блоке
     * @param string|null $type Тип данных YML-поля, либо null,
     *                          если не нужно фильтровать по типу данных
     * @return array <pre>[
     *     'value' => int ID# поля типа материалов,
     *     'caption' => string Наименование поля типа материалов
     * ][]</pre>
     */
    public function filterFieldsByType(Material_Type $mType, $type = null)
    {
        $result = [
            [
                'value' => 'name',
                'caption' => $this->view->_('NAME')
            ],
            [
                'value' => 'description',
                'caption' => $this->view->_('DESCRIPTION')
            ],
        ];
        foreach ($mType->fields as $row) {
            // if (($type == 'text') && ($row->urn == 'manufacturer')) {
            //     var_dump($row->datatype, self::$typesCompatibility[$type]); exit;
            // }
            if (($type === null) ||
                !isset(self::$typesCompatibility[$type]) ||
                ($row->datatype == $type) ||
                in_array($row->datatype, self::$typesCompatibility[$type])
            ) {
                $result[] = [
                    'value' => (int)$row->id,
                    'caption' => $row->name
                ];
            }
        }
        return $result;
    }


    public function importDefault()
    {
        $DATA = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $DATA = $_POST;
            foreach ($this->children['common']->children['fields']->children as $row) {
                if ($f = $row->import) {
                    $DATA = array_merge($DATA, (array)$f($row));
                }
            }
        } else {
            $Block = $this->meta['Block'];
            $MType = $this->meta['MType'];
            $DATA = [];
            if ($Block->types[$MType->id]->settings) {
                $settings = $Block->types[$MType->id]->settings;
                if (isset($settings['type'])) {
                    $DATA['type'] = $settings['type'];
                }
                if (isset($settings['param_exceptions'])) {
                    $DATA['param_exceptions'] = (int)(bool)$settings['param_exceptions'];
                }
                if (isset($settings['params_callback'])) {
                    $DATA['params_callback'] = $settings['params_callback'];
                }
                if (isset($settings['fields'])) {
                    foreach ($settings['fields'] as $key => $row) {
                        if (isset($row['field']) && $row['field']->id) {
                            $DATA['field_id'][$key] = $DATA['field_id[' . $key . ']'] = (int)$row['field']->id;
                        } elseif (isset($row['field_id']) &&
                            trim($row['field_id'])
                        ) {
                            $DATA['field_id'][$key] = $DATA['field_id[' . $key . ']'] = trim($row['field_id']);
                        }
                        if (isset($row['callback'])) {
                            $DATA['field_callback'][$key] = $DATA['field_callback[' . $key . ']'] = trim($row['callback']);
                        }
                        if (isset($row['value'])) {
                            $DATA['field_value'][$key] = $DATA['field_value[' . $key . ']'] = trim($row['value']);
                        }
                    }
                }

                if (isset($settings['params'])) {
                    foreach ($settings['params'] as $key => $row) {
                        if (isset($row['name'])) {
                            $DATA['add_param_name'][$key] = trim($row['name']);
                        }
                        if (isset($row['field']) && $row['field']->id) {
                            $DATA['add_param_field'][$key] = (int)$row['field']->id;
                        } elseif (isset($row['field_id']) &&
                            trim($row['field_id'])
                        ) {
                            $DATA['add_param_field'][$key] = trim(
                                $row['field_id']
                            );
                        }
                        if (isset($row['callback'])) {
                            $DATA['add_param_callback'][$key] = trim(
                                $row['callback']
                            );
                        }
                        if (isset($row['unit'])) {
                            $DATA['add_param_unit'][$key] = trim($row['unit']);
                        }
                        if (isset($row['value'])) {
                            $DATA['add_param_value'][$key] = trim(
                                $row['value']
                            );
                        }
                    }
                }

                if (isset($settings['ignored'])) {
                    foreach ($settings['ignored'] as $row) {
                        if ($row instanceof Material_Field) {
                            $DATA['ignore_param'][] = (int)$row->id;
                        } else {
                            $DATA['ignore_param'][] = trim($row);
                        }
                    }
                }
            } else {
                return parent::importDefault();
            }
        }
        return $DATA;
    }
}
