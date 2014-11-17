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

class EditImageLoaderForm extends \RAAS\Form
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


    protected function getInterfaceField()
    {
        $wf = function(Snippet_Folder $x) use (&$wf) {
            $temp = array();
            foreach ($x->children as $row) {
                if ($row->urn != '__RAAS_views') {
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
        $snippet = Snippet::importByURN('__RAAS_shop_imageloader_interface');
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

        $defaultParams = array(
            'caption' => $Item->id ? $Item->name : $view->_('EDIT_PRICELOADER'),
            'parentUrl' => Sub_Dev::i()->url . '&action=imageloaders',
            'meta' => array('CONTENT' => $CONTENT),
            'children' => array(
                array('name' => 'name', 'caption' => $this->view->_('NAME')), 
                array('type' => 'select', 'name' => 'mtype', 'caption' => $this->view->_('MATERIAL_TYPE'), 'children' => $CONTENT['material_types'], 'required' => true, ),
                array('type' => 'select', 'name' => 'ufid', 'caption' => $this->view->_('UNIQUE_FIELD'), 'children' => $CONTENT['fields']),
                array('name' => 'sep_string', 'caption' => $this->view->_('SEPARATOR'), 'class' => 'span1', 'default' => '.'), 
                $this->getInterfaceField(),
            )
        );
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}