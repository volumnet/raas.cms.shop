<?php
namespace RAAS\CMS\Shop;
use \RAAS\Redirector;

class Sub_Priceloaders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        $OUT = array();
        $OUT['CONTENT']['loaders'] = PriceLoader::getSet();
        $DATA = array();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $DATA = $_POST;
            $Loader = new PriceLoader((int)$_POST['loader']);
            if ($Loader->id) {
                $file = null;
                if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                    $file = $_FILES['file'];
                }
                $test = isset($_POST['test']);
                $clear = isset($_POST['clear']);
                $rows = isset($_POST['rows']) ? (int)$_POST['rows'] : 0;
                $cols = isset($_POST['cols']) ? (int)$_POST['cols'] : 0;
                $IN = $Loader->upload($file, $test, $clear, $rows, $cols);
            }
        } elseif ($this->action == 'download') {
            $Loader = new PriceLoader((int)$_GET['loader']);
            if ($Loader->id) {
                $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 0;
                $cols = isset($_GET['cols']) ? (int)$_GET['cols'] : 0;
                $IN = $Loader->download($rows, $cols);
            }
            $DATA['rows'] = (int)$_GET['rows'];
            $DATA['cols'] = (int)$_GET['cols'];
            $DATA['show_log'] = (int)$_GET['show_log'];
        } else {
            if ($OUT['CONTENT']['loaders']) {
                $loader = $OUT['CONTENT']['loaders'][0];
                $DATA['rows'] = (int)$loader->rows;
                $DATA['cols'] = (int)$loader->cols;
            }
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
                    'description' => $this->view->_(($_SERVER['REQUEST_METHOD'] == 'POST') ? 'PRICE_SUCCESSFULLY_UPLOADED' : 'PRICE_SUCCESSFULLY_DOWNLOADED')
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