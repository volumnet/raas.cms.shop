<?php
namespace RAAS\CMS\Shop;

class Sub_Imageloaders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        $OUT = array();
        $OUT['CONTENT']['loaders'] = ImageLoader::getSet();
        $DATA = array();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $DATA = $_POST;
            $Loader = new ImageLoader((int)$_POST['loader']);
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
                $clear = isset($_POST['clear']);
                $IN = $Loader->upload($files, $test, $clear);
            }
        } elseif ($this->action == 'download') {
            $Loader = new ImageLoader((int)$_GET['loader']);
            if ($Loader->id) {
                $IN = $Loader->download();
            }
        } else {
            $DATA['show_log'] = 1;
        }
        if (isset($IN)) {
            if (isset($IN['localError'])) {
                $OUT['localError'] = $IN['localError'];
            } elseif ($IN === false) {
                $OUT['localError'] = array(
                    array(
                        'name' => 'INVALID', 
                        'value' => 'file', 
                        'description' => $this->view->_(
                            ($_SERVER['REQUEST_METHOD'] == 'POST') ? 'ERR_SOME_ERROR_DUE_UPLOADING' : 'ERR_SOME_ERROR_DUE_DOWNLOADING'
                        )
                    )
                );
            }
            if (isset($IN['localSuccess'])) {
                $OUT['localSuccess'] = $IN['localSuccess'];
            } elseif (isset($IN['ok']) || ($IN === true)) {
                $OUT['localSuccess'] = array(
                    'name' => 'SUCCESS', 
                    'value' => 'file', 
                    'description' => $this->view->_(($_SERVER['REQUEST_METHOD'] == 'POST') ? 'IMAGES_SUCCESSFULLY_UPLOADED' : 'IMAGES_SUCCESSFULLY_DOWNLOADED')
                );
            }
            if (isset($IN['log']) && $DATA['show_log']) {
                $OUT['log'] = (array)$IN['log'];
            }
            if (isset($IN['data']) && $DATA['show_data']) {
                $OUT['raw_data'] = (array)$IN['data'];
            }
        }
        $OUT['DATA'] = $DATA;
        $OUT['url'] = $this->url;
        $this->view->main($OUT);
    }
}