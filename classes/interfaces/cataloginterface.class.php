<?php
/**
 * Файл стандартного интерфейса каталога
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\Pages;
use SOME\SOME;
use SOME\Text;
use RAAS\Attachment;
use RAAS\CMS\Block;
use RAAS\CMS\Block_Form;
use RAAS\CMS\Block_Material;
use RAAS\CMS\DiagTimer;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialInterface;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Page;
use RAAS\CMS\SearchInterface;

/**
 * Класс стандартного интерфейса каталога
 */
class CatalogInterface extends MaterialInterface
{
    /**
     * Класс фильтра каталога
     */
    const FILTER_CLASS = CatalogFilter::class;

    /**
     * Использовать (если нет дополнительной фильтрации) быстрый поиск
     * по ID# товаров, взятых из фильтра
     * @var bool
     */
    public $useFilterIds = true;

    /**
     * ID# товаров, полученных из фильтра (с учетом сортировки)
     * @var int[]
     */
    protected $filterIds = [];



    public function process(): array
    {
        $this->setCatalogFilter($this->block, $this->page, $this->get);
        $result = parent::process();
        return $result;
    }


    /**
     * Обрабатывает один материал
     * @param Block_Material $block Блок, для которого применяется интерфейс
     * @param Page $page Страница, для которой применяется интерфейс
     * @param Material $item Материал для обработки
     * @param array $get Поля $_GET параметров
     * @param array $server Поля $_SERVER параметров
     * @return array <pre><code>[
     *     'Item' => Material Обрабатываемый материал,
     *     'prev' ?=> Material Предыдущий материал,
     *     'next' ?=> Material Следующий материал,
     *     'commentFormBlock' ?=> Block_Form блок формы комментариев,
     *     'commentsListBlock' ?=> Block_Material блок списка комментариев,
     *     'comments' ?=> array<Material> список комментариев
     *     'commentsListText' ?=> string результат отработки блока
     *                            списка комментариев
     *     'rating' => int Рейтинг товара
     *     'faqFormBlock' ?=> Block_Form блок формы вопрос-ответ,
     *     'faqListBlock' ?=> Block_Material блок списка вопросов и ответов,
     *     'faq' ?=> array<Material> список вопросов и ответов
     *     'faqListText' ?=> string результат отработки блока
     *                       списка вопросов и ответов
     * ]</code></pre>
     */
    public function processMaterial(
        Block_Material $block,
        Page $page,
        Material $item,
        array $get = [],
        array $server = []
    ): array {
        $legacy = $this->checkLegacyArbitraryMaterialAddress($block, $page, $item, $server);
        if ($legacy) {
            return [];
        }
        $t = new DiagTimer();
        $this->setPageMetatags($page, $item, $block);
        $item->proceed = true;
        $result = ['Item' => $item];

        $prevNext = $this->getPrevNext($block, $page, $item, $get);
        foreach (['prev', 'next'] as $key) {
            if (isset($prevNext[$key])) {
                $result[$key] = $prevNext[$key];
            }
        }
        if ($resultComments = $this->processComments($block, $page, $item, 'commentFormBlock', 'commentsListBlock')) {
            foreach ([
                'commentFormBlock' => 'commentFormBlock',
                'commentsListBlock' => 'commentsListBlock',
                'comments' => 'comments',
                'commentsListText' => 'commentsListText'
            ] as $keyFrom => $keyTo) {
                if (isset($resultComments[$keyFrom])) {
                    $result[$keyTo] = $resultComments[$keyFrom];
                }
            }
            $rating = 0;
            $ratedReviews = 0;
            foreach ($result['comments'] as $comment) {
                if ($r = (int)$comment->rating) {
                    $rating += $r;
                    $ratedReviews++;
                }
            }
            if ($ratedReviews) {
                $rating /= $ratedReviews;
            }
            $result['rating'] = $rating;
        }
        if ($resultComments = $this->processComments($block, $page, $item, 'faqFormBlock', 'faqListBlock')) {
            foreach ([
                'commentFormBlock' => 'faqFormBlock',
                'commentsListBlock' => 'faqListBlock',
                'comments' => 'faq',
                'commentsListText' => 'faqListText'
            ] as $keyFrom => $keyTo) {
                if (isset($resultComments[$keyFrom])) {
                    $result[$keyTo] = $resultComments[$keyFrom];
                }
            }
        }
        $this->processVisited($item, $this->session);
        $t->stop();
        return $result;
    }


    /**
     * Устанавливает теги страницы
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param Material $item Текущий материал
     * @param Block_Material $block Блок, для которого применяется интерфейс
     */
    public function setPageMetatags(Page $page, Material $item, Block_Material $block = null)
    {
        parent::setPageMetatags($page, $item);
        $blockParams = [];
        if ($block) {
            $blockParams = $block->additionalParams;
        }
        if ($blockParams['metaTemplates']) {
            $metaTemplates = $metaData = [];
            foreach ([
                'name',
                'meta_title',
                'meta_keywords',
                'meta_description',
                'h1',
                'breadcrumbs_name',
                'menu_name'
            ] as $key) {
                if (!$item->$key) {
                    if (!$metaData) {
                        $metaData = $this->getItemMetadata($item);
                    }
                    if (!isset($metaTemplates[$key])) {
                        $metaTemplates[$key] = $this->getMetaTemplate($page, $key . '_' . $blockParams['metaTemplates']);
                    }
                    $page->$key = Text::renderTemplate($metaTemplates[$key], $metaData);
                }
            }
        }
    }


    /**
     * Устанавливает наследуемые шаблоны метатегов для листинга
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param Block_Material $block Блок, для которого применяется интерфейс
     */
    public function setListMetatags(Page $page, Block_Material $block = null)
    {
        $blockParams = [];
        if ($block) {
            $blockParams = $block->additionalParams;
        }
        if ($blockParams['listMetaTemplates'] ?? null) {
            $metaData = $this->getPageMetadata($page);
            $metaTemplates = [];
            foreach ([
                'name',
                'meta_title',
                'meta_keywords',
                'meta_description',
                'h1',
                'breadcrumbs_name',
                'menu_name'
            ] as $key) {
                if (!isset($metaTemplates[$key])) {
                    $metaTemplates[$key] = $this->getMetaTemplate($page, $key . '_' . $blockParams['listMetaTemplates']);
                }
            }
            foreach ($metaTemplates as $key => $val) {
                if (!$page->$key && $val) {
                    $page->$key = Text::renderTemplate($metaTemplates[$key], $metaData);
                }
            }
        }
    }


    public function processList(Block_Material $block, Page $page, array $get = []): array
    {
        $t = new DiagTimer();
        $result = parent::processList($block, $page, $get);
        $this->setListMetatags($page, $block);
        $doSearch = $this->isSearch($block, $page->catalogFilter, $get);
        $subcats = [];
        if ($page->visChildren && !$doSearch && $page->pid) {
            foreach ($page->visChildren as $child) {
                $child->counter = $page->catalogFilter->count($child);
                $subcats[] = $child;
            }
        }
        $result['subcats'] = $subcats;
        $result['doSearch'] = $doSearch;
        $t->stop();
        return $result;
    }


    public function getList(Block_Material $block, Page $page, array $get = [], Pages $pages = null): array
    {
        $st = microtime(true);
        $this->filterIds = $this->getFilterIds($block, $get, $page->catalogFilter);
        $sqlParts = $this->getSQLParts($block, $page, $get, $pages);
        $sqlQuery = $this->getSQLQuery($sqlParts['from'], $sqlParts['where'], $sqlParts['sort'], $sqlParts['order']);

        // var_dump(microtime(true) - $st, $sqlQuery, $sqlParts['bind']); exit;
        $st = microtime(true);
        if ($this->useFilterIds) {
            $set = Material::getSQLSet([$sqlQuery, $sqlParts['bind']]);
            // var_dump('aaa', microtime(true) - $st);
        } else {
            $set = Material::getSQLSet([$sqlQuery, $sqlParts['bind']], $pages);
            // var_dump('bbb', microtime(true) - $st);
        }
        // print_r($sqlQuery);
        // var_dump($sqlParts);
        // exit;
        $set = array_filter($set, function ($x) {
            return $x->currentUserHasAccess();
        });
        return $set;
    }


    public function getIdsList(Block_Material $block, Page $page, array $get = []): array
    {
        $this->filterIds = $this->getFilterIds($block, $get, $page->catalogFilter);
        return parent::getIdsList($block, $page, $get);
    }


    /**
     * Получает части SQL-выражения
     * @param Block_Material|null $block Блок, для которого применяется
     *                                   интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param Pages|null $pages Постраничная разбивка
     * @return array <pre><code>[
     *     'from' => array<
     *         string[] псевдоним поля => string SQL-инструкция по выборке таблицы
     *     > Список подключаемых таблиц,
     *     'where' => array<string SQL-инструкция> Ограничения для SQL WHERE,
     *     'sort' => string Сортировка для SQL ORDER BY
     *     'order' => ""|"ASC"|"DESC" Порядок сортировки для SQL ORDER BY,
     *     'bind' => array<mixed Значение связки> Связки для SQL-выражения
     * ]</code></pre>
     */
    public function getSQLParts(Block_Material $block, Page $page, array $get = [], Pages $pages = null): array
    {
        $st = microtime(true);

        $sqlFromAccess = $sqlFromBindAccess = $sqlWhereAccess = [];
        $sqlFromMaterials =  $sqlWhereMaterials = $sqlWhereBindMaterials = [];
        $sqlFromFilter = $sqlFromBindFilter = $sqlWhereFilter = $sqlWhereBindFilter = [];
        $sqlFromOrder = $sqlFromBindOrder = [];

        // $sqlFrom = $sqlFromBind = $sqlWhere = $sqlWhereBind = [];

        $sqlSort = $sqlOrder = "";
        $this->getListAccessSQL($sqlFromAccess, $sqlFromBindAccess, $sqlWhereAccess);
        $this->getMaterialsSQL(
            $block,
            $page,
            $sqlFromMaterials,
            $sqlWhereMaterials,
            $sqlWhereBindMaterials,
            $this->filterIds // Добавился параметр относительно MaterialInterface::getMaterialsSQL
        );
        $nativeFilter = array_values(array_filter(
            (array)$block->filter,
            function ($x) {
                return !is_numeric($x['field']);
            }
        ));
        if ($nativeFilter) {
            $this->useFilterIds = false;
            $this->getFilteringSQL(
                $sqlFromFilter,
                $sqlFromBindFilter,
                $sqlWhereFilter,
                $sqlWhereBindFilter,
                (array)$nativeFilter,
                $get
            );
        }
        $this->getOrderSQL(
            $block,
            $get,
            $sqlFromOrder,
            $sqlFromBindOrder,
            $sqlSort,
            $sqlOrder,
            $this->filterIds // Добавился параметр относительно MaterialInterface::getOrderSQL
        );
        if ($this->useFilterIds && $pages) {
            $sqlFromMaterials =  $sqlWhereMaterials = $sqlWhereBindMaterials = [];
            $pagedFilterIds = SOME::getArraySet($this->filterIds, $pages);
            $this->getMaterialsSQL(
                $block,
                $page,
                $sqlFromMaterials,
                $sqlWhereMaterials,
                $sqlWhereBindMaterials,
                $pagedFilterIds // Добавился параметр относительно MaterialInterface::getMaterialsSQL
            );
            $sqlFromOrder = $sqlFromBindOrder = [];
            $sqlSort = $sqlOrder = "";
            $this->getOrderSQL(
                $block,
                $get,
                $sqlFromOrder,
                $sqlFromBindOrder,
                $sqlSort,
                $sqlOrder,
                $pagedFilterIds // Добавился параметр относительно MaterialInterface::getOrderSQL
            );
        }
        $sqlFrom = array_merge(
            $sqlFromAccess,
            $sqlFromMaterials,
            $sqlFromFilter,
            $sqlFromOrder
        );
        $sqlFromBind = array_merge(
            $sqlFromBindAccess,
            $sqlFromBindFilter,
            $sqlFromBindOrder
        );
        $sqlWhere = array_merge(
            $sqlWhereAccess,
            $sqlWhereMaterials,
            $sqlWhereFilter
        );
        $sqlWhereBind = array_merge(
            $sqlWhereBindMaterials,
            $sqlWhereBindFilter
        );
        $result = [
            'from' => $sqlFrom,
            'where' => $sqlWhere,
            'sort' => $sqlSort,
            'order' => $sqlOrder,
            'bind' => array_merge($sqlFromBind, $sqlWhereBind),
        ];
        return $result;
    }


    /**
     * Получает SQL-инструкции по материалам
     * @param Block_Material|null $block Блок, для которого применяется
     *                                   интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array<
     *            string[] псевдоним поля => string SQL-инструкция
     *                                              по выборке таблицы
     *        > $sqlFrom Список подключаемых таблиц
     * @param array<string SQL-инструкция> $sqlWhere Ограничения для SQL WHERE
     * @param array<mixed Значение связки> $sqlWhereBind Связки для SQL WHERE
     * @param int[] $filterIds ID# товаров по фильтру
     */
    public function getMaterialsSQL(
        Block_Material $block,
        Page $page,
        array &$sqlFrom,
        array &$sqlWhere,
        array &$sqlWhereBind,
        array $filterIds = []
    ) {
        if ($filterIds) {
            $sqlWhere[] = "tM.id IN (" . implode(", ", array_fill(0, count($filterIds), "?")) . ")";
            $sqlWhereBind = array_merge($sqlWhereBind, $filterIds);
        } else {
            $sqlWhere[] = "0";
        }
    }


    /**
     * Получает список ID# товаров по фильтру и полнотекстовому поиску
     * с учетом сортировки
     * @param Block_Material|null $block Блок, для которого применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param CatalogFilter $catalogFilter Фильтр каталога
     * @return int[]
     */
    public function getFilterIds(Block_Material $block, array $get, CatalogFilter $catalogFilter)
    {
        $fullTextFilter = array_values(array_filter((array)$block->filter, function ($x) {
            return $x['relation'] == 'FULLTEXT';
        }));
        $fullTextIds = $this->getFullTextIds($fullTextFilter, $get);
        $filterIds = $this->getRawFilterIds($block, $get, $catalogFilter);
        if ($fullTextIds) {
            $result = array_values(array_intersect($fullTextIds, $filterIds));
        } else {
            $result = $filterIds;
        }
        return $result;
    }


    public function getFilteringItemSQL($sqlField, $relation, $val)
    {
        switch ($relation) {
            case 'FULLTEXT':
                return [];
                break;
            default:
                return parent::getFilteringItemSQL($sqlField, $relation, $val);
                break;
        }
    }


    /**
     * Получает список ID#, найденных при полнотекстовом поиске
     * @param array $filter <pre><code>array<[
     *     'var' => string Переменная для фильтрации
     *     'relation' => 'FULLTEXT' Отношение для фильтрации
     *     'field' => int ID# поля артикула
     * ]></code></pre> Данные по фильтрации
     * @param array $get Поля $_GET параметров
     * @return int[]|null Список ID# товаров (с учетом сортировки),
     *     либо null, если полнотекстовый поиск не использовался
     */
    public function getFullTextIds(array $filter = [], array $get = [])
    {
        foreach ((array)$filter as $filterItem) {
            if (isset(
                $filterItem['var'],
                $filterItem['relation'],
                $filterItem['field'],
                $get[$filterItem['var']]
            ) && $filterItem['relation'] == 'FULLTEXT') {
                $searchString = $get[$filterItem['var']];
                if (!$searchString) {
                    continue;
                }
                $mTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($this->block->material_type);
                $searchInterface = new SearchInterface();
                $searchArray = $searchInterface->getSearchArray($searchString);

                // 3. Ищем все материалы по имени и описанию
                $materialsNameDescriptionResult = $searchInterface->searchMaterialsByNameAndDescription(
                    $searchString,
                    $searchArray,
                    $mTypesIds
                );

                // 4. Ищем все материалы по данным
                $materialsDataResult = $searchInterface->searchMaterialsByData(
                    $searchString,
                    $searchArray,
                    $mTypesIds
                );
                $searchResult = $materialsNameDescriptionResult
                    + $materialsDataResult;
                arsort($searchResult);
                return array_keys($searchResult);
            }
        }
        return null;
    }


    /**
     * Получает список ID# товаров только по фильтру с учетом сортировки
     * @param Block_Material|null $block Блок, для которого применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param CatalogFilter $catalogFilter Фильтр каталога
     * @return int[]
     */
    public function getRawFilterIds(Block_Material $block, array $get, CatalogFilter $catalogFilter)
    {
        $sortVar = (string)$block->sort_var_name;
        $sortVal = ($get[$sortVar] ?? '');
        $orderVar = (string)$block->order_var_name;
        $sortParams = (array)$block->sort;
        $sortDefField = (string)$block->sort_field_default;
        $orderRelDefault = (string)$block->sort_order_default;
        if ($sortVar && $sortVal && $sortParams) {
            // Выберем подходящую запись
            // (у которой значение var совпадает со значением переменной сортировки $_GET)
            $sortItem = $this->getMatchingSortParam($sortVal, $sortParams, 'var');
            $orderRelation = (string)($sortItem['relation'] ?? '');
            if ($sortItem) {
                if ($sortItem['field'] == 'random') {
                    $ids = $catalogFilter->getIds();
                    shuffle($ids);
                    return $ids;
                } elseif (is_numeric($sortItem['field']) &&
                    isset($catalogFilter->properties[$sortItem['field']])
                ) {
                    $order = $this->getOrder($orderVar, $orderRelation, $get);
                    $order = ($order == 'desc' ? -1 : 1);
                    $urn = $catalogFilter->properties[$sortItem['field']]->urn;
                    $ids = $catalogFilter->getIds($urn, $order);
                    return $ids;
                }
            }
        }
        // Ни с чем не совпадает, но есть сортировка по умолчанию
        if ($sortDefField) {
            if ($sortDefField == 'random') {
                $ids = $catalogFilter->getIds();
                shuffle($ids);
                return $ids;
            } elseif (is_numeric($sortDefField) &&
                isset($catalogFilter->properties[$sortDefField])
            ) {
                $order = $this->getOrder($orderVar, $orderRelDefault, $get);
                $order = ($order == 'desc' ? -1 : 1);
                $urn = $catalogFilter->properties[$sortDefField]->urn;
                $ids = $catalogFilter->getIds($urn, $order);
                return $ids;
            }
        }
        $ids = $catalogFilter->getIds();
        return $ids;
    }


    /**
     * Получает SQL-инструкции по сортировке
     * @param Block_Material|null $block Блок, для которого применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array<
     *            string[] псевдоним поля => string SQL-инструкция по выборке таблицы
     *        > $sqlFrom Список подключаемых таблиц
     * @param array<mixed Значение связки> $sqlFromBind Связки для SQL FROM
     * @param string $sqlSort Сортировка для SQL ORDER BY
     * @param ""|"ASC"|"DESC" $sqlOrder Порядок сортировки для SQL ORDER BY
     * @param int[] $filterIds ID# товаров по фильтру
     */
    public function getOrderSQL(
        Block_Material $block,
        array $get,
        array &$sqlFrom,
        array &$sqlFromBind,
        &$sqlSort,
        &$sqlOrder,
        array $filterIds = []
    ) {
        $sortVar = (string)$block->sort_var_name;
        $sortVal = isset($get[$sortVar]) ? $get[$sortVar] : '';
        $orderVar = (string)$block->order_var_name;
        $sortParams = (array)$block->sort;
        $sortDefField = (string)$block->sort_field_default;
        $orderRelDefault = (string)$block->sort_order_default;
        $sqlSort = $sqlOrder = "";
        if ($filterIds) {
            $sqlSort = "FIELD(tM.id, " . implode(", ", array_map('intval', $filterIds)) . ")";
        }
        if ($sortVar && $sortVal && $sortParams) {
            // Выберем подходящую запись
            // (у которой значение var совпадает со значением переменной сортировки $_GET)
            $sortItem = $this->getMatchingSortParam($sortVal, $sortParams, 'var');
            $orderRelation = isset($sortItem['relation'])
                           ? (string)$sortItem['relation']
                           : '';
            if ($sortItem) {
                if (($sortItem['field'] != 'random') &&
                    !is_numeric($sortItem['field'])
                ) {
                    $sqlSort = $this->getField(
                        $sortItem['field'],
                        'tOr',
                        $sqlFrom,
                        $sqlFromBind
                    );
                    $sqlOrder = mb_strtoupper(
                        $this->getOrder($orderVar, $orderRelation, $get)
                    );
                    $this->useFilterIds = false;
                }
                return;
            }
        }
        // Ни с чем не совпадает, но есть сортировка по умолчанию
        if ($sortDefField) {
            if (($sortDefField != 'random') && !is_numeric($sortDefField)) {
                $sqlSort = $this->getField(
                    $sortDefField,
                    'tOr',
                    $sqlFrom,
                    $sqlFromBind
                );
                $sqlOrder = mb_strtoupper(
                    $this->getOrder($orderVar, $orderRelDefault, $get)
                );
                $this->useFilterIds = false;
            }
        }
    }


    /**
     * Обрабатывает блоки комментариев к товару
     * @param Block_Material|null $block Блок, для которого применяется интерфейс
     * @param Page $page Страница, для которой обрабатываются комментарии
     * @param Material $item Материал, для которого обрабатываются комментарии
     * @param string $formBlockVar Переменная в доп. параметрах,
     *                             где задается ID# блока формы комментариев
     * @param string $listBlockVar Переменная в доп. параметрах,
     *                             где задается ID# блока списка комментариев
     * @return array <pre><code>[
     *     'commentFormBlock' ?=> Block_Form блок формы комментариев,
     *     'commentsListBlock' ?=> Block_Material блок списка комментариев,
     *     'comments' ?=> array<Material> список комментариев
     *     'commentsListText' ?=> string результат отработки блока
     *                            списка комментариев
     * ]</code></pre>
     */
    public function processComments(
        Block_Material $block,
        Page $page,
        Material $item,
        string $formBlockVar = 'commentFormBlock',
        string $listBlockVar = 'commentsListBlock'
    ): array {
        $result = [];
        parse_str(trim($block->params), $blockParams);
        if (isset($blockParams[$formBlockVar])) {
            $commentFormBlock = Block::spawn($blockParams[$formBlockVar]);
            if (($commentFormBlock->id == $blockParams[$formBlockVar]) &&
                ($commentFormBlock instanceof Block_Form) &&
                ($commentFormBlock->Form->Material_Type->id)
            ) {
                $result['commentFormBlock'] = $commentFormBlock;
            }
        }
        if (isset($blockParams[$listBlockVar])) {
            $commentsListBlock = Block::spawn($blockParams[$listBlockVar]);
            if (($commentsListBlock->id == $blockParams[$listBlockVar]) &&
                ($commentsListBlock instanceof Block_Material) &&
                ($commentsListBlock->Material_Type->id)
            ) {
                $result['commentsListBlock'] = $commentsListBlock;

                $commentsListParams = [
                    'Page' => $page,
                    'Block' => $commentsListBlock,
                    'config' => $commentsListBlock->config,
                ];
                $commentsListData = [];
                if ($commentsListBlockInterfaceClassname = $commentsListBlock->interface_classname) {
                    $commentsListBlockInterface = new $commentsListBlockInterfaceClassname(
                        $commentsListBlock,
                        $page,
                        $this->get,
                        $this->post,
                        $this->cookie,
                        $this->session,
                        $this->server,
                        $this->files
                    );
                    $commentsListData = $commentsListBlockInterface->process();
                } elseif ($commentsListBlock->Interface->id) {
                    $commentsListData = $commentsListBlock->Interface->process($commentsListParams);
                }
                $commentsListData = array_merge((array)$commentsListData, (array)$commentsListParams);
                // 2021-08-22, AVS: если указана фильтрация на уровне блока
                // списка комментариев
                // (предполагается, что используется интерфейс комментариев),
                // то там уже есть своя фильтрация, и дополнительно фильтровать
                // не нужно
                if (!($commentsListBlock->additionalParams['materialFieldURN'] ?? null)) {
                    $commentsListData['Set'] = array_values(
                        array_filter(
                            (array)($commentsListData['Set'] ?? []),
                            function ($x) use ($item) {
                                return $this->commentsFilterFunction($x, $item);
                            }
                        )
                    );
                }
                $result['comments'] = $commentsListData['Set'];
                if ($commentsListBlock->Widget->id) {
                    ob_start();
                    $commentsListBlock->Widget->process($commentsListData);
                    $result['commentsListText'] = ob_get_clean();
                }
            }
        }
        return $result;
    }


    /**
     * Функция фильтрации комментариев по товару
     * @param Material $comment Комментарий
     * @param Material $item Товар, по которому фильтруем
     * @return bool
     */
    public function commentsFilterFunction(Material $comment, Material $item)
    {
        if (!$comment->material || !$comment->material->id || !$item->id) {
            return false;
        }
        return $comment->material->id == $item->id;
    }


    /**
     * Получает данные по товару для подстановки в шаблоны мета-тегов
     * @param Material $item Товар для получения данных
     * @return array<string[] => string>
     */
    public function getItemMetadata(Material $item)
    {
        $metaData = [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'urn' => $item->urn,
            'url' => $item->url,
            'h1' => $item->getH1(),
        ];
        foreach ($item->fields as $fieldURN => $field) {
            $val = $field->doRich();
            if ($val instanceof Material) {
                $val = $val->name;
            } elseif ($val instanceof Attachment) {
                $val = '';
            }
            $metaData[$fieldURN] = (string)$val;
        }
        return $metaData;
    }


    /**
     * Получает данные по странице для подстановки в шаблоны мета-тегов
     * @param Page $page Страница для получения данных
     * @return array<string[] => string>
     */
    public function getPageMetadata(Page $page)
    {
        $metaData = [
            'id' => $page->id,
            'name' => $page->name,
            'urn' => $page->urn,
            'url' => $page->url,
            'h1' => $page->h1,
            'menu_name' => $page->menu_name,
            'breadcrumbs_name' => $page->breadcrumbs_name,
        ];
        if ($page->catalogFilter) {
            $metaData['counter'] = $page->catalogFilter->count($page, true);
            $metaData['selfCounter'] = $page->catalogFilter->count($page, false);
            $priceField = ($page->catalogFilter->propertiesByURNs)['price'] ?? null;
            if ($priceField->id) {
                $priceValues = array_keys(($page->catalogFilter->propsMapping)[$priceField->id] ?? []);
                $priceValues = array_filter($priceValues);
                if ($priceValues) {
                    $metaData['price_from'] = min($priceValues);
                    $metaData['price_to'] = max($priceValues);
                }
            }
        }
        foreach ($page->fields as $fieldURN => $field) {
            $val = $field->doRich();
            if ($val instanceof Material) {
                $val = $val->name;
            } elseif ($val instanceof Attachment) {
                $val = '';
            }
            $metaData[$fieldURN] = (string)$val;
        }
        return $metaData;
    }


    /**
     * Получает рекурсивно шаблон метатегов для товара из категорий
     * @param Page $page Категория (изначально - родительская для товара)
     * @param string $templateFieldURN URN поля шаблона
     * @return string
     */
    public function getMetaTemplate(Page $page, $templateFieldURN)
    {
        if ($page->{$templateFieldURN}) {
            return $page->{$templateFieldURN};
        } elseif ($page->parent->id) {
            return $this->getMetaTemplate($page->parent, $templateFieldURN);
        }
        return '';
    }


    /**
     * Добавляет товар в список просмотренных (в начало) с записью в сессию
     * @param Material $item Товар для добавления в просмотренные
     * @param array<string[] => mixed> $session Переменная сессии
     * @return array<int ID# товара> список просмотренных товаров
     */
    public function processVisited(Material $item, array &$session)
    {
        $session['visited'] = (array)($session['visited'] ?? []);
        array_unshift($session['visited'], (int)$item->id);
        $session['visited'] = array_values(array_filter(array_unique(
            $session['visited']
        )));
        $_SESSION['visited'] = $session['visited'];
        return $session['visited'];
    }


    /**
     * Получает поля $_GET параметров для фильтрации
     * @param Block_Material $block Блок, для которого применяется интерфейс
     * @param Page $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @return array<string[] => mixed>
     */
    public function getFilterParams(Block_Material $block, CatalogFilter $catalogFilter, array $get = [])
    {
        $filterParams = $ignoredParams = [];
        $result = [];
        foreach ((array)$block->filter as $filterParam) {
            if (mb_substr($filterParam['var'], 0, 1) == '-') {
                $ignoredParams[] = mb_substr($filterParam['var'], 1);
            } elseif ($filterParam['relation'] != 'FULLTEXT') {
                $filterParams[$filterParam['var']] = $filterParam;
            }
        }

        foreach ($get as $key => $val) {
            if (isset($filterParams[$key])) {
                $filterParam = $filterParams[$key];
                if (isset($catalogFilter->properties[$filterParam['field']])) {
                    $newURN = $catalogFilter->properties[$filterParam['field']]->urn;
                    switch ($filterParam['relation']) {
                        case '>=':
                            $result[$newURN . '_from'] = (float)$val;
                            break;
                        case '<=':
                            $result[$newURN . '_to'] = (float)$val;
                            break;
                        case 'LIKE':
                            $result[$newURN . '_like'] = trim($val);
                            break;
                        case '=':
                            $result[$newURN] = $val;
                            break;
                    }
                }
            } elseif (!in_array($key, $ignoredParams)) {
                $result[$key] = $val;
            }
        }
        return $result;
    }


    /**
     * Определяет, ведется ли сейчас поиск
     * @param Block_Material $block Блок, для которого применяется интерфейс
     * @param Page $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @return bool
     */
    public function isSearch(Block_Material $block, CatalogFilter $catalogFilter, $get = [])
    {
        foreach ((array)$block->filter as $filterParam) {
            if ((
                    !is_numeric($filterParam['field']) ||
                    ($filterParam['relation'] == 'FULLTEXT')
                ) &&
                isset($get[$filterParam['var']])
            ) {
                return true;
            }
        }
        return $catalogFilter->filterHasCheckedOptions;
    }


    /**
     * Устанавливает (если нет) фильтр каталога
     * @param Block_Material $block Блок, для которого применяется интерфейс
     * @param Page $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     */
    public function setCatalogFilter(Block_Material $block, Page $page, array $get = [])
    {
        if (!$page->catalogFilter) {
            $t = new DiagTimer();
            $additionalParams = $block->additionalParams ?: [];
            $withChildrenGoods = (bool)($additionalParams['withChildrenGoods'] ?? false);
            $classname = static::FILTER_CLASS;
            $catalogFilter = $classname::loadOrBuild(
                $block->Material_Type,
                $withChildrenGoods,
                [],
                null,
                true,
                $additionalParams['useAvailabilityOrder'] ?? null
            );
            // // 2023-04-05, AVS: Актуально только при загрузке, т.к. build выполняется до этого
            // if ($useAvailabilityOrder = $block->additionalParams['useAvailabilityOrder']) {
            //     $catalogFilter->useAvailabilityOrder = $useAvailabilityOrder;
            // }
            $filterParams = $this->getFilterParams($block, $catalogFilter, $get);
            $catalogFilter->apply($page, $filterParams);
            $page->catalogFilter = $catalogFilter;
            $t->stop();
        }
    }
}
