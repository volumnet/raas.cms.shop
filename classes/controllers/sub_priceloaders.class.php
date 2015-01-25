<?php
namespace RAAS\CMS\Shop;
use \RAAS\Redirector;
use \RAAS\CMS\Page;

class Sub_Priceloaders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        $Form = new ProcessPriceLoaderForm();
        $OUT = $Form->process();
        if ($this->action == 'download') {
            $Loader = new PriceLoader((int)$_GET['loader']);
            if ($Loader->id) {
                $rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 0;
                $cols = isset($_GET['cols']) ? (int)$_GET['cols'] : 0;
                $Page = isset($_GET['cat_id']) ? (new Page((int)$_GET['cat_id'])) : $Loader->Page;
                $type = isset($_GET['type']) ? trim($_GET['type']) : '';
                $encoding = isset($_GET['encoding']) ? trim($_GET['encoding']) : '';
                $IN = $Loader->download($Page, $rows, $cols, $type, $encoding);
            }
            $OUT['DATA']['rows'] = (int)$_GET['rows'];
            $OUT['DATA']['cols'] = (int)$_GET['cols'];
            $OUT['DATA']['show_log'] = (int)$_GET['show_log'];
        } else {
            $IN = isset($Form->meta['OUT']) ? $Form->meta['OUT'] : array();
        }
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
        if (isset($IN['log']) && $OUT['DATA']['show_log']) {
            $OUT['log'] = (array)$IN['log'];
        }
        if (isset($IN['raw_data']) && $OUT['DATA']['show_data']) {
            $OUT['raw_data'] = (array)$IN['raw_data'];
        }
        $OUT['url'] = $this->url;
        $this->view->main($OUT);
    }
}