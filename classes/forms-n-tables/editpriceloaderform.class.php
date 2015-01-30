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
use \RAAS\CMS\Page;

class EditPriceLoaderForm extends \RAAS\Form
{
    const CATALOG_OFFSET_BY_CELLS = 0;
    const CATALOG_OFFSET_BY_SPACES = 4;

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


    protected function getInterfaceField()
    {
        $wf = function(Snippet_Folder $x) use (&$wf) {
            $temp = array();
            foreach ($x->children as $row) {
                if (strtolower($row->urn) != '__raas_views') {
                    $o = new Option(array('value' => '', 'caption' => $row->name, 'disabled' => 'disabled'));
                    $o->__set('children', $wf($row));
                    $temp[] = $o;
                }
            }
            foreach ($x->snippets as $row) {
                $temp[] = new Option(array('value' => $row->id, 'caption' => $row->name));
            }
            return $temp;
        };
        $snippet = Snippet::importByURN('__raas_shop_priceloader_interface');
        $field = new RAASField(array(
            'type' => 'select',
            'class' => 'input-xxlarge',
            'name' => 'interface_id', 
            'required' => true,
            'caption' => $this->view->_('INTERFACE'), 
            'placeholder' => $this->view->_('_NONE'), 
            'children' => $wf(new Snippet_Folder()),
            'default' => (int)$snippet->id
        ));
        return $field;
    }


    public function __construct(array $params = array())
    {
        $view = $this->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;
        $CONTENT = array();
        $mt = new Material_Type();
        $CONTENT['material_types'] = array('Set' => $mt->children);
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
        $p = new Page();
        $CONTENT['pages'] = array('Set' => $p->children);

        $defaultParams = array(
            'caption' => $Item->id ? $Item->name : $view->_('EDIT_PRICELOADER'),
            'parentUrl' => Sub_Dev::i()->url . '&action=priceloaders',
            'meta' => array('CONTENT' => $CONTENT),
            'children' => array(
                array('name' => 'name', 'caption' => $this->view->_('NAME')), 
                array('type' => 'select', 'name' => 'mtype', 'caption' => $this->view->_('MATERIAL_TYPE'), 'children' => $CONTENT['material_types'], 'required' => true, ),
                array('type' => 'select', 'name' => 'cat_id', 'caption' => $this->view->_('ROOT_CATEGORY'), 'children' => $CONTENT['pages'], 'required' => true, ),
                $this->getInterfaceField(),
                array('type' => 'checkbox', 'name' => 'create_pages', 'caption' => $this->view->_('ALLOW_TO_CREATE_PAGES')),
                array(
                    'type' => 'radio', 
                    'name' => 'catalog_offset', 
                    'caption' => $this->view->_('CATALOG_OFFSET'), 
                    'children' => array(
                        array('value' => static::CATALOG_OFFSET_BY_CELLS, 'caption' => $this->view->_('CATALOG_OFFSET_BY_CELLS')),
                        array('value' => static::CATALOG_OFFSET_BY_SPACES, 'caption' => $this->view->_('CATALOG_OFFSET_BY_SPACES')),
                    )
                ),
                array('type' => 'number', 'min' => 0, 'name' => 'rows', 'caption' => $this->view->_('OFFSET') . ', ' . $this->view->_('ROWS_FROM_TOP'), 'default' => 0),
                array('type' => 'number', 'min' => 0, 'name' => 'cols', 'caption' => $this->view->_('OFFSET') . ', ' . $this->view->_('COLS_FROM_LEFT'), 'default' => 0),
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
                                $DATA['column_download_callback'][] = (string)$row->callback_download;
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
                                $row->callback_download = (string)$_POST['column_download_callback'][$key];
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
                        'column_callback' => array('name' => 'column_callback', 'multiple' => true),
                        'column_download_callback' => array('name' => 'column_download_callback', 'multiple' => true),
                    )
                ))
            )
        );
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}