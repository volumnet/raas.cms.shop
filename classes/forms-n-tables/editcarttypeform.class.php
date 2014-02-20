<?php
namespace RAAS\CMS\Shop;
use \RAAS\Field as RAASField;
use \RAAS\Option;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Material_Field;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\FieldSet;
use \RAAS\CMS\Snippet_Folder;
use \RAAS\CMS\Snippet;

class EditCartTypeForm extends \RAAS\Form
{
    public function __construct(array $params = array())
    {
        $view = Module::i()->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;
        $CONTENT = array();
        $CONTENT['material_types'] = (array)Material_Type::getSet();
        $CONTENT['forms'] = array('Set' => array_merge(array(new CMSForm(array('id' => '', 'name' => $view->_('_NONE')))), CMSForm::getSet()));
        $CONTENT['fields'] = array('0' => new Material_Field(array('id' => 0, 'name' => $view->_('_NONE'))));
        foreach ($CONTENT['material_types'] as $row) {
            $CONTENT['fields'][$row->id][] = new Material_Field(array('id' => 0, 'name' => $view->_('_NONE')));
            if ($row->id) {
                foreach ($row->fields as $row2) {
                    if (!$row2->multiple && !in_array($row2->mtype, array('file', 'image'))) {
                        $CONTENT['fields'][$row->id][] = $row2;
                    }
                }
            }
        }
        $wf = function(Snippet_Folder $x) use (&$wf) {
            $temp = array();
            foreach ($x->children as $row) {
                if ($row->urn != '__RAAS_views') {
                    $o = new Option(array('value' => '', 'caption' => $row->name, 'disabled' => 'disabled'));
                    $o->children = $wf($row);
                    $temp[] = $o;
                }
            }
            foreach ($x->snippets as $row) {
                $temp[] = new Option(array('value' => $row->id, 'caption' => $row->name));
            }
            return $temp;
        };

        $defaultParams = array(
            'caption' => $Item->id ? $Item->name : $view->_('EDIT_CART_TYPE'),
            'parentUrl' => Sub_Dev::i()->url . '&action=cart_types',
            'meta' => array('CONTENT' => $CONTENT),
            'children' => array(
                array('name' => 'urn', 'caption' => $view->_('URN')),
                array('name' => 'name', 'caption' => $view->_('NAME'), 'required' => 'required'), 
                array('type' => 'select', 'name' => 'form_id', 'caption' => $view->_('USE_FORM_FIELDS'), 'children' => $CONTENT['forms']),
                array(
                    'type' => 'select',
                    'class' => 'input-xxlarge',
                    'name' => 'interface_id', 
                    'caption' => $view->_('INTERFACE'), 
                    'placeholder' => $view->_('_NONE'), 
                    'children' => $wf(new Snippet_Folder()),
                    'default' => Snippet::importByURN('__RAAS_shop_order_notify')->id,
                ),
                array(
                    'type' => 'codearea', 
                    'name' => 'description', 
                    'caption' => $view->_('TEMPLATE_CODE'), 
                    'default' => Module::i()->stdFormTemplate,
                    'import' => function($Field) { 
                        return $Field->Form->Item->Interface->id ? $Field->Form->Item->Interface->description : $Field->importDefault(); 
                    },
                    'export' => function($Field) {
                        $Field->Form->Item->description = '';
                        if (!(isset($_POST['interface_id']) && (int)$_POST['interface_id']) && isset($_POST['description'])) {
                            $Field->Form->Item->description = (string)$_POST['description'];
                        }
                    }, 
                ),
                array('type' => 'checkbox', 'name' => 'no_amount', 'caption' => $view->_('FAVORITES_MODE')),

                new FieldSet(array(
                    'template' => 'dev_edit_cart_type.mtypes.php',
                    'name' => 'mtypes', 
                    'caption' => $view->_('MATERIAL_TYPES'),
                    'import' => function($FieldSet) {
                        $DATA = array();
                        if ($FieldSet->Form->Item->material_types) {
                            foreach ((array)$FieldSet->Form->Item->material_types as $row) {
                                $DATA['price_id'][$row->id] = (int)$row->price_id;
                                $DATA['price_callback'][$row->id] = (string)$row->price_callback;
                            }
                        }
                        return $DATA;
                    },
                    'export' => function($FieldSet) {
                        $temp = array();
                        if (isset($_POST['price_id'])) {
                            foreach ((array)$_POST['price_id'] as $key => $val) {
                                if ((int)$_POST['price_id'][$key] || (isset($_POST['price_callback'][$key]) && trim($_POST['price_callback'][$key]))) {
                                    $row = array('id' => (int)$key, 'price_id' => (int)$_POST['price_id'][$key]);
                                    if (isset($_POST['price_callback'][$key]) && !(int)$_POST['price_id'][$key]) {
                                        $row['price_callback'] = (string)$_POST['price_callback'][$key];
                                    }
                                    $temp[] = $row;
                                }
                            }
                        }
                        if ($temp) {
                            $FieldSet->Form->Item->mtypes = $temp;
                        }
                    },
                    'children' => array(
                        'mtype' => array('type' => 'select', 'name' => 'mtype', 'children' => array('Set' => $CONTENT['material_types']), 'class' => 'span2'),
                        'price_id' => array('type' => 'select', 'name' => 'price_id', 'children' => array('Set' => $CONTENT['fields']), 'class' => 'span2'),
                        'price_callback' => array('name' => 'price_callback'),
                    )
                ))
            )
        );
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}