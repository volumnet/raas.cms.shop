<?php
/**
 * Форма обработки загрузчиков изображений
 */
namespace RAAS\CMS\Shop;

use RAAS\Form as RAASForm;

/**
 * Форма обработки загрузчиков изображений
 */
class ProcessImageLoaderForm extends RAASForm
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Imageloaders::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = [])
    {
        $loader = $params['loader'] ?? null;

        $defaultParams = [
            'caption' => $this->view->_('IMAGELOADERS'),
            'parentUrl' => Sub_Imageloaders::i()->url . '&action=imageloaders',
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
                    'template' => 'cms/shop/loaders.fields.inc.tmp.php',
                ],
                'image_field' => [
                    'name' => 'image_field',
                    'caption' => $this->view->_('IMAGE_FIELD'),
                    'export' => 'is_null',
                    'import' => 'is_null',
                    'template' => 'cms/shop/loaders.fields.inc.tmp.php',
                ],
                'filename_format' => [
                    'name' => 'filename_format',
                    'caption' => $this->view->_('FILENAME_FORMAT'),
                    'export' => 'is_null',
                    'import' => 'is_null',
                    'template' => 'cms/shop/loaders.fields.inc.tmp.php',
                ],
                'test' => [
                    'type' => 'checkbox',
                    'name' => 'test',
                    'caption' => $this->view->_('TEST_MODE'),
                    'default' => 1,
                ],
                'clear' => [
                    'type' => 'checkbox',
                    'name' => 'clear',
                    'caption' => $this->view->_('DELETE_PREVIOUS_IMAGES'),
                    'default' => 1,
                ],
                'file' => [
                    'type' => 'file',
                    'name' => 'file',
                    'caption' => $this->view->_('FILE_TO_UPLOAD'),
                    'class' => 'span3',
                    'multiple' => true,
                    'accept' => 'image/jpeg,image/png,image/gif,application/zip,application/x-compressed,application/x-zip-compressed,multipart/x-zip',
                    'template' => 'cms/shop/loaders.fields.inc.tmp.php',
                ],
                'show_log' => [
                    'type' => 'checkbox',
                    'name' => 'show_log',
                    'caption' => $this->view->_('SHOW_LOG'),
                    'style' => 'margin: 0;',
                    'default' => ($this->view->action == 'download') ? (int)($_GET['show_log'] ?? 0) : 1,
                ],
            ],
            'template' => 'loaders.tmp.php',
            'commit' => function ($form) use ($loader) {
                $form->meta['OUT'] = [];
                if ($loader->id) {
                    $files = [];
                    foreach (($_FILES['file']['tmp_name'] ?? []) as $key => $val) {
                        if (is_uploaded_file($val)) {
                            $files[] = [
                                'name' => $_FILES['file']['name'][$key],
                                'tmp_name' => $_FILES['file']['tmp_name'][$key],
                                'type' => $_FILES['file']['type'][$key],
                                'size' => $_FILES['file']['size'][$key],
                            ];
                        }
                    }
                    $test = (bool)($_POST['test'] ?? 0);
                    $clear = (bool)($_POST['clear'] ?? 0);
                    ini_set('max_execution_time', 600);
                    $out = $loader->upload($files, $test, $clear);
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
