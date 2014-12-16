<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Material_Field;
use \RAAS\FormTab;
use \RAAS\FieldSet;
use \RAAS\Field as RAASField;

class EditYMLTypeForm extends \RAAS\Form
{
    protected static $typesCompatibility = array(
        'text' => array('color', 'date', 'datetime-local', 'email', 'number', 'range', 'tel', 'time', 'url', 'month', 'radio', 'select'),
        'date' => array('datetime-local'), 
        'datetime-local' => array('date'), 
        'number' => array('text', 'range', 'checkbox', 'radio', 'select'), 
        'url' => array('text', 'email'), 
        'checkbox' => array('number', 'radio', 'select'), 
        'image' => array('file'), 
        'material' => array('material'), 
    );

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


    public function __construct(array $params = array())
    {
        $this->view->js[] = $this->view->publicURL . '/edit_yml_type.js';
        $Block = isset($params['Block']) ? $params['Block'] : null;
        $MType = isset($params['MType']) ? $params['MType'] : null;
        $Page = isset($params['Page']) ? $params['Page'] : null;
        
        $temp = array();
        foreach (Block_YML::$ymlTypes as $key => $val) {
            if ($key) {
                $temp[] = array('value' => $key, 'caption' => $this->view->_('YML_TYPE_' . strtoupper(preg_replace('/\\W+/i', '_', $key))));
            } else {
                $temp[] = array('value' => $key, 'caption' => $this->view->_('YML_TYPE_SIMPLE'));
            }
        }
        $defaultParams = array(
            'actionMenu' => false,
            'parentUrl' => $this->view->parent->parent->url . '&action=edit_block&id=' . (int)$Block->id . '&pid=' . $Page->id,
            'caption' => $MType->name,
            'meta' => array(
                'MType' => $MType,
                'Block' => $Block,
            ),
            'children' => array(
                'common' => new FormTab(array(
                    'name' => 'common',
                    'caption' => $this->view->_('COMMON_FIELDS'),
                    'children' => array(
                        'type' => array('type' => 'select', 'caption' => $this->view->_('MATERIAL_TYPE'), 'children' => $temp, 'name' => 'type'),
                        'fields' => new FieldSet(array(
                            'children' => array(), 
                            'template' => 'edit_yml_type_fields.inc.php',
                        )),
                    )
                )),
                'additional' => new FormTab(array(
                    'name' => 'additional',
                    'caption' => $this->view->_('ADDITIONAL_FIELDS'),
                    'children' => array(
                        'addfields' => new FieldSet(array('template' => 'edit_yml_type_param.inc.php')),
                        'param_exceptions' => array('type' => 'checkbox', 'name' => 'param_exceptions', 'caption' => $this->view->_('USE_ALL_UNUSED_FIELDS')),
                        'ignore_param' => array(
                            'type' => 'select', 
                            'name' => 'ignore_param', 
                            'multiple' => true, 
                            'children' => $this->filterFieldsByType($MType),
                            'caption' => $this->view->_('EXCEPT')
                        ),
                        'params_callback' => array(
                            'name' => 'params_callback', 
                            'caption' => $this->view->_('GLOBAL_PARAMS_CALLBACK'), 
                            'data-hint' => $this->view->_('FIELDS_CALLBACK_HINT')
                        )
                    )
                )),
            ),
            'commit' => function($Form) use ($Block, $MType) {
                $type = '';
                if (isset($_POST['type']) && trim($_POST['type']) && isset(Block_YML::$ymlTypes[trim($_POST['type'])])) {
                    $type = trim($_POST['type']);
                }
                $fields = array();
                if (isset($_POST['field_id'])) {
                    foreach ($_POST['field_id'] as $key => $val) {
                        if ((in_array($key, Block_YML::$defaultFields[0]) || in_array($key, Block_YML::$defaultFields[1]) || in_array($key, Block_YML::$ymlTypes[$type]))) {
                            $row = array();
                            if (isset($_POST['field_id'][$key]) && trim($_POST['field_id'][$key])) {
                                $row['field_id'] = trim($_POST['field_id'][$key]);
                            } 
                            if (isset($_POST['field_value'][$key]) && trim($_POST['field_value'][$key])) {
                                $row['field_static_value'] = trim($_POST['field_value'][$key]);
                            }
                            if (isset($_POST['field_callback'][$key]) && trim($_POST['field_callback'][$key])) {
                                $row['field_callback'] = trim($_POST['field_callback'][$key]);
                            }
                            if ($row) {
                                $fields[$key] = $row;
                            }
                        }
                    }
                }
                $params = array();
                if (isset($_POST['add_param_name'])) {
                    foreach ($_POST['add_param_name'] as $key => $val) {
                        if (trim($val) || (isset($_POST['add_param_field'][$key]) && trim($_POST['add_param_field'][$key]))) {
                            if ((isset($_POST['add_param_field'][$key]) && trim($_POST['add_param_field'][$key])) || (isset($_POST['add_param_value'][$key]) && $_POST['add_param_value'][$key])) {
                                $row = array();
                                if (trim($val)) {
                                    $row['param_name'] = trim($val);
                                }
                                if (isset($_POST['add_param_field'][$key]) && trim($_POST['add_param_field'][$key])) {
                                    $row['field_id'] = trim($_POST['add_param_field'][$key]);
                                } 
                                if (isset($_POST['add_param_value'][$key]) && trim($_POST['add_param_value'][$key])) {
                                    $row['param_static_value'] = trim($_POST['add_param_value'][$key]);
                                }
                                if (isset($_POST['add_param_callback'][$key]) && trim($_POST['add_param_callback'][$key])) {
                                    $row['field_callback'] = trim($_POST['add_param_callback'][$key]);
                                }
                                if (isset($_POST['add_param_unit'][$key]) && trim($_POST['add_param_unit'][$key])) {
                                    $row['param_unit'] = trim($_POST['add_param_unit'][$key]);
                                }
                                if ($row) {
                                    $params[] = $row;
                                }
                            }
                        }
                    }
                }
                $param_exceptions = isset($_POST['param_exceptions']) ? true : false;
                $ignore_param = array();
                if ($param_exceptions) {
                    $params_callback = trim(isset($_POST['params_callback']) ? $_POST['params_callback'] : '');
                    if (isset($_POST['ignore_param'])) {
                        $ignore_param = (array)$_POST['ignore_param'];
                    }
                } else {
                    $params_callback = '';
                }
                $Block->addType($MType, $type, $fields, $params, $ignore_param, $param_exceptions, $params_callback);
            }
        );
        $temp = array();
        foreach (Block_YML::$defaultFields[0] as $key) {
            $temp[$key] = array();
        }
        foreach (Block_YML::$ymlTypes as $t => $arr) {
            foreach ($arr as $key) {
                $temp[$key][] = $t;
            }
        }
        foreach (Block_YML::$defaultFields[1] as $key) {
            $temp[$key] = array();
        }
        
        foreach ($temp as $key => $types) {
            if (isset(Block_YML::$ymlFields[$key])) {
                $arr = Block_YML::$ymlFields[$key];
                $sel = array(
                    'type' => 'select', 
                    'placeholder' => '--',
                    'name' => 'field_id[' . $key . ']',
                    'children' => $this->filterFieldsByType($MType, (isset($arr['type']) && $arr['type'] ? $arr['type'] : 'text')),
                );
                if (in_array($key, array('name', 'description'))) {
                    $sel['default'] = $key;
                } elseif (isset($MType->fields[$key])) {
                    $sel['default'] = $MType->fields[$key]->id;
                }
                $fc = array('name' => 'field_callback[' . $key . ']');
                if ($arr['callback']) {
                    $fc['placeholder'] = $arr['callback'];
                }
                $fv = array('name' => 'field_value[' . $key . ']');
                if ($key == 'currencyId') {
                    $fv['type'] = 'select';
                    $fv['default'] = $Block->default_currency;
                    $fv['placeholder'] = '--';
                    $fv['children'] = array(array('value' => $Block->default_currency, 'caption' => $this->view->_('CURRENCY_' . $Block->default_currency)));
                    foreach ($Block->currencies as $c => $cur) {
                        $fv['children'][] = array('value' => $c, 'caption' => $this->view->_('CURRENCY_' . $c));
                    }
                } else {
                    if (isset($arr['type']) && $arr['type'] && !in_array($arr['type'], array('material', 'file', 'image'))) {
                        $fv['type'] = $arr['type'];
                    }
                }

                $rowarr = array(
                    'name' => $key, 
                    'caption' => $this->view->_('YML_FIELD_' . strtoupper($key)) ,
                    'children' => array('field_id' => $sel, 'field_callback' => $fc, 'field_value' => $fv),
                    'import' => function($FieldSet) {
                        $DATA = array();
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
                );
                if ($types) {
                    $rowarr['data-types'] = implode(';', $types);
                }
                if (isset($arr['required']) && $arr['required']) {
                    $rowarr['data-required'] = 1;
                    $t = $this;
                    $rowarr['check'] = function($FieldSet) use ($key, $t) {
                        if ((in_array($key, Block_YML::$defaultFields[0]) || in_array($key, Block_YML::$defaultFields[1]) || in_array($key, Block_YML::$ymlTypes[$_POST['type']])) && !trim($_POST['field_id'][$key]) && !trim($_POST['field_value'][$key])) {
                            return array('name' => 'MISSED', 'value' => $key, 'description' => sprintf($t->view->_('ERR_FIELD_REQUIRED'), $t->view->_('YML_FIELD_' . strtoupper($key))));
                        }
                    };
                }
                $row = new FieldSet($rowarr);
                $defaultParams['children']['common']->children['fields']->children[$key] = $row;
            }
        }
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }


    public function filterFieldsByType(Material_Type $Material_Type, $type = null)
    {
        $temp = array();
        $temp[] = array('value' => 'name', 'caption' => $this->view->_('NAME'));
        $temp[] = array('value' => 'description', 'caption' => $this->view->_('DESCRIPTION'));
        foreach ($Material_Type->fields as $row) {
            if (($type === null) || !isset(self::$typesCompatibility[$type]) || ($row->datatype == $type) || (in_array($row->datatype, self::$typesCompatibility[$type]))) {
                $temp[] = array('value' => (int)$row->id, 'caption' => $row->name);
            }
        }
        return $temp;
    }


    /**
     * @todo
     */
    public function importDefault()
    {
        $DATA = array();
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
            $DATA = array();
            if (isset($Block->types[$MType->id]->settings)) {
                $s = $Block->types[$MType->id]->settings;
                if (isset($s['type'])) {
                    $DATA['type'] = $s['type'];
                }
                if (isset($s['param_exceptions'])) {
                    $DATA['param_exceptions'] = (int)(bool)$s['param_exceptions'];
                }
                if (isset($s['params_callback'])) {
                    $DATA['params_callback'] = $s['params_callback'];
                }
                if (isset($s['fields'])) {
                    foreach ($s['fields'] as $key => $row) {
                        if (isset($row['field']) && $row['field']->id) {
                            $DATA['field_id'][$key] = $DATA['field_id[' . $key . ']'] = (int)$row['field']->id;
                        } elseif (isset($row['field_id']) && trim($row['field_id'])) {
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

                if (isset($s['params'])) {
                    foreach ($s['params'] as $key => $row) {
                        if (isset($row['name'])) {
                            $DATA['add_param_name'][$key] = trim($row['name']);
                        }
                        if (isset($row['field']) && $row['field']->id) {
                            $DATA['add_param_field'][$key] = (int)$row['field']->id;
                        } elseif (isset($row['field_id']) && trim($row['field_id'])) {
                            $DATA['add_param_field'][$key] = trim($row['field_id']);
                        }
                        if (isset($row['callback'])) {
                            $DATA['add_param_callback'][$key] = trim($row['callback']);
                        }
                        if (isset($row['unit'])) {
                            $DATA['add_param_unit'][$key] = trim($row['unit']);
                        }
                        if (isset($row['value'])) {
                            $DATA['add_param_value'][$key] = trim($row['value']);
                        }
                    }
                }

                if (isset($s['ignored'])) {
                    foreach ($s['ignored'] as $row) {
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