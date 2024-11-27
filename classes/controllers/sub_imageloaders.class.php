<?php
/**
 * Контроллер подмодуля загрузчиков изображений
 */
namespace RAAS\CMS\Shop;

class Sub_Imageloaders extends \RAAS\Abstract_Sub_Controller
{
    protected static $instance;
    
    public function run()
    {
        $loader = new ImageLoader($this->id);
        if ($loader->id) {
            if ($this->action == 'download') {
                ini_set('max_execution_time', 600);
                $loader->download();
            } else {
                $this->upload($loader);
            }
        } else {
            $this->view->showlist(['Set' => ImageLoader::getSet()]);
        }
    }


    /**
     * Отображает параметры загрузки загрузчика изображений
     */
    protected function upload(ImageLoader $loader)
    {
        $form = new ProcessImageLoaderForm(['loader' => $loader]);
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
                    'IMAGES_SUCCESSFULLY_UPLOADED' :
                    'IMAGES_SUCCESSFULLY_DOWNLOADED'
                )
            ];
        }
        if (isset($in['log']) && $out['DATA']['show_log']) {
            $out['log'] = (array)$in['log'];
        }
        $out['url'] = $this->url;
        $out['loader'] = $loader;
        $this->view->upload($out);
    }
}
