<?php
/**
 * Файл класса фильтра каталога
 * @package RAAS.CMS
 * @version 4.3
 * @author Alex V. Surnin <info@volumnet.ru>
 * @copyright 2018, Volume Networks
 */
namespace RAAS\CMS\Shop;

use SOME\Pages;
use SOME\Singleton;
use RAAS\Exception;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

/**
 * Класс фильтра каталога
 * @property-read Material_Type $materialType Тип материалов
 * @property-read array<int> $materialTypesIds ID# всех типов материалов
 * @property-read bool $withChildrenGoods Учитывать товары из дочерних категорий
 * @property-read array<int ID# поля | string URN поля> $ignoredFields Игнорируемые поля
 * @property-read Page $catalog Текущий каталог
 * @property-read array<int[] ID# свойства => Material_Field> $properties Список свойств
 * @property-read array<string[] URN свойства => Material_Field> $propertiesByURNs Список свойств по URN
 * @property-read array<
 *                    string[] ID# свойства => array<
 *                        mixed[] значение => array<int ID# товара>
 *                    >
 *                > $propsMapping Маппинг свойств к товарам
 * @property-read array<
 *                    string[] ID# свойства => array<int ID# товара>
 *                > $sortMapping Маппинг свойств для сортировки (в пустом ключе - товары без сортировки (по порядку отображения))
 * @property-read array<int> $catalogGoodsIds ID# товаров, доступные по страницам без учета фильтров
 * @property-read array<int> $categoryGoodsIds ID# товаров, доступные на текущей странице без учета фильтров
 * @property-read array<string[] ID# свойства => mixed|array<mixed> значение или набор значений> $filter Значения фильтра
 * @property-read bool $filterHasCheckedOptions Есть ли у фильтра отмеченные опции
 * @property-read array<string[] ID# свойства => array<
 *                    mixed[] значение => ['value' => mixed значение, 'enabled' => bool Активно ли значение]
 *                >> $availableProperties Доступные для фильтра свойства
 */
class CatalogFilter
{
    /**
     * Тип материалов
     * @var Material_Type
     */
    protected $materialType;

    /**
     * ID# всех типов материалов
     * @var array<int>
     */
    protected $materialTypesIds = [];

    /**
     * Учитывать товары из дочерних категорий
     * @var bool
     */
    protected $withChildrenGoods = false;

    /**
     * Игнорируемые поля
     * @var array<int ID# поля | string URN поля>
     */
    protected $ignoredFields = [];

    /**
     * Категория каталога
     * @var Page
     */
    protected $catalog;

    /**
     * ID# товаров, доступные по страницам без учета фильтров
     * @var array<int>
     */
    protected $catalogGoodsIds = [];

    /**
     * ID# товаров, доступные на текущей странице без учета фильтров
     * @var array<int>
     */
    protected $categoryGoodsIds = [];

    /**
     * Маппинг свойств к товарам
     * @var array<
     *          int[] ID# свойства => array<
     *              mixed[] значение => array<int ID# товара>
     *          >
     *      >
     */
    protected $propsMapping = [];

    /**
     * Маппинг свойств для сортировки (в пустом ключе - товары без сортировки (по порядку отображения))
     * Уже после применения каталога и фильтров
     * @var array<
     *          string[] ID# свойства => array<int ID# товара>
     *      >
     */
    protected $sortMapping = [];

    /**
     * Список свойств
     * @var array<int[] ID# свойства => Material_Field>
     */
    protected $properties = [];

    /**
     * Список свойств по URN
     * @var array<string[] URN свойства => Material_Field>
     */
    protected $propertiesByURNs = [];

    /**
     * Значения фильтра
     * @var array<string[] ID# свойства => mixed|array<mixed> значение или набор значений>
     */
    protected $filter = [];

    /**
     * Есть ли у фильтра отмеченные опции
     * @var bool
     */
    protected $filterHasCheckedOptions = false;

    /**
     * Доступные для фильтра свойства
     * @var array<string[] ID# свойства => array<
     *          mixed[] значение => ['value' => mixed значение, 'enabled' => bool Активно ли значение]
     *      >>
     */
    protected $availableProperties = [];

    public function __get($var)
    {
        switch ($var) {
            case 'materialType':
            case 'materialTypesIds':
            case 'withChildrenGoods':
            case 'ignoredFields':
            case 'catalog':
            case 'properties':
            case 'propertiesByURNs':
            case 'propsMapping':
            case 'sortMapping':
            case 'catalogGoodsIds':
            case 'categoryGoodsIds':
            case 'filter':
            case 'filterHasCheckedOptions':
            case 'availableProperties':
                return $this->$var;
                break;
        }
    }

    /**
     * Конструктор класса
     * @param Material_Type $materialType Тип материала
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param array<int ID# поля | string URN поля | Material_Field поле> $ignored Игнорируемые поля
     */
    public function __construct(Material_Type $materialType, $withChildrenGoods = false, array $ignored = [])
    {
        $this->materialType = $materialType;
        $this->withChildrenGoods = $withChildrenGoods;
        foreach ($ignored as $ignoredField) {
            if ($ignoredField instanceof Material_Field) {
                $this->ignoredFields[] = (int)$ignoredField->id;
            } elseif (is_numeric($ignoredField) || is_string($ignoredField)) {
                $this->ignoredFields[] = $ignoredField;
            }
        }
    }


    /**
     * Построение кэша
     */
    public function build()
    {
        $this->materialTypesIds = $this->materialType->selfAndChildrenIds;
        $properties = $this->getAllProperties($this->materialTypesIds, $this->ignoredFields);
        foreach ($properties as $property) {
            $this->properties[$property->id] = $property;
            $this->propertiesByURNs[$property->urn] = $property;
        }
        $this->catalogGoodsIds = $this->getCatalogGoodsIds($this->materialTypesIds);
        $this->propsMapping = $this->buildCache(
            $this->materialTypesIds,
            array_keys($this->properties),
            $this->catalogGoodsIds
        );
        if ($this->withChildrenGoods) {
            $parents = $this->getPagesParents();
            $this->propsMapping['pages_ids'] = $this->bubbleUpGoods((array)$this->propsMapping['pages_ids'], $parents);
        }
    }


    /**
     * Применение фильтра и каталога
     * @param Catalog $catalog Текущий каталог
     * @param array<string[] => mixed> $params Аналог $_GET
     */
    public function apply(Page $catalog, array $params = [])
    {
        ini_set('max_execution_time', 3600);
        $this->catalog = $catalog;
        $this->filter = $this->getFilter($params);
        $this->filterHasCheckedOptions = $this->getFilterHasCheckedOptions($this->filter);
        $this->categoryGoodsIds = $this->propsMapping['pages_ids'][$catalog->id];
        $catalogPropsMapping = $this->applyCatalog($this->propsMapping, $this->catalog->id);
        $filteredMapping = $this->applyFilter($catalogPropsMapping, $this->filter);
        $goodsIdsMapping = $this->reduceMappingToGoodsIds($filteredMapping);
        $crossFilterMapping = $this->applyCrossFilter($goodsIdsMapping, $this->categoryGoodsIds);
        $this->sortMapping = $this->getSortMapping($catalogPropsMapping, $crossFilterMapping['']);
        // $st = microtime(true);
        $this->availableProperties = $this->getAvailableProperties(
            $catalogPropsMapping,
            $crossFilterMapping,
            $this->filter
        );
        // var_dump(microtime(true) - $st); exit;
    }


    /**
     * Получает все доступные ID# товаров
     * @param array<int> $mtypesIds ID# всех учитываемых типов материалов
     * @return array<int>
     */
    public function getCatalogGoodsIds(array $mtypesIds)
    {
        if (!$mtypesIds) {
            return [];
        }
        $sqlQuery = "SELECT id
                       FROM " . Material::_tablename()
                  . " WHERE vis
                        AND (NOT show_from OR show_from <= NOW())
                        AND (NOT show_to OR show_to >= NOW())
                        AND pid IN (" . implode(", ", $mtypesIds) . ")
                   ORDER BY priority";
        $catalogGoodsIds = Material::_SQL()->getcol($sqlQuery);
        return $catalogGoodsIds;
    }


    /**
     * Получает все свойства (кроме файлов, изображений и материалов)
     * @param array<int> $materialTypesIds ID# типов материалов
     * @param array<int ID# поля | string URN поля | Material_Field поле> $ignored Игнорируемые поля
     * @return array<Material_Field>
     */
    public function getAllProperties(array $materialTypesIds, array $ignored = [])
    {
        $ignoredIds = $ignoredURNs = [];
        foreach ($ignored as $ignoredField) {
            if ($ignoredField instanceof Material_Field) {
                $ignoredIds[] = (int)$ignoredField->id;
            } elseif (is_numeric($ignoredField)) {
                $ignoredIds[] = (int)$ignoredField;
            } elseif (is_string($ignoredField)) {
                $ignoredURNs[] = $ignoredField;
            }
        }
        $sqlQuery = "SELECT *
                       FROM " . Material_Field::_tablename()
                  . " WHERE classname = ?
                        AND datatype NOT IN (?, ?, ?)
                        AND pid IN (" . implode(", ", array_fill(0, count($materialTypesIds), '?')) . ") ";
        $sqlBind = array_merge([Material_Type::class, 'file', 'image', 'material'], $materialTypesIds);
        if ($ignoredIds) {
            $sqlQuery .= " AND id NOT IN (" . implode(", ", array_fill(0, count($ignoredIds), '?')) . ") ";
            $sqlBind = array_merge($sqlBind, $ignoredIds);
        }
        if ($ignoredURNs) {
            $sqlQuery .= " AND urn NOT IN (" . implode(", ", array_fill(0, count($ignoredURNs), '?')) . ") ";
            $sqlBind = array_merge($sqlBind, $ignoredURNs);
        }
        $sqlQuery .= " ORDER BY priority";
        $result = Material_Field::getSQLSet([$sqlQuery, $sqlBind]);
        return $result;
    }


    /**
     * Получает соответствие ID# страниц их родительским ID#
     * @return array<int[] ID# страницы => int ID# родительской страницы>
     */
    public function getPagesParents()
    {
        $sqlQuery = "SELECT id, pid FROM " . Page::_tablename() . " ORDER BY id";
        $sqlResult = Page::_SQL()->get($sqlQuery);
        $parents = [];
        foreach ($sqlResult as $sqlRow) {
            $parents[(int)$sqlRow['id']] = (int)$sqlRow['pid'];
        }
        return $parents;
    }


    /**
     * Получает исходную таблицу свойств
     * @param array<int> $mtypesIds ID# всех учитываемых типов материалов
     * @param array<int> $propertiesIds ID# всех свойств
     * @param array<int> $catalogGoodsIds ID# товаров, доступные по страницам без учета фильтров
     * @return array<string[] ID# свойства => array<mixed[] значение => array<int ID# товара>>>
     */
    public function buildCache(array $mtypesIds, array $propertiesIds, array $catalogGoodsIds)
    {
        $propsMapping = [];
        if ($catalogGoodsIds && $propertiesIds) {
            $sqlQuery = "SELECT *
                           FROM cms_data
                          WHERE pid IN (" . implode(", ", $catalogGoodsIds) . ")
                            AND fid IN (" . implode(", ", $propertiesIds) . ")";
            $sqlResult = Material::_SQL()->query($sqlQuery);
            foreach ($sqlResult as $sqlRow) {
                $propsMapping[trim($sqlRow['fid'])][trim($sqlRow['value'])][(int)$sqlRow['pid']] = (int)$sqlRow['pid'];
            }
            foreach ($propsMapping as $fid => $fieldData) {
                uksort($propsMapping[$fid], function ($a, $b) {
                    return strnatcasecmp($a, $b);
                });
                $propsMapping[$fid] = array_map('array_values', $propsMapping[$fid]);
            }

            // Получим маппинг по страницам
            $sqlQuery = "SELECT *
                           FROM cms_materials_pages_assoc
                          WHERE id IN (" . implode(", ", $catalogGoodsIds) . ")";
            $sqlResult = Material::_SQL()->get($sqlQuery);
            foreach ($sqlResult as $sqlRow) {
                $propsMapping['pages_ids'][(string)$sqlRow['pid']][] = (int)$sqlRow['id'];
            }
        }
        return $propsMapping;
    }


    /**
     * Переносит товары из дочерних категорий в родительские
     * @param array<int[] ID# страницы => array<int ID# товара>> $pagesMapping Старое соответствие товаров страницам
     * @return array<
     *             int[] ID# страницы => int ID# родительской страницы
     *         > $parents Соответствие дочерних страниц родительским
     * @param array<int[] ID# страницы => array<int ID# товара>>
     */
    public function bubbleUpGoods(array $pagesMapping, array $parents)
    {
        $newPagesMapping = $pagesMapping;
        foreach ($pagesMapping as $pageId => $goodsIds) {
            while ($pageId = $parents[$pageId]) {
                if (!isset($newPagesMapping[$pageId])) {
                    $newPagesMapping[$pageId] = [];
                }
                $newPagesMapping[$pageId] = array_merge(
                    $newPagesMapping[$pageId],
                    $goodsIds
                );
            }
        }
        $newPagesMapping = array_map('array_unique', $newPagesMapping);
        $newPagesMapping = array_map('array_filter', $newPagesMapping);
        $newPagesMapping = array_map('array_values', $newPagesMapping);
        return $newPagesMapping;
    }


    /**
     * Составляет фильтр по переменным окружения
     * @param array<string[] => mixed> $params Аналог $_GET
     * @return array<
     *             string[] имя свойства => array<mixed>|
     *                                      ['from' => float, 'to' => float]|
     *                                      ['like' => string] значение или набор значений
     *         >
     * @throws Exception Выбрасывает исключение, если свойства не найдены
     */
    public function getFilter(array $params = [])
    {
        if (!$this->propertiesByURNs) {
            throw new Exception('Properties are not set');
        }
        $filter = [];
        foreach ($params as $key => $val) {
            if (preg_match('/^(\\w+)_(from|to|like)$/umi', $key, $regs)) {
                $propKey = $regs[1];
                $limitName = $regs[2];
                $prop = $this->propertiesByURNs[$propKey];
                if (in_array($limitName, ['from', 'to']) && (float)$val) {
                    $filter[$prop->id][$limitName] = (float)$val;
                } elseif (in_array($limitName, ['like']) && trim($val)) {
                    $filter[$prop->id][$limitName] = trim($val);
                }
            } elseif ($prop = $this->propertiesByURNs[$key]) {
                if ($val = array_values(
                    array_unique(array_filter((array)$val, function ($x) {
                        return trim($x) !== '';
                    }))
                )) {
                    $filter[$prop->id] = $val;
                }
            }
        }
        return $filter;
    }


    /**
     * Проверяет, есть ли у фильтра отмеченные опции
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @return bool
     */
    public function getFilterHasCheckedOptions(array $filter)
    {
        return (bool)$filter;
    }


    /**
     * Применяет ограничения по каталогу
     * @param array<string[] ID# свойства => array<
     *            mixed[] значение => array<int ID# товара>
     *        >> $propsMapping Старый маппинг
     * @param int $catalogId ID текущей категории
     * @return array<
     *          string[] ID# свойства => array<mixed[] значение => array<int ID# товара>>
     *      > Новый маппинг (после применения каталога, без pages_ids)
     */
    public function applyCatalog(array $propsMapping, $catalogId)
    {
        $filteredMapping = $propsMapping;
        unset($filteredMapping['pages_ids']);
        if ($catalogId) {
            $catalogGoodsIds = $propsMapping['pages_ids'][$catalogId];
            $catalogGoodsIdsFlipped = array_flip($catalogGoodsIds);
            foreach ($filteredMapping as $propVar => $propValues) {
                $t = new \RAAS\Timer;
                $t->start();
                $filteredMapping[$propVar] = array_map(function ($valueGoodsIds) use ($catalogGoodsIds, $catalogGoodsIdsFlipped) {
                    $y = array_flip($valueGoodsIds);
                    $res = array_keys(array_intersect_key($y, $catalogGoodsIdsFlipped));
                    return $res;
                }, $propValues);
                // 2019-01-31, AVS: 0.15 секунд за итерацию - слишком много
                $filteredMapping[$propVar] = array_filter($filteredMapping[$propVar]);
            }
        }
        return $filteredMapping;
    }


    /**
     * Применить фильтр к маппингу
     * @param array<string[] ID# свойства => array<
     *            mixed[] значение => array<int ID# товара>
     *        >> $propsMapping Старый маппинг
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @return array<string[] ID# свойства (только те, которые присутствуют в фильтре) => array<
     *             mixed[] значение => array<int ID# товара>
     *         >> Новый маппинг (после применения фильтра)
     */
    public function applyFilter(array $propsMapping, array $filter = [])
    {
        $filteredMapping = [];
        foreach ($filter as $propId => $filterValues) {
            if (isset($filterValues['from']) || isset($filterValues['to'])) {
                $newMappingValues = array();
                foreach ((array)$propsMapping[$propId] as $val => $valIds) {
                    if ((!isset($filterValues['from']) || ((float)$val >= (float)$filterValues['from'])) &&
                        (!isset($filterValues['to']) || ((float)$val <= (float)$filterValues['to']))) {
                        $newMappingValues[$val] = $valIds;
                    }
                }
                $filteredMapping[$propId] = $newMappingValues;
            } elseif (isset($filterValues['like'])) {
                $newMappingValues = array();
                foreach ((array)$propsMapping[$propId] as $val => $valIds) {
                    if (stristr($val, $filterValues['like'])) {
                        $newMappingValues[$val] = $valIds;
                    }
                }
                $filteredMapping[$propId] = $newMappingValues;
            } else {
                $filteredMapping[$propId] = array_intersect_key(
                    $propsMapping[$propId],
                    array_flip($filterValues)
                );
            }
        }
        return $filteredMapping;
    }


    /**
     * Получает список доступных ID# товаров по свойствам, если бы применялись только ограничения этого свойства.
     * @param array<string[] ID# свойства => array<
     *            mixed[] значение => array<int ID# товара>
     *        >> $propsMapping Маппинг свойств
     * @return array<string[] ID# свойства => array<int ID# товара>>
     */
    public function reduceMappingToGoodsIds(array $propsMapping)
    {
        $propsToGoodsIds = [];
        foreach ($propsMapping as $propsVar => $propsValues) {
            $propsToGoodsIds[$propsVar] = array_reduce($propsValues, 'array_merge', []);
            $propsToGoodsIds[$propsVar] = array_values(array_unique($propsToGoodsIds[$propsVar]));
        }
        return $propsToGoodsIds;
    }


    /**
     * Получает список доступных ID# товаров по свойствам,
     * если бы для каждого свойства применялись ограничения всех остальных свойств кроме него
     *
     * @param array<string[] ID# свойства => array<int ID# товара>> $goodsIdsMapping Маппинг свойств
     * @param array<int> $categoryGoodsIds ID# товаров, доступные на текущей странице без учета фильтров
     * @return array<string[] ID# свойства => array<int ID# товара>> (под пустым индексом - все ограничения)
     */
    public function applyCrossFilter(array $goodsIdsMapping, array $categoryGoodsIds)
    {
        $crossFilterMapping = [];
        $goodsIdsMapping[''] = $categoryGoodsIds;
        foreach ($goodsIdsMapping as $propVar => $propIds) {
            $newMapping = $goodsIdsMapping;
            unset($newMapping[$propVar]);
            if ($newMapping) {
                $goodsIds = array_reduce($newMapping, 'array_intersect', $categoryGoodsIds);
            } else {
                $goodsIds = $categoryGoodsIds;
            }
            $crossFilterMapping[$propVar] = array_values($goodsIds);
        }
        return $crossFilterMapping;
    }


    /**
     * Получает переменные окружения по фильтру
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @return array<string[] => mixed> Аналог $_GET
     */
    public function getURLParamsFromFilter(array $filter = [])
    {
        if (!$filter && $this->filter) {
            $filter = $this->filter;
        }
        $params = [];
        foreach ($filter as $propId => $propValues) {
            $prop = $this->properties ? $this->properties[$propId] : new Material_Field($propId);
            if (isset($propValues['from']) || isset($propValues['to'])) {
                if (isset($propValues['from'])) {
                    $params[$prop->urn . '_from'] = $propValues['from'];
                }
                if (isset($propValues['to'])) {
                    $params[$prop->urn . '_to'] = $propValues['to'];
                }
            } elseif (isset($propValues['like'])) {
                $params[$prop->urn . '_like'] = $propValues['like'];
            } else {
                $params[$prop->urn] = (count($propValues) > 1 ? $propValues : array_shift($propValues));
            }
        }
        return $params;
    }


    /**
     * Получает канонический URL из фильтра
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @param array<int[] ID# свойства => Material_Field свойство> $properties Все свойства
     * @param Catalog $catalog Текущий каталог
     * @return string
     * @throws Exception Выбрасывает исключение, если категория каталога не установлена
     */
    public function getCanonicalURLFromFilter(array $filter = [])
    {
        if (!$this->catalog->id) {
            throw new Exception('Catalog is not set');
        }
        $params = $this->getURLParamsFromFilter($filter);
        if (!count($params)) {
            return $this->catalog->url;
        }
        $urlArray = parse_url($_SERVER['REQUEST_URI']);
        parse_str($urlArray['query'], $urlArray);
        unset($urlArray['page']);
        unset($urlArray['id']);
        $urlArray = array_filter(array_merge($urlArray, $params), function ($x) {
            return $x !== '';
        });
        return $this->catalog->url . '?' . http_build_query($urlArray);
    }


    /**
     * Получает доступные свойства
     * @param array<
     *            string[] ID# свойства => array<mixed[] значение => array<int ID# товара>>
     *        > $propsMapping Маппинг свойств
     * @param array<
     *            string[] ID# свойства => array<int ID# товара>
     *        > $crossFilterMapping Список доступных ID# товаров по свойствам,
     *                              если бы для каждого свойства применялись ограничения
     *                              всех остальных свойств кроме него
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @return array<string[] ID# свойства => array<mixed[] значение => [
     *             'value' => mixed значение,
     *             'doRich' => mixed Отформатированное значение
     *             'prop' => Material_Field свойство, к которому относится значение
     *             'checked' => bool Установлено ли значение
     *             'enabled' => bool Активно ли значение
     *         ]>>
     */
    public function getAvailableProperties(array $propsMapping, array $crossFilterMapping, array $filter = [])
    {
        $filteredPropsMapping = [];
        foreach ($propsMapping as $propId => $propValues) {
            $prop = $this->properties ? $this->properties[$propId] : new Material_Field($propId);
            $crossFilterGoodsIds = $crossFilterMapping[$propId] ?: $crossFilterMapping[''];
            foreach ($propValues as $propValue => $goodsIds) {
                $valueData = [
                    'prop' => $prop,
                    'enabled' => (bool)array_intersect($goodsIds, $crossFilterGoodsIds)
                ];
                if (!in_array($prop->datatype, ['number', 'range'])) {
                    $valueData = array_merge($valueData, [
                        'value' => $propValue,
                        'doRich' => $prop->doRich($propValue),
                        'checked' => in_array($propValue, (array)$filter[$propId]),
                    ]);
                }
                $filteredPropsMapping[$propId][$propValue] = $valueData;
            }
        }
        return $filteredPropsMapping;
    }


    /**
     * Получает маппинг по сортировке
     * @param array<
     *            string[] ID# свойства => array<
     *                mixed[] значение => array<int ID# товара>
     *            >
     *        > $catalogPropsMapping Маппинг свойств к товарам в пределах категории
     * @param array<int> $goodsIds ID# товаров после применения категории и фильтра
     */
    public function getSortMapping($catalogPropsMapping, $goodsIds)
    {
        $newCatalogPropsMapping = [];
        foreach ($catalogPropsMapping as $propId => $propData) {
            $mappingKeys = array_keys($propData);
            natcasesort($mappingKeys);
            foreach ($mappingKeys as $propValue) {
                $newCatalogPropsMapping[$propId][$propValue] = $catalogPropsMapping[$propId][$propValue];
            }
        }
        $catalogReduced = $this->reduceMappingToGoodsIds($newCatalogPropsMapping);
        $sortMapping = [];
        foreach ($catalogReduced as $propId => $propGoodsIds) {
            $sortMapping[$propId] = array_values(array_intersect($propGoodsIds, $goodsIds));
        }
        $sortMapping[''] = $goodsIds;
        return $sortMapping;
    }


    /**
     * Получает ID# товаров с учетом (или без учета) сортировки
     * @param string $sort URN поля для сортировки, либо пустая строка для сортировки только по порядку отображения
     * @param int $order Порядок сортировки - >= 0 - прямой, < 0 - обратный
     * @return array<int>
     * @throws Exception Выбрасывает исключение, когда фильтр не инициализирован и/или не задан
     */
    public function getIds($sort = '', $order = 1)
    {
        if (!$this->propertiesByURNs || !$this->sortMapping) {
            throw new Exception('Filter is not initialized or is not applied');
        }
        $sortKey = '';
        if ($sort && isset($this->propertiesByURNs[$sort])) {
            $sortKey = $this->propertiesByURNs[$sort]->id;
        }
        $ids = $this->sortMapping[$sortKey];
        if ($sortKey && ($order < 0)) {
            $ids = array_reverse($ids);
        }
        return $ids;
    }


    /**
     * Получает товары с учетом (или без учета) сортировки
     * @param string $sort URN поля для сортировки, либо пустая строка для сортировки только по порядку отображения
     * @param Pages|null $pages Постраничная разбивка
     * @return array<int>
     */
    public function getMaterials(Pages $pages = null, $sort = '', $order = 1)
    {
        $ids = $this->getIds($sort, $order);
        if (!$ids) {
            return [];
        }
        if ($pages) {
            do {
                $pageIds = array_slice($ids, (int)$pages->from, (int)$pages->rows_per_page);
                $i++;
            } while (!$pages->check(count($pageIds), count($ids)) && ($i < 100));
        } else {
            $pageIds = $ids;
        }

        $sqlIds = implode(", ", array_fill(0, count($pageIds), "?"));
        $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS *
                       FROM " . Material::_tablename()
                  . " WHERE id IN (" . $sqlIds . ")
                   ORDER BY FIELD(id, " . $sqlIds . ")";
        $sqlBind = array_merge($pageIds, $pageIds);
        $set = Material::getSQLSet([$sqlQuery, $sqlBind]);
        return $set;
    }


    /**
     * Считает количество товаров в категории
     * @param Page|int $page Страница или ID# страницы
     * @return int
     */
    public function count($page)
    {
        $count = 0;
        $pageId = (int)(($page instanceof Page) ? $page->id : $page);
        if (isset($this->propsMapping['pages_ids'][$pageId])) {
            $count = count($this->propsMapping['pages_ids'][$pageId]);
        }
        return $count;
    }


    /**
     * Экспортирует данные
     * @return [
     *             'materialType' => Material_Type,
     *             'withChildrenGoods' => bool Учитывать товары из дочерних категорий,
     *             'ignoredFields' => array<int ID# поля | string URN поля> Игнорируемые поля,
     *             'materialTypesIds' => int ID# всех типов материалов,
     *             'properties' => array<
     *                                 int[] ID# свойства => array<string[] ключ свойства => mixed значение>
     *                             > Свойства,
     *             'catalogGoodsIds' => array<int> ID# товаров,
     *             'propsMapping' => array<
     *                                   string[] ID# свойства => array<
     *                                       mixed[] значение => array<int ID# товара>
     *                                   >
     *                               > Маппинг свойств
     *         ]
     */
    public function export()
    {
        $result = [
            'materialType' => $this->materialType,
            'withChildrenGoods' => (bool)$this->withChildrenGoods,
            'ignoredFields' => $this->ignoredFields,
            'materialTypesIds' => $this->materialTypesIds,
            'properties' => $this->properties,
            'catalogGoodsIds' => $this->catalogGoodsIds,
            'propsMapping' => $this->propsMapping,
        ];
        return $result;
    }


    /**
     * Импортирует данные
     * @param [
     *             'materialType' => Material_Type,
     *             'withChildrenGoods' => bool Учитывать товары из дочерних категорий,
     *             'ignoredFields' => array<int ID# поля | string URN поля> Игнорируемые поля,
     *             'materialTypesIds' => int ID# всех типов материалов,
     *             'properties' => array<
     *                                 int[] ID# свойства => array<string[] ключ свойства => mixed значение>
     *                             > Свойства,
     *             'catalogGoodsIds' => array<int> ID# товаров,
     *             'propsMapping' => array<
     *                                   string[] ID# свойства => array<
     *                                       mixed[] значение => array<int ID# товара>
     *                                   >
     *                               > Маппинг свойств
     *         ] $data Данные для импорта
     * @return static
     * @throws Exception Выбрасывает исключение, если какое-то из свойств не задано
     */
    public static function import(array $data)
    {
        foreach ([
            'materialType',
            'withChildrenGoods',
            'ignoredFields',
            'materialTypesIds',
            'properties',
            'catalogGoodsIds',
            'propsMapping',
        ] as $key) {
            if (!isset($data[$key])) {
                throw new Exception('Invalid import format - property ' . $key . ' is not set');
            }
        }
        $materialType = $data['materialType'];
        $filter = new CatalogFilter($materialType, (bool)$data['withChildrenGoods'], (array)$data['ignoredFields']);
        foreach (['materialTypesIds', 'catalogGoodsIds', 'propsMapping'] as $key) {
            $filter->$key = $data[$key];
        }
        $props = $propsByURNs = [];
        foreach ((array)$data['properties'] as $propId => $propData) {
            $field = $propData;
            $props[$propId] = $field;
            $propsByURNs[$propData->urn] = $field;
        }
        $filter->properties = $props;
        $filter->propertiesByURNs = $propsByURNs;
        return $filter;
    }


    /**
     * Получает путь к файлу по умолчанию
     * @param int $materialTypeId ID# типа материалов
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @return string
     */
    public static function getDefaultFilename($materialTypeId, $withChildrenGoods)
    {
        return Package::i()->cacheDir . '/system/catalogfilter' .
               (int)$materialTypeId . '.' .
               ($withChildrenGoods ? 'wch' : 'noch') . '.php';
    }


    /**
     * Сохраняет кэш в файл
     * @param string|null $filename Путь, куда сохраняем. Если не указан, будет использован путь по умолчанию
     * @throws Exception Выбрасывает исключение, если не удалось сохранить файл
     */
    public function save($filename = null)
    {
        if (!$filename) {
            $filename = static::getDefaultFilename($this->materialType->id, $this->withChildrenGoods);
        }
        $dir = dirname($filename);
        @mkdir($dir, 0777, true);
        $tmpFilename = tempnam(sys_get_temp_dir(), 'raas_');
        $data = $this->export();
        $cacheId = 'RAASCACHE' . date('YmdHis') . md5(rand());
        $text = '<' . '?php return unserialize(<<' . "<'" . $cacheId . "'\n" . serialize($data) . "\n" . $cacheId . "\n);\n";
        $result = file_put_contents($tmpFilename, $text);
        @unlink($filename);
        @rename($tmpFilename, $filename);
        if (!is_file($filename)) {
            throw new Exception('Cannot save filter cache data');
        }
    }


    /**
     * Загружает кэш из файла
     * @param Material_Type $materialType Тип материала
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param string|null $filename Путь, откуда загружаем. Если не указан, будет использован путь по умолчанию
     * @return static
     * @throws Exception Выбрасывает исключение, если не удалось загрузить файл (или каскадно, если файл не распознан)
     */
    public static function load(Material_Type $materialType, $withChildrenGoods = false, $filename = null)
    {
        if (!$filename) {
            $filename = static::getDefaultFilename($materialType->id, $withChildrenGoods);
        }
        if (!is_file($filename)) {
            throw new Exception('Cannot load filter cache data - filename ' . $filename . ' doesn\'t exist');
        }
        $data = include $filename;
        $filter = static::import($data);
        return $filter;
    }


    /**
     * Загружает или строит кэш
     * @param Material_Type $materialType Тип материала
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param array<int ID# поля | string URN поля | Material_Field поле> $ignored Игнорируемые поля
     * @param string|null $filename Путь для загрузки/сохранения. Если не указан, будет использован путь по умолчанию
     * @param bool $save Сохранять кэш при построении
     * @return static
     */
    public static function loadOrBuild(Material_Type $materialType, $withChildrenGoods = false, array $ignored = [], $filename = null, $save = true)
    {
        try {
            $filter = static::load($materialType, $withChildrenGoods, $filename);
        } catch (Exception $e) {
            $filter = new static($materialType, $withChildrenGoods, $ignored);
            $filter->build();
            if ($save) {
                $filter->save($filename);
            }
        }
        return $filter;
    }
}
