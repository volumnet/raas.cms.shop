<?php
/**
 * Контроллер подмодуля загрузчиков прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

/**
 * Контроллер подмодуля загрузчиков прайсов
 */
class Sub_Priceloaders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;

    public function run()
    {
        $loader = new PriceLoader($this->id);
        if ($loader->id) {
            if ($this->action == 'download') {
                $this->download($loader);
            } else {
                $this->upload($loader);
            }
        } else {
            $this->view->showlist(['Set' => PriceLoader::getSet()]);
        }
    }


    /**
     * Загрузка прайс-листа
     * @param PriceLoader $loader Загрузчик
     */
    protected function upload(PriceLoader $loader)
    {
        $form = new ProcessPriceLoaderForm(['loader' => $loader]);
        $out = $form->process();
        $in = (array)($form->meta['OUT'] ?? []);
        if (isset($in['localError'])) {
            $out['localError'] = $in['localError'];
        } elseif ($in === false) {
            $out['localError'] = [
                [
                    'name' => 'INVALID',
                    'value' => 'file',
                    'description' => $this->view->_(
                        ($_SERVER['REQUEST_METHOD'] == 'POST') ?
                        'ERR_SOME_ERROR_DUE_UPLOADING' :
                        'ERR_SOME_ERROR_DUE_DOWNLOADING'
                    )
                ]
            ];
        }
        if (isset($in['localSuccess'])) {
            $out['localSuccess'] = $in['localSuccess'];
        } elseif (isset($in['ok']) || ($in === true)) {
            $out['localSuccess'] = [
                'name' => 'SUCCESS',
                'value' => 'file',
                'description' => $this->view->_(
                    ($_SERVER['REQUEST_METHOD'] == 'POST') ?
                    'PRICE_SUCCESSFULLY_UPLOADED' :
                    'PRICE_SUCCESSFULLY_DOWNLOADED'
                )
            ];
        }
        if (isset($in['log']) && $out['DATA']['show_log']) {
            $out['log'] = (array)$in['log'];
        }
        if (isset($in['raw_data']) && $out['DATA']['show_data']) {
            $out['raw_data'] = (array)$in['raw_data'];
        }
        $out['url'] = $this->url;
        $out['loader'] = $loader;
        $this->view->upload($out);
    }


    /**
     * Скачивание прайс-листа
     * @param PriceLoader $loader Загрузчик
     */
    public function download(PriceLoader $loader)
    {
        $form = new DownloadPriceLoaderForm(['loader' => $loader]);
        $out = $form->process();
        $out['loader'] = $loader;
        $this->view->download($out);
    }
}
