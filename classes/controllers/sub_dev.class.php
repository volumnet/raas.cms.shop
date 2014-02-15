<?php
namespace RAAS\CMS\Shop;
use \RAAS\Redirector as Redirector;
use \RAAS\Attachment as Attachment;
use \ArrayObject as ArrayObject;
use \RAAS\Field as Field;
use \RAAS\FieldSet as FieldSet;
use \RAAS\FieldContainer as FieldContainer;
use \RAAS\Form as RAASForm;
use \RAAS\FormTab as FormTab;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\OptGroup as OptGroup;
use \RAAS\Option as Option;
use \RAAS\StdSub as StdSub;
use \RAAS\CMS\Material_Type as Material_Type;
use \RAAS\CMS\Material_Field as Material_Field;

class Sub_Dev extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        $this->view->submenu = \RAAS\CMS\ViewSub_Dev::i()->devMenu();
        switch ($this->action) {
            case 'edit_cart_type': case 'edit_order_status': case 'edit_priceloader': case 'edit_imageloader':
                $this->{$this->action}();
                break;
            case 'cart_types':
                $this->view->{$this->action}(array('Set' => Cart_Type::getSet()));
                break;
            case 'order_statuses':
                $this->view->{$this->action}(array('Set' => Order_Status::getSet()));
                break;
            case 'priceloaders':
                $this->view->{$this->action}(array('Set' => PriceLoader::getSet()));
                break;
            case 'imageloaders':
                $this->view->{$this->action}(array('Set' => ImageLoader::getSet()));
                break;
            case 'move_up_order_status': case 'move_down_order_status':
                $Item = new Order_Status((int)$this->id);
                $f = str_replace('_order_status', '', $this->action);
                StdSub::$f($Item, $this->url . '&action=order_statuses');
                break;
            case 'delete_cart_type':
                $Item = new Cart_Type((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=cart_types');
                break;
            case 'delete_order_status':
                $Item = new Order_Status((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=order_statuses');
                break;
            case 'delete_priceloader':
                $Item = new PriceLoader((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=priceloaders');
                break;
            case 'delete_imageloader':
                $Item = new ImageLoader((int)$this->id);
                StdSub::delete($Item, $this->url . '&action=imageloaders');
                break;
            default:
                new Redirector(\RAAS\CMS\ViewSub_Dev::i()->url);
                break;
        }
    }


    protected function edit_cart_type()
    {
        $Item = new Cart_Type((int)$this->id);
        $CONTENT = array();
        $CONTENT['material_types'] = (array)Material_Type::getSet();
        $CONTENT['forms'] = array('Set' => array_merge(array(new CMSForm(array('id' => '', 'name' => $this->view->_('_NONE')))), CMSForm::getSet()));
        $CONTENT['fields'] = array('0' => new Material_Field(array('id' => 0, 'name' => $this->view->_('_NONE'))));
        foreach ($CONTENT['material_types'] as $row) {
            $CONTENT['fields'][$row->id][] = new Material_Field(array('id' => 0, 'name' => $this->view->_('_NONE')));
            if ($row->id) {
                foreach ($row->fields as $row2) {
                    if (!$row2->multiple && !in_array($row2->mtype, array('file', 'image'))) {
                        $CONTENT['fields'][$row->id][] = $row2;
                    }
                }
            }
        }
        $Form = new RAASForm(array(
            'Item' => $Item,
            'caption' => $this->view->_('EDIT_CART_TYPE'),
            'parentUrl' => $this->url . '&action=cart_types',
            'meta' => array('CONTENT' => $CONTENT),
            'children' => array(
                array('name' => 'urn', 'caption' => $this->view->_('URN')),
                array('name' => 'name', 'caption' => $this->view->_('NAME'), 'required' => 'required'), 
                array('type' => 'select', 'name' => 'form_id', 'caption' => $this->view->_('USE_FORM_FIELDS'), 'children' => $CONTENT['forms']),
                array('type' => 'checkbox', 'name' => 'std_template', 'caption' => $this->view->_('USE_STANDARD_NOTIFY_TEXT'), 'default' => 1),
                array(
                    'type' => 'codearea', 
                    'name' => 'description', 
                    'caption' => $this->view->_('TEMPLATE_CODE'), 
                    'default' => $this->model->stdFormTemplate,
                    'import' => function($Field) use ($t) { return $Field->Form->Item->std_template ? $Field->default : $Field->importDefault(); }
                ),
                new FieldSet(array(
                    'template' => 'dev_edit_cart_type.mtypes.php',
                    'name' => 'mtypes', 
                    'caption' => $this->view->_('MATERIAL_TYPES'),
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
                        'mtype' => array('type' => 'select', 'name' => 'mtype', 'children' => $CONTENT['material_types'], 'class' => 'span2'),
                        'price_id' => array('type' => 'select', 'name' => 'price_id', 'children' => $CONTENT['fields'], 'class' => 'span2'),
                        'price_callback' => array('name' => 'price_callback'),
                    )
                ))
            )
        ));
        $this->view->{__FUNCTION__}($Form->process());
    }


    protected function edit_order_status()
    {
        $Item = new Order_Status((int)$this->id);
        $Form = new RAASForm(array(
            'Item' => $Item,
            'caption' => $this->view->_('EDIT_ORDER_STATUS'),
            'parentUrl' => $this->url . '&action=order_statuses',
            'children' => array(
                array('name' => 'name', 'caption' => $this->view->_('NAME'), 'required' => 'required'), 
            )
        ));
        $this->view->{__FUNCTION__}($Form->process());
    }


    protected function edit_priceloader()
    {
        $Item = new PriceLoader((int)$this->id);
        $CONTENT = array();
        $CONTENT['material_types'] = array('Set' => (array)Material_Type::getSet());
        $CONTENT['fields'] = array(
            array('value' => 'urn', 'caption' => $this->view->_('URN')),
            array('value' => 'name', 'caption' => $this->view->_('NAME')),
            array('value' => 'description', 'caption' => $this->view->_('DESCRIPTION')),
        );
        if ($Item->id) {
            $Material_Type = $Item->Material_Type;
        } elseif (isset($_POST['mtype'])) {
            $Material_Type = new Material_Type($_POST['mtype']);
        } else {
            $Material_Type = $CONTENT['material_types']['Set'][0];
        }
        foreach ((array)$Material_Type->fields as $row) {
            if (!($row->multiple || in_array($row->datatype, array('file', 'image')))) {
                $CONTENT['fields'][] = array('value' => (int)$row->id, 'caption' => $row->name);
            }
        }
        $Form = new RAASForm(array(
            'Item' => $Item,
            'caption' => $this->view->_('EDIT_PRICELOADER'),
            'parentUrl' => $this->url . '&action=priceloaders',
            'meta' => array('CONTENT' => $CONTENT),
            'children' => array(
                array('name' => 'name', 'caption' => $this->view->_('NAME')), 
                array('type' => 'select', 'name' => 'mtype', 'caption' => $this->view->_('MATERIAL_TYPE'), 'children' => $CONTENT['material_types'], 'required' => true, ),
                array('type' => 'checkbox', 'name' => 'std_interface', 'caption' => $this->view->_('USE_STANDARD_INTERFACE'), 'default' => 1),
                array(
                    'type' => 'codearea', 
                    'name' => 'description', 
                    'default' => $this->model->stdPriceLoaderInterface,
                    'import' => function($Field) { return $Field->Form->Item->std_interface ? $Field->default : $Field->Form->Item->description; },
                    'export' => function($Field) {
                        $Field->Form->Item->description = '';
                        if (!(isset($_POST['std_interface']) && (int)$_POST['std_interface']) && isset($_POST['description'])) {
                            $Field->Form->Item->description = (string)$_POST['description'];
                        }
                    }, 
                ),
                new FieldSet(array(
                    'template' => 'dev_edit_priceloader.columns.php',
                    'caption' => $this->view->_('COLUMNS'),
                    'import' => function($FieldSet) {
                        $DATA = array();
                        if ($FieldSet->Form->Item->columns) {
                            foreach ((array)$FieldSet->Form->Item->columns as $row) {
                                $DATA['column_id'][] = (int)$row->id;
                                $DATA['column_fid'][] = (string)$row->fid;
                                $DATA['column_callback'][] = (string)$row->callback;
                            }
                        }
                        $DATA['ufid'] = $FieldSet->Form->Item->ufid;
                        return $DATA;
                    },
                    'oncommit' => function($FieldSet) {
                        $todelete = $FieldSet->Form->Item->columns_ids;
                        $Set = array();
                        $temp = array();
                        if (isset($_POST['column_id'])) {
                            $i = 0;
                            foreach ((array)$_POST['column_id'] as $key => $val) {
                                $row = new PriceLoader_Column((int)$val);
                                if ($row->id) {
                                    $todelete = array_diff($todelete, array($row->id));
                                } else {
                                    $row->pid = (int)$FieldSet->Form->Item->id;
                                }
                                $row->fid = (string)$_POST['column_fid'][$key];
                                $row->callback = (string)$_POST['column_callback'][$key];
                                $row->priority = ++$i;
                                $Set[] = $row;
                            }
                        }
                        if ($todelete) {
                            foreach ($todelete as $val) {
                                PriceLoader::delete(new PriceLoader_Column((int)$val));
                            }
                        }
                        if ($Set) {
                            foreach ($Set as $row) {
                                $row->commit();
                            }
                        }
                    },
                    'children' => array(
                        'ufid' => array('type' => 'radio', 'name' => 'ufid'),
                        'column_id' => array('type' => 'hidden', 'name' => 'column_id', 'multiple' => true),
                        'column_fid' => array('type' => 'select', 'name' => 'column_fid', 'children' => $CONTENT['fields'], 'class' => 'span2', 'multiple' => true),
                        'column_callback' => array('name' => 'price_callback', 'multiple' => true),
                    )
                ))
            )
        ));
        $this->view->{__FUNCTION__}($Form->process());
    }


    protected function edit_imageloader()
    {
        $Item = new ImageLoader((int)$this->id);
        $CONTENT = array();
        $CONTENT['material_types'] = array('Set' => (array)Material_Type::getSet());
        $CONTENT['fields'] = array(
            array('value' => 'urn', 'caption' => $this->view->_('URN')),
            array('value' => 'name', 'caption' => $this->view->_('NAME')),
            array('value' => 'description', 'caption' => $this->view->_('DESCRIPTION')),
        );
        if ($Item->id) {
            $Material_Type = $Item->Material_Type;
        } elseif (isset($_POST['mtype'])) {
            $Material_Type = new Material_Type($_POST['mtype']);
        } else {
            $Material_Type = $CONTENT['material_types']['Set'][0];
        }
        foreach ((array)$Material_Type->fields as $row) {
            if (!($row->multiple || in_array($row->datatype, array('file', 'image')))) {
                $CONTENT['fields'][] = array('value' => (int)$row->id, 'caption' => $row->name);
            }
        }
        $Form = new RAASForm(array(
            'Item' => $Item,
            'caption' => $this->view->_('EDIT_IMAGELOADER'),
            'parentUrl' => $this->url . '&action=priceloaders',
            'meta' => array('CONTENT' => $CONTENT),
            'children' => array(
                array('name' => 'name', 'caption' => $this->view->_('NAME')), 
                array('type' => 'select', 'name' => 'mtype', 'caption' => $this->view->_('MATERIAL_TYPE'), 'children' => $CONTENT['material_types'], 'required' => true, ),
                array('type' => 'select', 'name' => 'ufid', 'caption' => $this->view->_('UNIQUE_FIELD'), 'children' => $CONTENT['fields']),
                array('name' => 'sep_string', 'caption' => $this->view->_('SEPARATOR'), 'class' => 'span1', 'default' => '.'), 
                array('type' => 'checkbox', 'name' => 'std_interface', 'caption' => $this->view->_('USE_STANDARD_INTERFACE'), 'default' => 1),
                array(
                    'type' => 'codearea', 
                    'name' => 'description', 
                    'default' => $this->model->stdImageLoaderInterface,
                    'import' => function($Field) { return $Field->Form->Item->std_interface ? $Field->default : $Field->Form->Item->description; },
                    'export' => function($Field) {
                        $Field->Form->Item->description = '';
                        if (!(isset($_POST['std_interface']) && (int)$_POST['std_interface']) && isset($_POST['description'])) {
                            $Field->Form->Item->description = (string)$_POST['description'];
                        }
                    }, 
                ),
            )
        ));
        $this->view->{__FUNCTION__}($Form->process());
    }
}