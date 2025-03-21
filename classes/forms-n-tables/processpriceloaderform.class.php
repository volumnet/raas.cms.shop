<?php
/**
 * Форма загрузки прайс-листа
 */
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;
use RAAS\CMS\Page;

/**
 * Форма загрузки прайс-листа
 */
class ProcessPriceLoaderForm extends \RAAS\Form
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Priceloaders::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = [])
    {
        $p = new Page();
        $loader = $params['loader'] ?? null;

        $defaultParams = [
            'caption' => $loader->name,
            'parentUrl' => Sub_Priceloaders::i()->url . '&action=priceloaders',
            'class' => 'form-horizontal',
            'meta' => [
                'loader' => $loader,
            ],
            'children' => [
                'material_type' => [
                    'name' => 'material_type',
                    'caption' => $this->view->_('MATERIAL_TYPE'),
                    'export' => 'is_null',
                    'import' => 'is_null',
                    'template' => 'cms/shop/loaders.fields.inc.php',
                ],
                'columns' => [
                    'name' => 'columns',
                    'caption' => $this->view->_('COLUMNS'),
                    'export' => 'is_null',
                    'import' => 'is_null',
                    'template' => 'cms/shop/loaders.fields.inc.php',
                ],
                'cat_id' => [
                    'type' => 'select',
                    'name' => 'cat_id',
                    'caption' => $this->view->_('ROOT_CATEGORY'),
                    'children' => ['Set' => $p->children],
                    'class' => 'span4',
                    'default' => (int)$loader->cat_id,
                ],
                'offset' => new FieldSet([
                    'name' => 'offset',
                    'caption' => $this->view->_('OFFSET'),
                    'template' => 'cms/shop/loaders.fields.inc.php',
                    'children' => [
                        'rows' => [
                            'type' => 'number',
                            'class' => 'span1',
                            'name' => 'rows',
                            'caption' => mb_strtolower($this->view->_('ROWS_FROM_TOP')),
                            'default' => (int)$loader->rows,
                            'min' => 0,
                            'step' => 1,
                        ],
                        'cols' => [
                            'type' => 'number',
                            'class' => 'span1',
                            'name' => 'cols',
                            'caption' => mb_strtolower($this->view->_('COLS_FROM_LEFT')),
                            'default' => (int)$loader->cols,
                            'min' => 0,
                            'step' => 1
                        ],
                    ],
                ]),
                'test' => [
                    'type' => 'checkbox',
                    'name' => 'test',
                    'caption' => $this->view->_('TEST_MODE'),
                    'default' => 1,
                ],
                'clear' => [
                    'type' => 'select',
                    'name' => 'clear',
                    'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS'),
                    'children' => [
                        [
                            'value' => PriceLoader::DELETE_PREVIOUS_MATERIALS_NONE,
                            'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS_NONE')
                        ],
                        [
                            'value' => PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY,
                            'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY')
                        ],
                        [
                            'value' => PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES,
                            'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES')
                        ],
                    ],
                ],
                'file' => [
                    'type' => 'file',
                    'name' => 'file',
                    'caption' => $this->view->_('FILE_TO_UPLOAD'),
                    'template' => 'cms/shop/loaders.fields.inc.php',
                ],
                'show' => new FieldSet([
                    'name' => 'show',
                    'caption' => $this->view->_('SHOW'),
                    'template' => 'cms/shop/loaders.fields.inc.php',
                    'children' => [
                        'show_log' => [
                            'type' => 'checkbox',
                            'name' => 'show_log',
                            'caption' => $this->view->_('LOG'),
                            'style' => 'margin: 0;',
                            'default' => 1,
                        ],
                        'show_data' => [
                            'type' => 'checkbox',
                            'name' => 'show_data',
                            'caption' => $this->view->_('DATA'),
                            'style' => 'margin: 0;',
                            'default' => 1,
                        ],
                    ]
                ]),
            ],
            'template' => 'loaders.tmp.php',
            'commit' => function ($form) use ($loader) {
                $form->meta['OUT'] = [];
                if ($loader->id) {
                    $file = [];
                    if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                        $file = $_FILES['file'];
                    }
                    $page = new Page((int)($_POST['cat_id'] ?? 0));
                    $test = (bool)($_POST['test'] ?? 0);
                    $clear = (int)($_POST['clear'] ?? 0);
                    $rows = (int)($_POST['rows'] ?? 0);
                    $cols = (int)($_POST['cols'] ?? 0);
                    ini_set('max_execution_time', 600);
                    $out = $loader->upload($file, $page, $test, $clear, $rows, $cols);
                    $form->meta['OUT'] = $out;
                }
            },
            'import' => function ($form) {
                $result = $form->importDefault();
                $result['clear'] = 0;
                $result['test'] = 1;
                return $result;
            },
            'oncommit' => 'is_null',
            'redirect' => 'is_null',
            'submitCaption' => $this->view->_('UPLOAD'),
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
