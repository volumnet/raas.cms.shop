<?php
/**
 * Форма скачивания прайс-листа
 */
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;
use RAAS\CMS\Page;

/**
 * Форма скачивания прайс-листа
 */
class DownloadPriceLoaderForm extends \RAAS\Form
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
            'caption' => $this->view->_('DOWNLOAD') . ': ' . $loader->name,
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
                    'required' => true,
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
                'type' => [
                    'type' => 'select',
                    'name' => 'type',
                    'caption' => $this->view->_('DOWNLOAD_PRICE'),
                    'default' => 'xlsx',
                    'required' => true,
                    'children' => [
                        ['value' => 'xlsx', 'caption' => $this->view->_('EXCEL2007')],
                        ['value' => 'xls', 'caption' => $this->view->_('EXCEL')],
                        ['value' => 'csv_win1251', 'caption' => $this->view->_('CSV_WIN1251')],
                        ['value' => 'csv', 'caption' => $this->view->_('CSV_UTF8')],
                    ],
                ],
            ],
            'commit' => function ($form) use ($loader) {
                $page = isset($_POST['cat_id']) ? (new Page((int)$_POST['cat_id'])) : $loader->Page;
                $rows = (int)($_POST['rows'] ?? 0);
                $cols = (int)($_POST['cols'] ?? 0);
                $type = trim((string)($_POST['type'] ?? ''));
                $encoding = '';
                if ($type == 'csv_win1251') {
                    $type = 'csv';
                    $encoding = 'Windows-1251';
                }
                ini_set('max_execution_time', 3600);
                $in = $loader->download($page, $rows, $cols, $type, $encoding);
            },
            'oncommit' => 'is_null',
            'redirect' => 'is_null',
            'submitCaption' => $this->view->_('DOWNLOAD'),
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
