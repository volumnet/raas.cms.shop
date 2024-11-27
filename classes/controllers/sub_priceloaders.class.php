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
        if (in_array($this->action, [
            'vis_unaffected_material',
            'invis_unaffected_material',
            'delete_unaffected_material',
            'vis_unaffected_page',
            'invis_unaffected_page',
            'delete_unaffected_page',
        ])) {
            $loader = new PriceLoader($this->nav['pid'] ?? 0);
            if (!$loader->id) {
                exit;
            }

            $interfaceClassname = $loader->interface_classname;
            $interface = new $interfaceClassname($loader);
            $datafile = $this->getStepDataFilename($loader);
            $data = $interface->loadStepData($datafile);
            $ids = (array)($_GET['id'] ?? []);
            $items = [];
            $affectedIds = [];
            $hashTag = '';
            if (stristr($this->action, 'material')) {
                $affectedIds = $interface->getStepAffectedMaterialsIds($data);
                $classname = Material::class;
                $hashTag = '#materials';
                if (in_array('all', $ids)) {
                    $allLoaderPagesIds = PageRecursiveCache::i()->getSelfAndChildrenIds((int)$data['rootCategoryId']);
                    $allLoaderMaterialTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($loader->mtype);
                    $sqlQuery = "SELECT DISTINCT tM.id
                                   FROM " . Material::_tablename() . " AS tM
                                   JOIN cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id
                                  WHERE tM.pid IN (" . implode(", ", $allLoaderMaterialTypesIds) . ")
                                    AND tMPA.pid IN (" . implode(", ", $allLoaderPagesIds) . ")";
                    $ids = Material::_SQL()->getcol($sqlQuery);
                }
            } elseif (stristr($this->action, 'page')) {
                $affectedIds = $interface->getStepAffectedPagesIds($data);
                $classname = Page::class;
                $hashTag = '#pages';
            }
            if (in_array('all', $ids)) {
                $ids = [];
                if (stristr($this->action, 'material')) {
                    // @todo по всем материалам
                } elseif (stristr($this->action, 'page')) {
                    // @todo по всем страницам
                }
            }
            $ids = array_map('intval', $ids);
            $ids = array_values(array_diff($ids, $affectedIds));
            if ($ids) {
                $items = $classname::getSet([
                    'where' => ["id IN (" . implode(", ", $ids) . ")"],
                    'orderBy' => 'id',
                ]);
            }
            $f = null;
            foreach (['vis', 'invis', 'delete'] as $actionName) {
                if (preg_match('/^' . $actionName . '_/umis', $this->action)) {
                    $f = $actionName;
                    break;
                }
            }
            if ($f) {
                if (isset($_GET['back'])) {
                    $returnUrl = 'history:back';
                } else {
                    $returnUrl = $this->url . '&action=unaffected&id=' . (int)$loader->id;
                }
                $returnUrl .= $hashTag;
                // var_dump($f, $items); exit;
                StdSub::$f($items, $returnUrl, false);
            }
            exit;
        } else {
            $loader = new PriceLoader($this->id);
            if ($loader->id) {
                if ($this->action == 'download') {
                    $this->download($loader);
                } elseif ($this->action == 'unaffected') {
                    $this->unaffected($loader);
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
                $affectedMaterialsIds = $interface->getStepAffectedMaterialsIds($data);
                $affectedPagesIds = $interface->getStepAffectedPagesIds($data);

                $allLoaderPagesIds = PageRecursiveCache::i()->getSelfAndChildrenIds((int)$data['rootCategoryId']);
                $allLoaderMaterialTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($loader->mtype);

                $loaderArr['unaffectedMaterialsCount'] = 0;
                if ($allLoaderPagesIds && $allLoaderMaterialTypesIds) {
                    $sqlQuery = "SELECT COUNT(DISTINCT tM.id)
                                   FROM " . Material::_tablename() . " AS tM
                                   JOIN cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id
                                  WHERE tM.pid IN (" . implode(", ", $allLoaderMaterialTypesIds) . ")
                                    AND tMPA.pid IN (" . implode(", ", $allLoaderPagesIds) . ")";
                    if ($affectedMaterialsIds) {
                        $sqlQuery .= " AND tM.id NOT IN (" . implode(", ", $affectedMaterialsIds) . ")";
                    }
                    $sqlResult = Material::_SQL()->getvalue($sqlQuery);
                    $loaderArr['unaffectedMaterialsCount'] = (int)$sqlResult;
                }
                $loaderArr['unaffectedPagesCount'] = count(array_diff($allLoaderPagesIds, $affectedPagesIds));
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
     * Обработка незадействованных материалов и страниц
     * @param PriceLoader $loader Загрузчик
     */
    public function unaffected(PriceLoader $loader)
    {
        $interfaceClassname = $loader->interface_classname;
        $interface = new $interfaceClassname($loader);
        $datafile = $this->getStepDataFilename($loader);
        $data = $interface->loadStepData($datafile);
        $affectedMaterialsIds = $interface->getStepAffectedMaterialsIds($data);
        $affectedPagesIds = $interface->getStepAffectedPagesIds($data);
        $materialType = $loader->Material_Type;
        $materialTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($loader->mtype);
        $allLoaderPagesIds = PageRecursiveCache::i()->getSelfAndChildrenIds((int)$data['rootCategoryId']);


        $out = [];
        $localError = [];
        if (($data['step'] ?? 0) < 3) {
            $localError[] = [
                'name' => 'INVALID',
                'value' => 'data',
                'description' => $this->view->_('YOU_CAN_VIEW_UNAFFECTED_ONLY_AFTER_PRICE_APPLYING'),
            ];
        }

        // Материалы
        $searchString = $this->nav['search_string'] ?? '';
        $pageNum = $this->nav['page'] ?? 1;
        $sort = $this->nav['sort'] ?? 'post_date';
        $order = $this->nav['order'] ?? 'asc';
        $columns = array_filter(
            $materialType->fields,
            function ($x) {
                return $x->show_in_table;
            }
        );

        $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS tM.* ";
        if (!$materialType->global_type) {
            $sqlQuery .= ", (
                                SELECT COUNT(tMPA2.pid)
                                  FROM " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA2
                                 WHERE id = tM.id
                            ) AS pages_counter";
        }

        $sqlQuery .= " FROM " . Material::_tablename() . " AS tM ";
        // 2016-01-14, AVS: добавил поиск по данным
        $sqlQuery .= " WHERE tM.pid IN (" . implode(", ", $materialTypesIds) . ")";
        if ($affectedMaterialsIds) {
            $sqlQuery .= " AND tM.id NOT IN (" . implode(", ", $affectedMaterialsIds) . ")";
        }
        if (!$materialType->global_type) {
            $sqlQuery .= " AND (
                                SELECT COUNT(*)
                                  FROM " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA
                                 WHERE tMPA.id = tM.id
                                   AND tMPA.pid IN (" . implode(", ", $allLoaderPagesIds) . ")
                            )";
        }
        // 2016-01-14, AVS: добавил поиск по данным
        if ($searchString) {
            $likeSearchString = Application::i()->SQL->real_escape_string($searchString);
            $sqlQuery .= "  AND (
                                    tM.id = '" . $likeSearchString . "'
                                 OR tM.name LIKE '%" . $likeSearchString . "%'
                                 OR tM.description LIKE '%" . $likeSearchString . "%'
                                 OR tM.urn LIKE '%" . $likeSearchString . "%'
                                 OR (
                                        SELECT COUNT(*)
                                          FROM " . Material::_dbprefix() . Material_Field::DATA_TABLE . " AS tD
                                         WHERE tD.pid = tM.id
                                           AND tD.value LIKE '%" . $likeSearchString . "%'
                                )
                            )";
        }
        $sqlQuery .= " GROUP BY tM.id
                       ORDER BY NOT tM.priority ASC, tM.priority ASC";
        $pages = new Pages($pageNum, Application::i()->registryGet('rowsPerPage'));
        if (isset($sort, $columns[$sort]) && ($row = $columns[$sort])) {
            $reverse = (isset($order) && ($order == 'desc'));
            $_order = $reverse ? 'desc' : 'asc';
            $ids = Package::i()->getCompareSQL($row, $reverse);
            if ($ids) {
                $sqlQuery .= ", FIELD(tM.id, " . implode(", ", $ids) . ")";
            }
        } else {
            switch ($sort) {
                case 'name':
                case 'urn':
                case 'modify_date':
                    $_sort = 'tM.' . $sort;
                    break;
                default:
                    $sort = 'post_date';
                    $_sort = 'tM.post_date';
                    break;
            }
            if (isset($order) && ($order == 'desc')) {
                $_order = 'desc';
            } elseif (!isset($order) && in_array($sort, ['post_date', 'modify_date'])) {
                $_order = 'desc';
            } else {
                $_order = 'asc';
            }
            $sqlQuery .= ", " . $_sort . " " . strtoupper($_order);
        }
        $Set = Material::getSQLSet($sqlQuery, $pages);
        $out['Set'] = $Set;
        $out['Pages'] = $pages;
        $out['sort'] = $sort;
        $out['order'] = $_order;
        $out['mtype'] = $materialType;
        $out['searchString'] = $searchString;
        $out['pagesSource'] = $this->getUnaffectedPagesSource((int)$data['rootCategoryId'], $affectedPagesIds);

        $out['localError'] = $localError;
        $out['loader'] = $loader;
        $out['step'] = ($data['step'] ?? '');
        $this->view->unaffected($out);
    }


    /**
     * Получает список незадействованных категорий для дерева
     * @param int $catId ID# категории, для которой определяем дочерние
     * @param int[] $affectedPagesIds ID# задействованных категорий
     * @return array <pre><code>array<[
     *     'value' => int ID# категории,
     *     'text' => string Название категории,
     *     'children' =>? array дочерние элементы (рекурсивно)
     * ]></code></pre>
     */
    public function getUnaffectedPagesSource($catId = 0, array $affectedPagesIds = [], $level = 0): array
    {
        $result = [];
        $children = PageRecursiveCache::i()->getChildrenCache($catId);
        foreach ($children as $child) {
            $resultEntry = [
                'value' => (int)$child['id'],
                'caption' => $child['name'],
                'name' => $child['name'],
                'affected' => in_array((int)$child['id'], $affectedPagesIds),
            ];
            if ($ch = $this->getUnaffectedPagesSource((int)$child['id'], $affectedPagesIds, $level + 1)) {
                $resultEntry['children'] = $ch;
            }
            if (!$resultEntry['affected'] || $ch) {
                $result[] = $resultEntry;
            }
        }
        if (!$level) {
            $rootCache = PageRecursiveCache::i()->cache[$catId];
            $result = [[
                'value' => $catId,
                'caption' => $rootCache['name'],
                'name' => $rootCache['name'],
                'affected' => in_array((int)$child['id'], $affectedPagesIds),
                'children' => $result,
            ]];
        }
        return $result;
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
