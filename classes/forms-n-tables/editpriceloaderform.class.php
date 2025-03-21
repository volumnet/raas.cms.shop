<?php
/**
 * Форма редактирования загрузчика прайсов
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\FieldSet;
use RAAS\Form as RAASForm;
use RAAS\Option;
use RAAS\CMS\InterfaceField;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;
use RAAS\CMS\Snippet_Folder;

/**
 * Класс формы редактирования загрузчика прайсов
 */
class EditPriceLoaderForm extends RAASForm
{
    /**
     * "Отбивка" категорий производится ячейками
     */
    const CATALOG_OFFSET_BY_CELLS = 0;

    /**
     * "Отбивка" категорий производится пробелами
     */
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


    /**
     * Получает поле интерфейса
     * @return RAASField
     */
    protected function getInterfaceField(): RAASField
    {
        $field = new InterfaceField([
            'name' => 'interface_id',
            'meta' => [
                'interfaceClassnameFieldName' => 'interface_classname',
                'rootInterfaceClass' => PriceloaderInterface::class
            ],
            'caption' => $this->view->_('INTERFACE'),
            'default' => PriceloaderInterface::class,
        ]);
        return $field;
    }


    public function __construct(array $params = [])
    {
        $view = $this->view;
        $t = Module::i();
        $item = isset($params['Item']) ? $params['Item'] : null;
        $content = [];
        $mt = new Material_Type();
        $content['material_types'] = ['Set' => $mt->children];
        $content['fields'] = [
            [
                'value' => 'urn',
                'caption' => $this->view->_('URN')
            ],
            [
                'value' => 'vis',
                'caption' => $this->view->_('VISIBILITY')
            ],
            [
                'value' => 'name',
                'caption' => $this->view->_('NAME')
            ],
            [
                'value' => 'description',
                'caption' => $this->view->_('DESCRIPTION')
            ],
            [
                'value' => 'meta_title',
                'caption' => $this->view->_('META_TITLE')
            ],
            [
                'value' => 'meta_description',
                'caption' => $this->view->_('META_DESCRIPTION')
            ],
            [
                'value' => 'meta_keywords',
                'caption' => $this->view->_('META_KEYWORDS')
            ],
            [
                'value' => 'priority',
                'caption' => $this->view->_('PRIORITY')
            ],
            [
                'value' => 'h1',
                'caption' => $this->view->_('H1')
            ],
            [
                'value' => 'menu_name',
                'caption' => $this->view->_('MENU_NAME')
            ],
            [
                'value' => 'breadcrumbs_name',
                'caption' => $this->view->_('BREADCRUMBS_NAME')
            ],
        ];
        if ($item && $item->id) {
            $Material_Type = $item->Material_Type;
        } elseif (isset($_POST['mtype'])) {
            $Material_Type = new Material_Type($_POST['mtype']);
        } else {
            $Material_Type = $content['material_types']['Set'][0];
        }
        foreach ((array)$Material_Type->fields as $row) {
            // 2015-06-01, AVS: убрали условие !$row->multiple, т.к.
            // из прайсов могут загружаться и множественные поля
            // 2016-10-04, AVS: убрали ограничение
            // !(in_array($row->datatype, ['file', 'image'])),
            // т.к. был запрос на "хитрую" загрузку картинок из прайсов
            $content['fields'][] = [
                'value' => (int)$row->id,
                'caption' => $row->name
            ];
        }
        $p = new Page();
        $content['pages'] = ['Set' => $p->children];

        $defaultParams = [
            'caption' => ($item && $item->id) ? $item->name : $view->_('EDIT_PRICELOADER'),
            'parentUrl' => Sub_Dev::i()->url . '&action=priceloaders',
            'meta' => ['CONTENT' => $content],
            'children' => [
                'name' => [
                    'name' => 'name',
                    'caption' => $this->view->_('NAME')
                ],
                'urn' => [
                    'name' => 'urn',
                    'caption' => $this->view->_('URN')
                ],
                'mtype' => [
                    'type' => 'select',
                    'name' => 'mtype',
                    'caption' => $this->view->_('MATERIAL_TYPE'),
                    'children' => $content['material_types'],
                    'required' => true,
                ],
                'cat_id' => [
                    'type' => 'select',
                    'name' => 'cat_id',
                    'caption' => $this->view->_('ROOT_CATEGORY'),
                    'children' => $content['pages'],
                    'required' => true,
                ],
                'interface_id' => $this->getInterfaceField(),
                'step_interface' => [
                    'type' => 'checkbox',
                    'name' => 'step_interface',
                    'caption' => $this->view->_('STEP_INTERFACE'),
                    'default' => 1,
                ],
                'create_pages' => [
                    'type' => 'checkbox',
                    'name' => 'create_pages',
                    'caption' => $this->view->_('ALLOW_TO_CREATE_PAGES')
                ],
                'create_materials' => [
                    'type' => 'checkbox',
                    'name' => 'create_materials',
                    'caption' => $this->view->_('ALLOW_TO_CREATE_MATERIALS'),
                    'default' => 1,
                ],
                'update_materials' => [
                    'type' => 'checkbox',
                    'name' => 'update_materials',
                    'caption' => $this->view->_('ALLOW_TO_UPDATE_MATERIALS'),
                    'default' => 1,
                ],
                'cats_usage' => [
                    'type' => 'select',
                    'name' => 'cats_usage',
                    'caption' => $this->view->_('CATEGORIES_USAGE'),
                    'default' => 0,
                    'class' => 'input-xxlarge',
                    'children' => [
                        [
                            'value' => PriceLoader::CATS_USAGE_NORMAL,
                            'caption' => $this->view->_('USE_CATS_REPEAT'),
                        ],
                        [
                            'value' => PriceLoader::CATS_USAGE_DONT_REPEAT,
                            'caption' => $this->view->_('USE_CATS_DONT_REPEAT'),
                        ],
                        [
                            'value' => PriceLoader::CATS_USAGE_DONT_USE,
                            'caption' => $this->view->_('DONT_USE_CATS'),
                        ],
                    ],
                ],
                'media_action' => [
                    'type' => 'select',
                    'name' => 'media_action',
                    'caption' => $this->view->_('WHAT_TO_DO_WITH_MEDIA_FIELDS'),
                    'default' => PriceLoader::MEDIA_FIELDS_APPEND_IF_EMPTY,
                    'class' => 'input-xxlarge',
                    'children' => [
                        [
                            'value' => PriceLoader::MEDIA_FIELDS_APPEND_IF_EMPTY,
                            'caption' => $this->view->_('MEDIA_FIELDS_APPEND_IF_EMPTY'),
                        ],
                        [
                            'value' => PriceLoader::MEDIA_FIELDS_APPEND_TO_NEW_ONLY,
                            'caption' => $this->view->_('MEDIA_FIELDS_APPEND_TO_NEW_ONLY'),
                        ],
                        [
                            'value' => PriceLoader::MEDIA_FIELDS_APPEND,
                            'caption' => $this->view->_('MEDIA_FIELDS_APPEND'),
                        ],
                        [
                            'value' => PriceLoader::MEDIA_FIELDS_REPLACE,
                            'caption' => $this->view->_('MEDIA_FIELDS_REPLACE'),
                        ],
                    ],
                ],
                'catalog_offset' => [
                    'type' => 'radio',
                    'name' => 'catalog_offset',
                    'caption' => $this->view->_('CATALOG_OFFSET'),
                    'children' => [
                        [
                            'value' => static::CATALOG_OFFSET_BY_CELLS,
                            'caption' => $this->view->_(
                                'CATALOG_OFFSET_BY_CELLS'
                            )
                        ],
                        [
                            'value' => static::CATALOG_OFFSET_BY_SPACES,
                            'caption' => $this->view->_(
                                'CATALOG_OFFSET_BY_SPACES'
                            )
                        ],
                    ]
                ],
                'rows' => [
                    'type' => 'number',
                    'min' => 0,
                    'name' => 'rows',
                    'caption' => $this->view->_('OFFSET') . ', '
                              .  $this->view->_('ROWS_FROM_TOP'),
                    'default' => 0
                ],
                'cols' => [
                    'type' => 'number',
                    'min' => 0,
                    'name' => 'cols',
                    'caption' => $this->view->_('OFFSET') . ', '
                              .  $this->view->_('COLS_FROM_LEFT'),
                    'default' => 0
                ],
                'columns' => new FieldSet([
                    'template' => 'dev_edit_priceloader.columns.inc.php',
                    'caption' => $this->view->_('COLUMNS'),
                    'import' => function ($fieldSet) {
                        $DATA = [];
                        if ($fieldSet->Form->Item->columns) {
                            foreach ((array)$fieldSet->Form->Item->columns as $row) {
                                $DATA['column_id'][] = (int)$row->id;
                                $DATA['column_fid'][] = (string)$row->fid;
                                $DATA['column_callback'][] = (string)$row->callback;
                                $DATA['column_download_callback'][] = (string)$row->callback_download;
                            }
                        }
                        $DATA['ufid'] = $fieldSet->Form->Item->ufid;
                        return $DATA;
                    },
                    'oncommit' => function ($fieldSet) {
                        $todelete = $fieldSet->Form->Item->columns_ids;
                        $Set = [];
                        $temp = [];
                        if (isset($_POST['column_id'])) {
                            $i = 0;
                            foreach ((array)$_POST['column_id'] as $key => $val) {
                                $row = new PriceLoader_Column((int)$val);
                                if ($row->id) {
                                    $todelete = array_diff(
                                        $todelete,
                                        [$row->id]
                                    );
                                } else {
                                    $row->pid = (int)$fieldSet->Form->Item->id;
                                }
                                $row->fid = (string)$_POST['column_fid'][$key];
                                $row->callback = (string)($_POST['column_callback'][$key] ?? '');
                                $row->callback_download = (string)($_POST['column_download_callback'][$key] ?? '');
                                $row->priority = ++$i;
                                $Set[] = $row;
                            }
                        }
                        if ($todelete) {
                            foreach ($todelete as $val) {
                                PriceLoader::delete(
                                    new PriceLoader_Column((int)$val)
                                );
                            }
                        }
                        if ($Set) {
                            foreach ($Set as $row) {
                                $row->commit();
                            }
                        }
                    },
                    'children' => [
                        'ufid' => [
                            'type' => 'radio',
                            'name' => 'ufid'
                        ],
                        'column_id' => [
                            'type' => 'hidden',
                            'name' => 'column_id',
                            'multiple' => true
                        ],
                        'column_fid' => [
                            'type' => 'select',
                            'name' => 'column_fid',
                            'children' => $content['fields'],
                            'class' => 'span2',
                            'multiple' => true
                        ],
                        'column_callback' => [
                            'type' => 'textarea',
                            'name' => 'column_callback',
                            'multiple' => true
                        ],
                        'column_download_callback' => [
                            'type' => 'textarea',
                            'name' => 'column_download_callback',
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
