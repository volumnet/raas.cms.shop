<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Page;

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


    public function __construct(array $params = array())
    {
        $view = $this->view;
        $t = Module::i();
        $p = new Page();
        $Item = $params['Item'] ?? null;
        $CONTENT['pages'] = array('Set' => $p->children);
        if ($CONTENT['loaders'] = PriceLoader::getSet()) {
            $loader = $CONTENT['loaders'][0];
        } else {
            $loader = new PriceLoader();
        }

        $defaultParams = array(
            'caption' => ($Item && $Item->id) ? $Item->name : $view->_('PRICELOADERS'),
            'parentUrl' => Sub_Priceloaders::i()->url . '&action=priceloaders',
            'action' => Sub_Priceloaders::i()->url . '&action=priceloaders',
            'data-role' => 'loader-form',
            'class' => 'form-horizontal',
            'children' => array(
                'loader' => array(
                    'type' => 'select',
                    'name' => 'loader',
                    'caption' => $this->view->_('LOADER'),
                    'class' => 'span4',
                    'children' => array(
                        'Set' => $CONTENT['loaders'],
                        'additional' => function($row) use ($view) {
                            $arr = array();
                            $col_names = json_encode(array_map(function($x) use ($row, $view) {
                                if (is_numeric($x->fid)) {
                                    $text = $x->Field->name;
                                } elseif ($x->fid == 'name') {
                                    $text = $view->_('NAME');
                                } elseif ($x->fid == 'urn') {
                                    $text = $view->_('URN');
                                } elseif ($x->fid == 'vis') {
                                    $text = $view->_('VISIBILITY');
                                } elseif ($x->fid == 'description') {
                                    $text = $view->_('DESCRIPTION');
                                } elseif ($x->fid == 'meta_title') {
                                    $text = $view->_('META_TITLE');
                                } elseif ($x->fid == 'meta_description') {
                                    $text = $view->_('META_DESCRIPTION');
                                } elseif ($x->fid == 'meta_keywords') {
                                    $text = $view->_('META_KEYWORDS');
                                } elseif ($x->fid == 'priority') {
                                    $text = $view->_('PRIORITY');
                                } else {
                                    $text = '';
                                }
                                $unique = ($x->fid == $row->ufid);
                                return array('text' => $text, 'unique' => $unique);
                            }, (array)$row->columns));
                            $arr['data-material-type'] = $row->Material_Type->name;
                            $arr['data-rows'] = (int)$row->rows;
                            $arr['data-cols'] = (int)$row->cols;
                            $arr['data-cat_id'] = (int)$row->cat_id;
                            if ($col_names) {
                                $arr['data-col-names'] = $col_names;
                            }
                            return $arr;
                        }
                    ),
                    'required' => true,
                    'default' => ($this->view->action == 'download') ? (isset($_GET['loader']) ? (int)$_GET['loader'] : 0) : (int)$loader->id
                ),
                'cat_id' => array(
                    'type' => 'select',
                    'name' => 'cat_id',
                    'caption' => $this->view->_('ROOT_CATEGORY'),
                    'children' => $CONTENT['pages'],
                    'class' => 'span4',
                    'default' => ($this->view->action == 'download') ? (isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0) : (int)$loader->cat_id,
                ),
                'rows' => array(
                    'type' => 'number',
                    'class' => 'span1',
                    'name' => 'rows',
                    'caption' => mb_strtolower($this->view->_('ROWS_FROM_TOP')),
                    'default' => ($this->view->action == 'download') ? (isset($_GET['rows']) ? (int)$_GET['rows'] : 0) : (int)$loader->rows,
                    'min' => 0,
                    'step' => 1,
                ),
                'cols' => array(
                    'type' => 'number',
                    'class' => 'span1',
                    'name' => 'cols',
                    'caption' => mb_strtolower($this->view->_('COLS_FROM_LEFT')),
                    'default' => ($this->view->action == 'download') ? (isset($_GET['cols']) ? (int)$_GET['cols'] : 0) : (int)$loader->cols,
                    'min' => 0,
                    'step' => 1
                ),
                'test' => array('type' => 'checkbox', 'name' => 'test', 'caption' => $this->view->_('TEST_MODE'), 'default' => 1),
                'clear' => array(
                    'type' => 'select',
                    'name' => 'clear',
                    'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS'),
                    'children' => array(
                        array('value' => PriceLoader::DELETE_PREVIOUS_MATERIALS_NONE, 'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS_NONE')),
                        array('value' => PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY, 'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY')),
                        array('value' => PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES, 'caption' => $this->view->_('DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES')),
                    )
                ),
                'file' => array('type' => 'file', 'name' => 'file', 'caption' => $this->view->_('FILE_TO_UPLOAD'), 'class' => 'span3'),
                'show_log' => array(
                    'type' => 'checkbox',
                    'name' => 'show_log',
                    'caption' => $this->view->_('LOG'),
                    'style' => 'margin: 0;',
                    'default' => 1,
                ),
                'show_data' => array('type' => 'checkbox', 'name' => 'show_data', 'caption' => $this->view->_('DATA'), 'style' => 'margin: 0;', 'default' => 1),
            ),
            'template' => 'loaders.tmp.php',
            'commit' => function ($Form) {
                $Loader = new PriceLoader((int)$_POST['loader']);
                $Form->meta['OUT'] = array();
                if ($Loader->id) {
                    $file = [];
                    if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                        $file = $_FILES['file'];
                    }
                    $Page = new Page((int)($_POST['cat_id'] ?? 0));
                    $test = isset($_POST['test']);
                    $clear = (int)($_POST['clear'] ?? 0);
                    $rows = (int)($_POST['rows'] ?? 0);
                    $cols = (int)($_POST['cols'] ?? 0);
                    ini_set('max_execution_time', 3600);
                    $IN = $Loader->upload($file, $Page, $test, $clear, $rows, $cols);
                    $Form->meta['OUT'] = $IN;
                }
            },
            'oncommit' => 'is_null',
            'redirect' => 'is_null',
        );
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}
