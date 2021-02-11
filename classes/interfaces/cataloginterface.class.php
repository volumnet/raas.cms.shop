<?php
/**
 * Файл стандартного интерфейса каталога
 */
namespace RAAS\CMS\Shop;

use Twig_Environment;
use Twig_Loader_String;
use RAAS\Attachment;
use RAAS\CMS\Block;
use RAAS\CMS\Block_Form;
use RAAS\CMS\Block_Material;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialInterface;
use RAAS\CMS\Page;

/**
 * Класс стандартного интерфейса каталога
 */
class CatalogInterface extends MaterialInterface
{
    /**
     * Класс фильтра каталога
     */
    const FILTER_CLASS = CatalogFilter::class;

    public function process()
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
     * @return [
     *             'Item' => Material Обрабатываемый материал,
     *             'prev' ?=> Material Предыдущий материал,
     *             'next' ?=> Material Следующий материал,
     *             'commentFormBlock' ?=> Block_Form блок формы комментариев,
     *             'commentsListBlock' ?=> Block_Material блок списка комментариев,
     *             'comments' ?=> array<Material> список комментариев
     *             'commentsListText' ?=> string результат отработки блока
     *                                    списка комментариев
     *             'rating' => int Рейтинг товара
     *             'faqFormBlock' ?=> Block_Form блок формы вопрос-ответ,
     *             'faqListBlock' ?=> Block_Material блок списка вопросов и ответов,
     *             'faq' ?=> array<Material> список вопросов и ответов
     *             'faqListText' ?=> string результат отработки блока
     *                               списка вопросов и ответов
     *         ]
     */
    public function processMaterial(Block_Material $block, Page $page, Material $item, array $get = [], array $server = [])
    {
        $legacy = $this->checkLegacyArbitraryMaterialAddress($block, $page, $item, $server);
        if ($legacy) {
            return;
        }
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
            $twig = new Twig_Environment(new Twig_Loader_String());
            foreach (['name', 'meta_title', 'meta_keywords', 'meta_description', 'h1'] as $key) {
                if (!$item->$key) {
                    if (!$metaData) {
                        $metaData = $this->getItemMetadata($item);
                    }
                    if (!isset($metaTemplates[$key])) {
                        $metaTemplates[$key] = $this->getMetaTemplate($page, $key . '_' . $blockParams['metaTemplates']);
                    }
                    $page->$key = $twig->render($metaTemplates[$key], $metaData);
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
        if ($blockParams['listMetaTemplates']) {
            $metaData = $this->getPageMetadata($page);
            $metaTemplates = [];
            $twig = new Twig_Environment(new Twig_Loader_String());
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
                    $page->$key = $twig->render($metaTemplates[$key], $metaData);
                }
            }
        }
    }


    public function processList(Block_Material $block, Page $page, array $get = [])
    {
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
        return $result;
    }


    public function getSQLParts(Block_Material $block, Page $page, array $get = [])
    {
        $sqlFrom = $sqlFromBind = $sqlWhere = $sqlWhereBind = $result = [];
        $sqlSort = $sqlOrder = "";
        $this->getListAccessSQL($sqlFrom, $sqlFromBind, $sqlWhere);
        $this->getMaterialsSQL(
            $block,
            $page,
            $sqlFrom,
            $sqlWhere,
            $sqlWhereBind
        );
        $this->getFilteringSQL(
            $sqlFrom,
            $sqlFromBind,
            $sqlWhere,
            $sqlWhereBind,
            (array)$block->filter,
            $get
        );
        $this->getOrderSQL(
            $block,
            $get,
            $sqlFrom,
            $sqlFromBind,
            $sqlSort,
            $sqlOrder,
            $page->catalogFilter // Добавился параметр относительно MaterialInterface::getSQLParts
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


    public function getMaterialsSQL(
        Block_Material $block,
        Page $page,
        array &$sqlFrom,
        array &$sqlWhere,
        array &$sqlWhereBind
    ) {
        if ($page->catalogFilter) {
            $ids = $page->catalogFilter->getIds();
            if ($ids) {
                $sqlWhere[] = "tM.id IN (" . implode(", ", array_fill(0, count($ids), "?")) . ")";
                $sqlWhereBind = array_merge($sqlWhereBind, $ids);
            } else {
                $sqlWhere[] = "0";
            }
        }
    }


    public function getFilteringSQL(
        array &$sqlFrom,
        array &$sqlFromBind,
        array &$sqlWhere,
        array &$sqlWhereBind,
        array $filter = [],
        array $get = []
    ) {
        $fullTextFilter = array_values(array_filter($filter, function ($x) {
            return $x['relation'] == 'FULLTEXT';
        }));
        $filter = array_values(array_filter($filter, function ($x) {
            return !is_numeric($x['field']);
        }));
        parent::getFilteringSQL(
            $sqlFrom,
            $sqlFromBind,
            $sqlWhere,
            $sqlWhereBind,
            $filter,
            $get
        );
        $this->getFullTextFilteringSQL(
            $sqlFrom,
            $sqlFromBind,
            $sqlWhere,
            $sqlWhereBind,
            $fullTextFilter,
            $get
        );
    }


    /**
     * Получает SQL-инструкции по полнотекстовому поиску
     * @param array<
     *            string[] псевдоним поля => string SQL-инструкция по выборке таблицы
     *        > $sqlFrom Список подключаемых таблиц
     * @param array<mixed Значение связки> $sqlFromBind Связки для SQL FROM
     * @param array<string SQL-инструкция> $sqlWhere Ограничения для SQL WHERE
     * @param array<mixed Значение связки> $sqlWhereBind Связки для SQL WHERE
     * @param array<[
     *            'var' => string Переменная для фильтрации
     *            'relation' => 'FULLTEXT' Отношение для фильтрации
     *            'field' => int ID# поля артикула
     *        ]> $filter Данные по фильтрации
     * @param array $get Поля $_GET параметров
     */
    public function getFullTextFilteringSQL(
        array &$sqlFrom,
        array &$sqlFromBind,
        array &$sqlWhere,
        array &$sqlWhereBind,
        array $filter = [],
        array $get = []
    ) {
        $sqlArray = [];
        foreach ((array)$filter as $filterItem) {
            if (isset(
                $filterItem['var'],
                $filterItem['relation'],
                $filterItem['field'],
                $get[$filterItem['var']]
            ) && $filterItem['relation'] == 'FULLTEXT') {
                $var = $filterItem['var'];
                $val = $get[$var];
                $field = $filterItem['field'];
                $sqlField = $this->getField($field, 't' . $field, $sqlFrom, $sqlFromBind);
                $sqlNameField = $this->getField('name', '', $sqlFrom, $sqlFromBind);

                $filteringItemSQL = $this->getFilteringItemSQL($sqlField, 'LIKE', $val);
                $filteringNameSQL = $this->getFilteringItemSQL($sqlNameField, 'LIKE', $val);
                $sqlArray[$var][] = "(" . implode(" OR ", [$filteringNameSQL[0], $filteringItemSQL[0]]) . ")";
                $sqlWhereBind[] = $filteringNameSQL[1];
                $sqlWhereBind[] = $filteringItemSQL[1];
            }
        }
        foreach ($sqlArray as $key => $arr) {
            $sqlWhere[$key] = $arr ? "(" . implode(" AND ", $arr) . ")" : "";
        }
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
     * @param CatalogFilter $catalogFilter Фильтр каталога
     */
    public function getOrderSQL(
        Block_Material $block,
        array $get,
        array &$sqlFrom,
        array &$sqlFromBind,
        &$sqlSort,
        &$sqlOrder,
        CatalogFilter $catalogFilter = null
    ) {
        $sortVar = (string)$block->sort_var_name;
        $sortVal = isset($get[$sortVar]) ? $get[$sortVar] : '';
        $orderVar = (string)$block->order_var_name;
        $sortParams = (array)$block->sort;
        $sortDefField = (string)$block->sort_field_default;
        $orderRelDefault = (string)$block->sort_order_default;
        if ($sortVar && $sortVal && $sortParams) {
            // Выберем подходящую запись
            // (у которой значение var совпадает со значением переменной сортировки $_GET)
            $sortItem = $this->getMatchingSortParam($sortVal, $sortParams, 'var');
            $orderRelation = isset($sortItem['relation'])
                           ? (string)$sortItem['relation']
                           : '';
            if ($sortItem) {
                if (is_numeric($sortItem['field'])) {
                    if ($catalogFilter &&
                        isset($catalogFilter->properties[$sortItem['field']])
                    ) {
                        $order = $this->getOrder($orderVar, $orderRelation, $get);
                        $order = ($order == 'desc' ? -1 : 1);
                        $urn = $catalogFilter->properties[$sortItem['field']]->urn;
                        $ids = $catalogFilter->getIds($urn, $order);
                        $sqlSort = $ids ? "FIELD(tM.id, " . implode(", ", array_map('intval', $ids)) . ")" : "";
                        $sqlOrder = "";
                    }
                } else {
                    $sqlSort = $this->getField(
                        $sortItem['field'],
                        'tOr',
                        $sqlFrom,
                        $sqlFromBind
                    );
                    $sqlOrder = mb_strtoupper(
                        $this->getOrder($orderVar, $orderRelation, $get)
                    );
                }
                return;
            }
        }
        // Ни с чем не совпадает, но есть сортировка по умолчанию
        if ($sortDefField) {
            if (is_numeric($sortDefField)) {
                if ($catalogFilter &&
                    isset($catalogFilter->properties[$sortDefField])
                ) {
                    $order = $this->getOrder($orderVar, $orderRelDefault, $get);
                    $order = ($order == 'desc' ? -1 : 1);
                    $urn = $catalogFilter->properties[$sortDefField]->urn;
                    $ids = $catalogFilter->getIds($urn, $order);
                    $sqlSort = $ids ? "FIELD(tM.id, " . implode(", ", array_map('intval', $ids)) . ")" : "";
                    $sqlOrder = "";
                }
            } else {
                $sqlSort = $this->getField(
                    $sortDefField,
                    'tOr',
                    $sqlFrom,
                    $sqlFromBind
                );
                $sqlOrder = mb_strtoupper(
                    $this->getOrder($orderVar, $orderRelDefault, $get)
                );
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
     * @return [
     *             'commentFormBlock' ?=> Block_Form блок формы комментариев,
     *             'commentsListBlock' ?=> Block_Material блок списка комментариев,
     *             'comments' ?=> array<Material> список комментариев
     *             'commentsListText' ?=> string результат отработки блока
     *                                    списка комментариев
     *         ]
     */
    public function processComments(
        Block_Material $block,
        Page $page,
        Material $item,
        $formBlockVar = 'commentFormBlock',
        $listBlockVar = 'commentsListBlock'
    ) {
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
                if ($commentsListBlock->Interface->id) {
                    $commentsListData = $commentsListBlock->Interface->process($commentsListParams);
                }
                $commentsListData = array_merge(
                    (array)$commentsListData,
                    (array)$commentsListParams
                );
                $commentsListData['Set'] = array_values(
                    array_filter(
                        (array)$commentsListData['Set'],
                        function ($x) use ($item) {
                            return $this->commentsFilterFunction($x, $item);
                        }
                    )
                );
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
            $metaData['counter'] = $page->catalogFilter->count(true);
            $metaData['selfCounter'] = $page->catalogFilter->count(false);
            $priceField = $page->catalogFilter->propertiesByURNs['price'];
            if ($priceField->id) {
                $priceValues = array_keys($page->catalogFilter->availableProperties[$priceField->id]);
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
        $session['visited'] = (array)$session['visited'];
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
            parse_str(trim($block->params), $blockParams);
            $withChildrenGoods = isset($blockParams['withChildrenGoods'])
                               ? (bool)$blockParams['withChildrenGoods']
                               : false;
            $classname = static::FILTER_CLASS;
            $catalogFilter = $classname::loadOrBuild(
                $block->Material_Type,
                $withChildrenGoods,
                []
            );
            $filterParams = $this->getFilterParams($block, $catalogFilter, $get);
            $catalogFilter->apply($page, $filterParams);
            $page->catalogFilter = $catalogFilter;
        }
    }
}
