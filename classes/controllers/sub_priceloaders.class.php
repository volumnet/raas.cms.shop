<?php
/**
 * Контроллер подмодуля загрузчиков прайсов
 */
namespace RAAS\CMS\Shop;

use SOME\HTTP;
use SOME\Pages;
use RAAS\Application;
use RAAS\Redirector;
use RAAS\StdSub;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Page;
use RAAS\CMS\PageRecursiveCache;

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
                if ((
                    ($loader->interface_classname == PriceloaderInterface::class) ||
                    is_subclass_of($loader->interface_classname, PriceloaderInterface::class)
                ) && $loader->step_interface) {
                    $this->stepUpload($loader);
                } else {
                    $this->upload($loader);
                }
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
     * Пошаговая загрузка (новый интерфейс)
     * @param PriceLoader $loader Загрузчик
     */
    public function stepUpload(PriceLoader $loader)
    {
        $out = [];
        $interfaceClassname = $loader->interface_classname;
        $interface = new $interfaceClassname($loader);
        $datafile = $this->getStepDataFilename($loader);
        $data = $interface->loadStepData($datafile);
        $step = min($_GET['step'] ?? 0, $data['step'] ?? 0);
        if (!$step) {
            $interface->clearStepData($datafile);
            $data = [];
        }
        $localError = [];
        $loaderArr = ['id' => (int)$loader->id];
        if (($_SERVER['REQUEST_METHOD'] ?? null) == 'POST') {
            $result = [];
            switch ($step) {
                case 2:
                    $result = $interface->stepApply($data);
                    break;
                case 1:
                    $result = $interface->stepMatching(
                        $data,
                        (int)($_POST['cat_id'] ?? 0),
                        (int)($_POST['rows'] ?? 0),
                        (array)$_POST['columns']
                    );
                    break;
                default:
                    $filename = $type = '';
                    if (($_FILES['file']['tmp_name'] ?? null) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                        $filename = $_FILES['file']['tmp_name'];
                        $type = strtolower(pathinfo((string)$_FILES['file']['name'], PATHINFO_EXTENSION));
                    }
                    $result = $interface->stepUpload($filename, $type);
                    break;
            }
            $newData = ($result['data'] ?? null);
            if ($newData) {
                $data = $newData;
                $interface->saveStepData($datafile, $data);
            }
            if ($result['localError'] ?? null) {
                $localError = $result['localError'];
            } elseif ($newData) {
                $nextStep = (int)$data['step'];
                new Redirector(HTTP::queryString('step=' . $nextStep));
            }
        }

        if (in_array($step, [1, 2, 3])) {
            $loaderArr['ufid'] = (string)$loader->ufid;
            foreach ($loader->columns as $column) {
                if ($column->fid) {
                    $columnData = [
                        'id' => (int)$column->id,
                        'fid' => $column->fid,
                    ];
                    if ($column->Field->id) {
                        $columnData['name'] = $column->Field->name;
                    } else {
                        $columnData['name'] = $this->view->_(mb_strtoupper($column->fid));
                    }
                    $loaderArr['columns'][(string)$columnData['id']] = $columnData;
                }
            }
        }
        switch ($step) {
            case 1:
                $loaderArr['fields']['cat_id'] = [
                    'source' => $this->getPagesSource(),
                ];
                $loaderArr['totalRows'] = count($data['rows']);
                $data['rows'] = array_slice($data['rows'] ?? [], 0, 100);
                break;
            case 2:
                break;
            case 3:
                break;
        }

        $out['localError'] = $localError;
        $out['step'] = $step;
        $out['data'] = $data;
        $out['loader'] = $loader;
        $out['loaderArr'] = $loaderArr;
        $this->view->stepUpload($out);
    }


    /**
     * Получает список категорий для выпадающего меню
     * @param int $catId ID# категории, для которой определяем дочерние
     * @return array <pre><code>array<[
     *     'value' => int ID# категории,
     *     'text' => string Название категории,
     *     'children' =>? array дочерние элементы (рекурсивно)
     * ]></code></pre>
     */
    public function getPagesSource($catId = 0): array
    {
        $result = [];
        $children = PageRecursiveCache::i()->getChildrenCache($catId);
        foreach ($children as $child) {
            $resultEntry = [
                'value' => (int)$child['id'],
                'caption' => $child['name'],
                'name' => $child['name'],
            ];
            if ($ch = $this->getPagesSource((int)$child['id'])) {
                $resultEntry['children'] = $ch;
            }
            $result[] = $resultEntry;
        }
        return $result;
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


    /**
     * Получает имя файла для хранения данных пошаговой загрузки
     * @param PriceLoader $loader Загрузчик
     * @return string
     */
    public function getStepDataFilename(PriceLoader $loader): string
    {
        $user = Application::i()->user;
        $dirname = Application::i()->baseDir . '/cache/system';
        $result = $dirname . '/priceloader' . (int)$loader->id . '_' . (int)$user->id . '.php';
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        return $result;
    }
}
