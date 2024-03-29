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
            $temp = [];
            foreach ($x->children as $row) {
                if (strtolower($row->urn) != '__raas_views') {
                    $o = new Option(['value' => '', 'caption' => $row->name, 'disabled' => 'disabled']);
                    $o->__set('children', $wf($row));
                    $temp[] = $o;
                }
            }
            foreach ($x->snippets as $row) {
                $temp[] = new Option([
                    'value' => $row->id,
                    'caption' => $row->urn . (($row->name && ($row->name != $row->urn)) ? (': ' . $row->name) : ''),
                ]);
            }
            return $temp;
        };
        $snippet = Snippet::importByURN('__raas_shop_imageloader_interface');
        $field = new RAASField([
            'type' => 'select',
            'class' => 'input-xxlarge',
            'name' => 'interface_id', 
            'required' => true,
            'caption' => $this->view->_('INTERFACE'), 
            'placeholder' => $this->view->_('_NONE'), 
            'children' => $wf(new Snippet_Folder()),
            'default' => (int)$snippet->id
        ]);
        return $field;
    }


    public function __construct(array $params = [])
    {
        $view = $this->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;
        $CONTENT = [];
        $mt = new Material_Type();
        $CONTENT['material_types'] = ['Set' => $mt->children];
        $CONTENT['imageFields'] = [];
        $CONTENT['fields'] = [
            ['value' => 'urn', 'caption' => $this->view->_('URN')],
            ['value' => 'name', 'caption' => $this->view->_('NAME')],
            ['value' => 'description', 'caption' => $this->view->_('DESCRIPTION')],
        ];
        if ($Item->id) {
            $Material_Type = $Item->Material_Type;
        } elseif (isset($_POST['mtype'])) {
            $Material_Type = new Material_Type($_POST['mtype']);
        } else {
            $Material_Type = $CONTENT['material_types']['Set'][0];
        }
        foreach ((array)$Material_Type->fields as $row) {
            if (!($row->multiple || in_array($row->datatype, ['file', 'image']))) {
                $CONTENT['fields'][] = ['value' => (int)$row->id, 'caption' => $row->name];
            } elseif (in_array($row->datatype, ['image'])) {
                $CONTENT['imageFields'][] = ['value' => (int)$row->id, 'caption' => $row->name];
            }
        }

        $defaultParams = [
            'caption' => $Item->id ? $Item->name : $view->_('EDIT_PRICELOADER'),
            'parentUrl' => Sub_Dev::i()->url . '&action=imageloaders',
            'meta' => ['CONTENT' => $CONTENT],
            'children' => [
                [
                    'name' => 'name',
                    'caption' => $this->view->_('NAME'),
                ],
                [
                    'name' => 'urn',
                    'caption' => $this->view->_('URN'),
                ],
                [
                    'type' => 'select',
                    'name' => 'mtype',
                    'caption' => $this->view->_('MATERIAL_TYPE'),
                    'children' => $CONTENT['material_types'],
                    'required' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'ufid',
                    'caption' => $this->view->_('UNIQUE_FIELD'),
                    'children' => $CONTENT['fields'],
                ],
                [
                    'type' => 'select',
                    'name' => 'ifid',
                    'caption' => $this->view->_('IMAGE_FIELD'),
                    'children' => $CONTENT['imageFields'],
                ],
                [
                    'name' => 'sep_string',
                    'caption' => $this->view->_('SEPARATOR'),
                    'class' => 'span1',
                    'default' => '.',
                ],
                $this->getInterfaceField(),
            ],
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
