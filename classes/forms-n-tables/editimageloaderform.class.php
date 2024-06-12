<?php
/**
 * Форма редактирования загрузчика изображений
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\FieldSet;
use RAAS\Form as RAASForm;
use RAAS\Option;
use RAAS\CMS\Form as CMSForm;
use RAAS\CMS\InterfaceField;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Snippet_Folder;
use RAAS\CMS\Snippet;

/**
 * Форма редактирования загрузчика изображений
 */
class EditImageLoaderForm extends RAASForm
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
        $field = new InterfaceField([
            'name' => 'interface_id',
            'meta' => [
                'interfaceClassnameFieldName' => 'interface_classname',
                'rootInterfaceClass' => ImageloaderInterface::class
            ],
            'caption' => $this->view->_('INTERFACE'),
            'default' => ImageloaderInterface::class,
        ]);
        return $field;
    }


    public function __construct(array $params = [])
    {
        $view = $this->view;
        $t = Module::i();
        $item = isset($params['Item']) ? $params['Item'] : null;
        $CONTENT = [];
        $mt = new Material_Type();
        $CONTENT['material_types'] = ['Set' => $mt->children];
        $CONTENT['imageFields'] = [];
        $CONTENT['fields'] = [
            ['value' => 'urn', 'caption' => $this->view->_('URN')],
            ['value' => 'name', 'caption' => $this->view->_('NAME')],
            ['value' => 'description', 'caption' => $this->view->_('DESCRIPTION')],
        ];
        if ($item && $item->id) {
            $Material_Type = $item->Material_Type;
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
            'caption' => ($item && $item->id) ? $item->name : $view->_('EDIT_PRICELOADER'),
            'parentUrl' => Sub_Dev::i()->url . '&action=imageloaders',
            'meta' => ['CONTENT' => $CONTENT],
            'children' => [
                'name' => [
                    'name' => 'name',
                    'caption' => $this->view->_('NAME'),
                ],
                'urn' => [
                    'name' => 'urn',
                    'caption' => $this->view->_('URN'),
                ],
                'mtype' => [
                    'type' => 'select',
                    'name' => 'mtype',
                    'caption' => $this->view->_('MATERIAL_TYPE'),
                    'children' => $CONTENT['material_types'],
                    'required' => true,
                ],
                'ufid' => [
                    'type' => 'select',
                    'name' => 'ufid',
                    'caption' => $this->view->_('UNIQUE_FIELD'),
                    'children' => $CONTENT['fields'],
                ],
                'ifid' => [
                    'type' => 'select',
                    'name' => 'ifid',
                    'caption' => $this->view->_('IMAGE_FIELD'),
                    'children' => $CONTENT['imageFields'],
                ],
                'sep_string' => [
                    'name' => 'sep_string',
                    'caption' => $this->view->_('SEPARATOR'),
                    'class' => 'span1',
                    'default' => '.',
                ],
                'interface_id' => $this->getInterfaceField(),
            ],
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
