<?php
namespace RAAS\CMS\Shop;

class ProcessImageLoaderForm extends \RAAS\Form
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


    public function __construct(array $params = array())
    {
        $view = $this->view;
        $t = Module::i();
        if ($CONTENT['loaders'] = ImageLoader::getSet()) {
            $loader = $CONTENT['loaders'][0];
        } else {
            $loader = new ImageLoader();
        }

        $defaultParams = array(
            'caption' => $Item->id ? $Item->name : $view->_('IMAGELOADERS'),
            'parentUrl' => Sub_Imageloaders::i()->url . '&action=imageloaders',
            'action' => Sub_Imageloaders::i()->url . '&action=imageloaders',
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
                            if (is_numeric($row->ufid)) {
                                $text = $row->Unique_Field->name;
                            } elseif ($row->ufid == 'name') {
                                $text = $view->_('NAME');
                            } elseif ($row->ufid == 'urn') {
                                $text = $view->_('URN');
                            } elseif ($row->ufid == 'description') {
                                $text = $view->_('DESCRIPTION');
                            } else {
                                $text = '';
                            }
                            $file_format = ($text ? '[' . $text . ']' . $row->sep_string : '') . '[' . $view->_('FILENAME') . '].(jpg|gif|png)';
                            $arr['data-material-type'] = $row->Material_Type->name;
                            $arr['data-image-field'] = $row->Image_Field->name;
                            if ($file_format) {
                                $arr['data-file-format'] = $file_format;
                            }
                            return $arr;
                        }
                    ), 
                    'required' => true, 
                    'default' => ($this->view->action == 'download') ? (isset($_GET['loader']) ? (int)$_GET['loader'] : 0) : (int)$loader->id
                ),
                'test' => array('type' => 'checkbox', 'name' => 'test', 'caption' => $this->view->_('TEST_MODE'), 'default' => 1),
                'clear' => array('type' => 'checkbox', 'name' => 'clear', 'caption' => $this->view->_('DELETE_PREVIOUS_IMAGES'), 'default' => 1),
                'file' => array(
                    'type' => 'file', 
                    'name' => 'file', 
                    'caption' => $this->view->_('FILE_TO_UPLOAD'), 
                    'class' => 'span3',
                    'multiple' => true,
                    'accept' => 'image/jpeg,image/png,image/gif,application/zip,application/x-compressed,application/x-zip-compressed,multipart/x-zip'
                ),
                'show_log' => array(
                    'type' => 'checkbox', 
                    'name' => 'show_log', 
                    'caption' => $this->view->_('SHOW_LOG'), 
                    'style' => 'margin: 0;', 
                    'default' => ($this->view->action == 'download') ? (isset($_GET['show_log']) ? (int)$_GET['show_log'] : 0) : 1, 
                ),
            ),
            'template' => 'loaders.tmp.php',
            'commit' => function($Form) {
                $Loader = new ImageLoader((int)$_POST['loader']);
                $Form->meta['OUT'] = array();
                if ($Loader->id) {
                    $files = array();
                    foreach ($_FILES['file']['tmp_name'] as $key => $val) {
                        if (is_uploaded_file($val)) {
                            $files[] = array(
                                'name' => $_FILES['file']['name'][$key], 
                                'tmp_name' => $_FILES['file']['tmp_name'][$key], 
                                'type' => $_FILES['file']['type'][$key],
                                'size' => $_FILES['file']['size'][$key],
                            );
                        }
                    }
                    $test = isset($_POST['test']);
                    $clear = isset($_POST['clear']) ? (int)$_POST['clear'] : 0;
                    $IN = $Loader->upload($files, $test, $clear);
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