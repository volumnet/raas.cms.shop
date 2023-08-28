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
 * @property-read int[] $materialTypesIds  ID# типов материалов
 * @property-read bool $withChildrenGoods Учитывать товары из дочерних категорий
 * @property-read array $ignoredFields <pre><code>array<int ID# поля | string URN поля></code></pre> Игнорируемые поля
 * @property-read Page $catalog Текущий каталог
 * @property-read array $properties <pre><code>array<int[] ID# свойства => Material_Field></code></pre> Список свойств
 * @property-read array $propertiesByURNs <pre><code>array<
 *     string[] URN свойства => Material_Field
 * ></code></pre> Список свойств по URN
 * @property-read array $richValues <pre><code>array<
 *     int[] ID# свойства (только те, у которых rich-значения отличаются от сырых значений) => array<
 *         mixed[] сырое значение => mixed rich-значение
 *     >
 * ></code></pre> Rich-значения свойств, у которых могут заведомо отличаться от сырых значений
 * @property-read array $numericFieldsIds <pre><code>array<
 *     string ID# поля => int ID# поля
 * ></code></pre> Массив ID# числовых полей (number или range)
 * @property-read array $propsMapping <pre><code>array<
 *     string[] ID# свойства => array<mixed[] значение => array<string[] ID# товара => int ID# товара>>
 * ></code></pre> Маппинг свойств к товарам
 * @property-read array $catalogPropsMapping <pre><code>array<
 *     string[] ID# свойства => array<mixed[] значение => array<string[] ID# товара => int ID# товара>>
 * ></code></pre> Маппинг свойств к товарам (только в категории)
 * @property-read array $sortMapping <pre><code>array<
 *     string[] ID# свойства => array<string[] ID# товара => int ID# товара>
 * ></code></pre> Маппинг свойств для сортировки (в пустом ключе - товары без сортировки (по порядку отображения))
 * @property-read array $catalogGoodsIds <pre><code>array<
 *     string[] ID# товара => int ID# товара
 * ></code></pre> ID# товаров, доступные по страницам без учета фильтров
 * @property-read array $categoryGoodsIds <pre><code>array<
 *     string[] ID# товара => int ID# товара
 * ></code></pre> ID# товаров, доступные на текущей странице без учета фильтров
 * @property-read array $filter <pre><code>array<
 *                    string[] ID# свойства => mixed|array<mixed> значение или набор значений
 *                ></code></pre> Значения фильтра
 * @property-read bool $filterHasCheckedOptions Есть ли у фильтра отмеченные опции
 * @property-read array $availableProperties <pre><code>array<string[] ID# свойства => array<
 *     mixed[] значение => [
 *         'value' => mixed значение,
 *         'enabled' => bool Активно ли значение
 *     ]
 * >></code></pre> Доступные для фильтра свойства
 * @property-read array $counter <pre><code>array<
 *     string ID# категории => int количество товаров
 * ></code></pre> Счетчик товаров в категориях с учетом подкатегорий
 * @property-read array $selfCounter <pre><code>array<
 *     string ID# категории => int количество товаров
 * ></code></pre> Счетчик товаров в категориях без учета подкатегорий
 * @property-read array $goodsIdsMapping <pre><code>array<
 *     string[] ID# свойства => array<string[] ID# товара => int ID# товара>
 * ></code></pre> Объединенные товары по свойствам (без учета значения)
 * @property-read array <pre><code>array<
 *     string[] ID# свойства => array<string[] ID# товара => int ID# товара>
 * ></code></pre> $crossFilterMapping Кросс-фильтры.
 */
class CatalogFilter
{
    /**
     * Использовать сортировку по наличию
     * @var string|null URN поля наличия, либо null чтобы не использовать
     */
    public $useAvailabilityOrder = null;

    /**
     * Тип материалов
     * @var Material_Type
     */
    protected $materialType;

    /**
     * ID# всех типов материалов
     * @var array <pre><code>array<string[] ID# типа материалов => int ID# типа материалов></code></pre>
     */
    protected $materialTypesIds = [];

    /**
     * Учитывать товары из дочерних категорий
     * @var bool
     */
    protected $withChildrenGoods = false;

    /**
     * Игнорируемые поля
     * @var array <pre><code>array<int ID# поля | string URN поля></code></pre>
     */
    protected $ignoredFields = [];

    /**
     * Категория каталога
     * @var Page
     */
    protected $catalog;

    /**
     * ID# товаров, доступные по страницам без учета фильтров
     * @var array <pre><code>array<string[] ID# товара => int ID# товара></code></pre>
     */
    protected $catalogGoodsIds = [];

    /**
     * ID# товаров, доступные на текущей странице без учета фильтров
     * @var array <pre><code>array<string[] ID# товара => int ID# товара></code></pre>
     */
    protected $categoryGoodsIds = [];

    /**
     * Маппинг свойств к товарам
     * @var array <pre><code>array<
     *     int[] ID# свойства => array<mixed[] значение => array<string[] ID# товара => int ID# товара>>
     * ></code></pre>
     */
    protected $propsMapping = [];

    /**
     * Маппинг свойств к товарам только товаров, которые есть в категории, без учета pages_ids
     * @var array <pre><code>array<
     *     int[] ID# свойства => array<mixed[] значение => array<string[] ID# товара => int ID# товара>>
     * ></code></pre>
     */
    protected $catalogPropsMapping = [];

    /**
     * Маппинг свойств для сортировки
     * (в пустом ключе - товары без сортировки (по порядку отображения))
     * Уже после применения каталога и фильтров
     * @var array <pre><code>array<string[] ID# свойства => array<string[] ID# товара => int ID# товара>></code></pre>
     */
    protected $sortMapping = [];

    /**
     * Список свойств
     * @var array <pre><code>array<int[] ID# свойства => Material_Field></code></pre>
     */
    protected $properties = [];

    /**
     * Список свойств по URN
     * @var array <pre><code>array<string[] URN свойства => Material_Field></code></pre>
     */
    protected $propertiesByURNs = [];

    /**
     * Rich-значения свойств, у которых могут заведомо отличаться от сырых значений
     * @var array <pre><code>array<
     *     int[] ID# свойства (только те, у которых rich-значения отличаются от сырых значений) => array<
     *         mixed[] сырое значение => mixed rich-значение
     *     >
     * ></code></pre>
     */
    protected $richValues = [];


    /**
     * Массив ID# числовых полей (number или range)
     * @var array <pre><code>array<string ID# поля => int ID# поля></code></pre>
     */
    protected $numericFieldsIds = [];

    /**
     * Значения фильтра
     * @var array <pre><code>array<
     *     string[] ID# свойства => mixed|array<mixed> значение или набор значений
     * ></code></pre>
     */
    protected $filter = [];

    /**
     * Есть ли у фильтра отмеченные опции
     * @var bool
     */
    protected $filterHasCheckedOptions = false;

    /**
     * Доступные для фильтра свойства
     * @var array <pre><code>array<string[] ID# свойства => array<mixed[] значение => [
     *     'value' => mixed значение,
     *     'enabled' => bool Активно ли значение
     * ]>></code></pre>
     */
    protected $availableProperties = [];

    /**
     * Счетчик товаров в категориях с учетом подкатегорий
     * @var array <pre><code>array<string ID# категории => int количество товаров></code></pre>
     */
    protected $counter = [];

    /**
     * Счетчик товаров в категориях без учета подкатегорий
     * @var array <pre><code>array<string ID# категории => int количество товаров></code></pre>
     */
    protected $selfCounter = [];

    /**
     * Объединенные товары по свойствам (без учета значения).
     *
     * $goodsIdsMapping['price'] - это те ID# товаров, которые находятся в текущей категории и удовлетворяют
     * фильтру по цене.
     * Cвойства только те, которые есть в фильтре.
     * @var array <pre><code>array<
     *     string[] ID# свойства => array<string[] ID# товара => int ID# товара>
     * ></code></pre>
     */
    protected $goodsIdsMapping = [];

    /**
     * Кросс-фильтры.
     *
     * $crossFilterMapping['price'] - это все товары, у которых применяется весь фильтр, кроме фильтра по цене
     * (сделано для того, чтобы потом получить все возможные значения по свойству для фильтра, не обращая внимания
     * на ограничения собственно по этому свойству)
     * ВАЖНАЯ ЧАСТЬ - под пустым индексом [''] находится список товаров, с применением ВСЕГО фильтра,
     * т.е. фактически отображаемые товары
     * @var array <pre><code>array<
     *     string[] ID# свойства => array<string[] ID# товара => int ID# товара>
     * ></code></pre>
     */
    protected $crossFilterMapping = [];

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
            case 'richValues':
            case 'numericFieldsIds':
            case 'propsMapping':
            case 'catalogPropsMapping':
            case 'sortMapping':
            case 'catalogGoodsIds':
            case 'categoryGoodsIds':
            case 'filter':
            case 'filterHasCheckedOptions':
            case 'counter':
            case 'selfCounter':
            case 'goodsIdsMapping':
            case 'crossFilterMapping':
                return $this->$var;
                break;
            case 'availableProperties':
                if (!$this->availableProperties) {
                    $this->availableProperties = $this->getAvailableProperties(
                        $this->catalogPropsMapping,
                        $this->crossFilterMapping,
                        $this->filter,
                        $this->numericFieldsIds,
                        $this->richValues
                    );
                }
                return $this->availableProperties;
                break;
        }
    }

    /**
     * Конструктор класса
     * @param Material_Type $materialType Тип материала
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param array<
     *            int ID# поля | string URN поля | Material_Field поле
     *        > $ignored Игнорируемые поля
     */
    public function __construct(
        Material_Type $materialType,
        $withChildrenGoods = false,
        array $ignored = []
    ) {
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
        $st = microtime(1);
        $this->materialTypesIds = $this->materialType->selfAndChildrenIds;
        $properties = $this->getAllProperties(
            $this->materialTypesIds,
            $this->ignoredFields
        );
        foreach ($properties as $property) {
            $this->properties[(string)$property->id] = $property;
            $this->propertiesByURNs[$property->urn] = $property;
            if (in_array($property->datatype, ['number', 'range'])) {
                $this->numericFieldsIds[(string)$property->id] = (int)$property->id;
            }
        }
        $this->catalogGoodsIds = $this->getCatalogGoodsIds(
            $this->materialTypesIds
        );
        $this->propsMapping = $this->buildCache(
            $this->materialTypesIds,
            array_keys($this->properties),
            $this->catalogGoodsIds
        );
        $parents = $this->getPagesParents();
        $bubbledUpPagesMapping = $this->bubbleUpGoods(
            (array)$this->propsMapping['pages_ids'],
            $parents
        );
        $this->selfCounter = array_map('count', $this->propsMapping['pages_ids']);
        $this->counter = array_map('count', $bubbledUpPagesMapping);
        if ($this->withChildrenGoods) {
            $this->propsMapping['pages_ids'] = $bubbledUpPagesMapping;
        }
        $this->richValues = $this->getRichValues(
            $this->propsMapping,
            $this->properties
        );
        foreach ($this->propsMapping as $propId => $propData) {
            if ($propId != 'priority') {
                uksort($this->propsMapping[$propId], function ($a, $b) use ($propId) {
                    $aRich = isset($this->richValues[$propId][$a])
                           ? $this->richValues[$propId][$a]
                           : $a;
                    $bRich = isset($this->richValues[$propId][$b])
                           ? $this->richValues[$propId][$b]
                           : $b;
                    return strnatcasecmp($aRich, $bRich);
                });
            }
        }
    }


    /**
     * Формирует rich-значения для свойств, у которых они заведомо могут
     * отличаться от сырых значений
     * @param array<
     *            string[] ID# свойства => array<
     *                mixed[] значение => array<
     *                   string[] ID# товара => int ID# товара
     *                >
     *            >
     *        > $propsMapping Маппинг свойств к товарам
     * @param array<int[] ID# свойства => Material_Field> Список всех свойств
     * @return array<
     *             int[] ID# свойства (только те, у которых rich-значения
     *                        отличаются от сырых значений) => array<
     *                 mixed[] сырое значение => mixed rich-значение
     *             >
     *          >
     */
    public function getRichValues(array $propsMapping = [], array $properties = [])
    {
        $result = [];
        $materialPropsIds = $materialsToRetrieve = [];
        foreach ($propsMapping as $propId => $propValues) {
            $prop = $properties[$propId] ?? null;
            if ($prop && $prop->id) {
                if (in_array($prop->datatype, ['radio', 'select']) ||
                    (($prop->datatype == 'checkbox') && $prop->multiple)
                ) {
                    foreach ($propValues as $propValue => $propGoodsIds) {
                        $result[$propId][$propValue] = $prop->doRich($propValue);
                    }
                } elseif ($prop->datatype == 'material') {
                    $materialPropsIds[] = $propId;
                    foreach ($propValues as $propValue => $propGoodsIds) {
                        $result[$propId][$propValue] = $propValue;
                        $materialsToRetrieve[] = (int)$propValue;
                    }
                }
            }
        }
        // Обновим материалы по именам
        if ($materialsToRetrieve) {
            $sqlQuery = "SELECT id, name
                           FROM " . Material::_tablename()
                      . " WHERE id IN (" . implode(", ", $materialsToRetrieve) . ")";
            $sqlResult = Material::_SQL()->get($sqlQuery);
            $materialsNames = [];
            foreach ($sqlResult as $sqlRow) {
                $materialsNames[trim($sqlRow['id'])] = $sqlRow['name'];
            }
            foreach ($materialPropsIds as $propId) {
                foreach ((array)$result[$propId] as $propValue) {
                    if (isset($materialsNames[$propValue])) {
                        $result[$propId][trim($propValue)] = $materialsNames[$propValue];
                    }
                }
            }
        }
        return $result;
    }


    /**
     * Применение фильтра и каталога
     * @param Catalog $catalog Текущий каталог
     * @param array<string[] => mixed> $params Аналог $_GET
     */
    public function apply(Page $catalog, array $params = [])
    {
        $this->catalog = $catalog;
        $this->filter = $this->getFilter($params);
        // var_dump($this->filter); exit;
        $this->filterHasCheckedOptions = $this->getFilterHasCheckedOptions(
            $this->filter
        );
        // Получим список товаров в данной категории (без учета фильтра)
        $this->categoryGoodsIds = (array)$this->propsMapping['pages_ids'][$catalog->id];

        // Отфильтруем $this->propsMapping, оставив только те товары, которые есть
        // в данной категории ($this->categoryGoodsIds), попутно уберем из него
        // элемент pages_ids
        $this->catalogPropsMapping = $this->applyCatalog(
            $this->propsMapping,
            $this->catalog->id
        );

        // Выкинем из предыдущего пункта те свойства, которые не присутствуют в
        // фильтре и те значения, которые не подходят под фильтр.
        // Иными словами, в $filteredMapping остаются только те свойства
        // и значения, которые удовлетворяют фильтру
        $filteredMapping = $this->applyFilter(
            $this->catalogPropsMapping,
            $this->filter
        );

        // Объединим все товары по свойствам (не будем учитывать значения).
        // Иными словами, $goodsIdsMapping['price'] - это те ID# товаров, которые
        // находятся в текущей категории и удовлетворяют фильтру по цене.
        // Как и в предыдущем пункте, свойства только те, которые есть в фильтре.
        $this->goodsIdsMapping = $this->reduceMappingToGoodsIds($filteredMapping);

        // Получим кросс-фильтры.
        // В противоположность предыдущему пункту,
        // $crossFilterMapping['price'] - это все товары, у которых применяется
        // весь фильтр, кроме фильтра по цене (сделано для того, чтобы потом
        // получить все возможные значения по свойству для фильтра,
        // не обращая внимания на ограничения собственно по этому свойству)
        // ВАЖНАЯ ЧАСТЬ - под пустым индексом [''] находится список товаров, с
        // применением ВСЕГО фильтра, т.е. фактически отображаемые товары
        $this->crossFilterMapping = $this->applyCrossFilter(
            $this->goodsIdsMapping,
            (array)$this->categoryGoodsIds
        );

        // Отсортируем полный маппинг по значениям свойств и отберем только
        // список товаров с применением каталога и фильтра, а затем объединим по
        // свойствам
        $this->sortMapping = $this->getSortMapping(
            $this->catalogPropsMapping,
            $this->crossFilterMapping[''] // Фактически отображаемые товары
        );
    }


    /**
     * Получает все доступные ID# товаров
     * @param array<int> $mtypesIds ID# всех учитываемых типов материалов
     * @return array<string[] ID# товара => int ID# товара>
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
        $sqlResult = Material::_SQL()->getcol($sqlQuery);
        $catalogGoodsIds = [];
        foreach ($sqlResult as $val) {
            $catalogGoodsIds[(string)$val] = (int)$val;
        }
        return $catalogGoodsIds;
    }


    /**
     * Получает все свойства (кроме файлов, изображений и материалов)
     * @param array<int> $materialTypesIds ID# типов материалов
     * @param array<
     *            int ID# поля | string URN поля | Material_Field поле
     *        > $ignored Игнорируемые поля
     * @return array<Material_Field>
     */
    public function getAllProperties(
        array $materialTypesIds,
        array $ignored = []
    ) {
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
                        AND datatype NOT IN (?, ?)
                        AND pid IN (" . implode(", ", array_fill(0, count($materialTypesIds), '?')) . ") ";
        // 2019-02-12, AVS: убрал тип material, чтобы была возможность искать по бренду
        $sqlBind = array_merge([Material_Type::class, 'file', 'image'], $materialTypesIds);
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
     * @param array<
     *            string[] ID# товара => int ID# товара
     *        > $catalogGoodsIds ID# товаров, доступные по страницам
     *                           без учета фильтров
     * @return array<
     *             string[] ID# свойства => array<
     *                 mixed[] значение => array<
     *                     string[] ID# товара => int ID# товара
     *                 >
     *             >
     *         >
     */
    public function buildCache(
        array $mtypesIds,
        array $propertiesIds,
        array $catalogGoodsIds
    ) {
        $propsMapping = [];
        if ($this->useAvailabilityOrder && isset($this->propertiesByURNs[$this->useAvailabilityOrder])) {
            $availabilityProp = $this->propertiesByURNs[$this->useAvailabilityOrder];
        } else {
            $availabilityProp = null;
        }
        if ($catalogGoodsIds && $propertiesIds) {
            $sqlQuery = "SELECT *
                           FROM cms_data
                          WHERE pid IN (" . implode(", ", $catalogGoodsIds) . ")
                            AND fid IN (" . implode(", ", $propertiesIds) . ")";
            $sqlResult = Material::_SQL()->query($sqlQuery);
            foreach ($sqlResult as $sqlRow) {
                $value = trim($sqlRow['value']);
                if ($availabilityProp && ($sqlRow['fid'] == $availabilityProp->id)) {
                    $value = trim((int)(bool)$value);
                }
                $propsMapping[trim($sqlRow['fid'])][$value][(string)$sqlRow['pid']] = (int)$sqlRow['pid'];
            }
            // 2019-02-06, AVS: Пока уберем сортировку, сортировать будем
            // в build'е сразу по свойству $this->propsMapping
            // foreach ($propsMapping as $fid => $fieldData) {
            //     uksort($propsMapping[$fid], function ($a, $b) {
            //         return strnatcasecmp($a, $b);
            //     });
            //     // $propsMapping[$fid] = array_map('array_values', $propsMapping[$fid]);
            // }

            // Получим маппинг по страницам
            $sqlQuery = "SELECT *
                           FROM cms_materials_pages_assoc
                          WHERE id IN (" . implode(", ", $catalogGoodsIds) . ")";
            $sqlResult = Material::_SQL()->get($sqlQuery);
            foreach ($sqlResult as $sqlRow) {
                $propsMapping['pages_ids'][(string)$sqlRow['pid']][(string)$sqlRow['id']] = (int)$sqlRow['id'];
            }

            // Получим маппинг по порядку отображения
            $sqlQuery = "SELECT id, priority
                           FROM " . Material::_tablename()
                      . " WHERE id IN (" . implode(", ", $catalogGoodsIds) . ")";
            $sqlResult = Material::_SQL()->get($sqlQuery);
            $nonPrioritized = [];
            $prioritized = [];
            foreach ($sqlResult as $sqlRow) {
                $priority = (int)$sqlRow['priority'] ?: 999999999;
                $propsMapping['priority'][(string)$priority][(string)$sqlRow['id']] = (int)$sqlRow['id'];
            }
        }
        return $propsMapping;
    }


    /**
     * Переносит товары из дочерних категорий в родительские
     * @param array<
     *            int[] ID# страницы => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        > $pagesMapping Старое соответствие товаров страницам
     * @return array<
     *             int[] ID# страницы => int ID# родительской страницы
     *         > $parents Соответствие дочерних страниц родительским
     * @param array<
     *            int[] ID# страницы => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        >
     */
    public function bubbleUpGoods(array $pagesMapping, array $parents)
    {
        $newPagesMapping = $pagesMapping;
        foreach ($pagesMapping as $pageId => $goodsIds) {
            while ($pageId = $parents[$pageId]) {
                if (!isset($newPagesMapping[$pageId])) {
                    $newPagesMapping[$pageId] = [];
                }
                $newPagesMapping[$pageId] = $newPagesMapping[$pageId] + $goodsIds;
            }
        }
        // $newPagesMapping = array_map('array_unique', $newPagesMapping);
        // $newPagesMapping = array_map('array_filter', $newPagesMapping);
        // $newPagesMapping = array_map('array_values', $newPagesMapping);
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
                    // 2022-03-02, AVS: не работало булево "нет", т.к. из фильтра приходит 0, а в свойствах ""
                    if (($prop->datatype == 'checkbox') &&
                        !$prop->multiple &&
                        in_array(0, $val)
                    ) {
                        $filter[$prop->id][] = '';
                    }
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
     *            mixed[] значение => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        >> $propsMapping Старый маппинг
     * @param int $catalogId ID текущей категории
     * @return array<
     *          string[] ID# свойства => array<
     *              mixed[] значение => array<
     *                  string[] ID# товара => int ID# товара
     *              >
     *          >
     *      > Новый маппинг (после применения каталога, без pages_ids)
     */
    public function applyCatalog(array $propsMapping, $catalogId)
    {
        $filteredMapping = $propsMapping;
        unset($filteredMapping['pages_ids']);
        if ($catalogId) {
            $catalogGoodsIds = (array)$propsMapping['pages_ids'][$catalogId];
            // $catalogGoodsIdsFlipped = array_flip($catalogGoodsIds);
            foreach ($filteredMapping as $propVar => $propValues) {
                $filteredMapping[$propVar] = array_map(
                    function ($valueGoodsIds) use ($catalogGoodsIds) {
                        // $y = array_flip($valueGoodsIds);
                        $res = array_intersect_key($valueGoodsIds, $catalogGoodsIds);
                        return $res;
                    },
                    $propValues
                );
                // 2019-01-31, AVS: 0.15 секунд за итерацию - слишком много
                $filteredMapping[$propVar] = array_filter(
                    $filteredMapping[$propVar]
                );
            }
        }
        return $filteredMapping;
    }


    /**
     * Применить фильтр к маппингу
     * @param array<string[] ID# свойства => array<
     *            mixed[] значение => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        >> $propsMapping Старый маппинг
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @return array<string[] ID# свойства (только те, которые присутствуют в фильтре) => array<
     *             mixed[] значение => array<
     *                 string[] ID# товара => int ID# товара
     *             >
     *         >> Новый маппинг (после применения фильтра)
     */
    public function applyFilter(array $propsMapping, array $filter = [])
    {
        $filteredMapping = [];
        foreach ($filter as $propId => $filterValues) {
            if (isset($filterValues['from']) || isset($filterValues['to'])) {
                $newMappingValues = [];
                foreach ((array)$propsMapping[$propId] as $val => $valIds) {
                    if ((!isset($filterValues['from']) || ((float)$val >= (float)$filterValues['from'])) &&
                        (!isset($filterValues['to']) || ((float)$val <= (float)$filterValues['to']))) {
                        $newMappingValues[$val] = $valIds;
                    }
                }
                $filteredMapping[$propId] = $newMappingValues;
            } elseif (isset($filterValues['like'])) {
                $newMappingValues = [];
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
     *            mixed[] значение => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        >> $propsMapping Маппинг свойств
     * @return array<
     *             string[] ID# свойства => array<
     *                 string[] ID# товара => int ID# товара
     *             >
     *         >
     */
    public function reduceMappingToGoodsIds(array $propsMapping)
    {
        $propsToGoodsIds = [];
        foreach ($propsMapping as $propsVar => $propsValues) {
            if (!isset($propsToGoodsIds[$propsVar])) {
                $propsToGoodsIds[$propsVar] = [];
            }
            foreach ($propsValues as $propValue => $propValueIds) {
                $propsToGoodsIds[$propsVar] += (array)$propValueIds;
            }
            // $propsToGoodsIds[$propsVar] = array_values(array_unique($propsToGoodsIds[$propsVar]));
        }
        return $propsToGoodsIds;
    }


    /**
     * Получает список доступных ID# товаров по свойствам,
     * если бы для каждого свойства применялись ограничения всех остальных свойств кроме него
     *
     * @param array<
     *            string[] ID# свойства => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        > $goodsIdsMapping Маппинг свойств
     * @param array<
     *            string[] ID# товара => int ID# товара
     *        > $categoryGoodsIds ID# товаров, доступные на текущей странице
     *                            без учета фильтров
     * @return array<
     *             string[] ID# свойства => array<
     *                 string[] ID# товара => int ID# товара
     *             >
     *         > (под пустым индексом - все ограничения)
     */
    public function applyCrossFilter(array $goodsIdsMapping, array $categoryGoodsIds)
    {
        $crossFilterMapping = [];
        $goodsIdsMapping[''] = $categoryGoodsIds;
        foreach ($goodsIdsMapping as $propVar => $propIds) {
            $newMapping = $goodsIdsMapping;
            unset($newMapping[$propVar]);
            if ($newMapping) {
                $goodsIds = array_reduce(
                    $newMapping,
                    'array_intersect_key',
                    $categoryGoodsIds
                );
            } else {
                $goodsIds = $categoryGoodsIds;
            }
            $crossFilterMapping[$propVar] = $goodsIds;
        }
        return $crossFilterMapping;
    }


    /**
     * Получает переменные окружения по фильтру
     * @param array<
     *            string[] ID# свойства => array<mixed>|
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
     *            string[] ID# свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @param string|null $additionalPropertyURN URN дополнительного свойства
     *                                           для установки/снятия
     * @param mixed $additionalValue Дополнительное значение
     *                               для установки/снятия
     * @param bool $exclusive Использовать только дополнительное свойство
     * @return string
     * @throws Exception Выбрасывает исключение, если категория каталога не установлена
     */
    public function getCanonicalURLFromFilter(
        array $filter = [],
        $additionalPropertyURN = null,
        $additionalValue = null,
        $exclusive = false
    ) {
        if (!$this->catalog->id) {
            throw new Exception('Catalog is not set');
        }
        $params = $this->getURLParamsFromFilter($filter);

        if ($additionalPropertyURN) {
            $params[$additionalPropertyURN] = (array)$params[$additionalPropertyURN];
            $additionalKeys = array_keys(
                (array)$params[$additionalPropertyURN],
                $additionalValue
            );
            if ($exclusive) {
                unset($params[$additionalPropertyURN]);
                if (!$additionalKeys) {
                    $params[$additionalPropertyURN][] = $additionalValue;
                }
            } else {
                if ($additionalKeys) {
            // ob_end_clean(); var_dump($additionalKeys); exit;
                    foreach ($additionalKeys as $key) {
                        unset($params[$additionalPropertyURN][$key]);
                    }
                    $params[$additionalPropertyURN] = array_values($params[$additionalPropertyURN]);
                } else {
                    $params[$additionalPropertyURN][] = $additionalValue;
                }
            }
        }

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
        $urlSuffix = http_build_query($urlArray);
        $urlSuffix = $urlSuffix ? '?' . $urlSuffix : '';
        return $this->catalog->url . $urlSuffix;
    }


    /**
     * Получает доступные свойства
     * @param array<
     *            string[] ID# свойства => array<
     *                mixed[] значение => array<
     *                    string[] ID# товара => int ID# товара
     *                >
     *            >
     *        > $propsMapping Маппинг свойств
     * @param array<
     *            string[] ID# свойства => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        > $crossFilterMapping Список доступных ID# товаров по свойствам,
     *                              если бы для каждого свойства применялись ограничения
     *                              всех остальных свойств кроме него
     * @param array<
     *            string[] имя свойства => array<mixed>|
     *                                     ['from' => float, 'to' => float]|
     *                                     ['like' => string] значение или набор значений
     *        > $filter Фильтр для проверки
     * @param array<
     *            string ID# поля => int ID# поля
     *        > $numericFieldsIds Массив ID# числовых полей (number или range)
     * @param array<
     *           int[] ID# свойства (только те, у которых rich-значения
     *                      отличаются от сырых значений) => array<
     *               mixed[] сырое значение => mixed rich-значение
     *           >
     *        > $richValues Rich-значения свойств, у которых могут заведомо
     *                      отличаться от сырых значений
     * @return array<string[] ID# свойства => array<mixed[] значение => [
     *             'value' => mixed значение,
     *             'doRich' => mixed Отформатированное значение
     *             'prop' => Material_Field свойство, к которому относится значение
     *             'checked' => bool Установлено ли значение
     *             'enabled' => bool Активно ли значение
     *         ]>>
     */
    public function getAvailableProperties(
        array $propsMapping,
        array $crossFilterMapping,
        array $filter = [],
        array $numericFieldsIds = [],
        array $richValues = []
    ) {
        $filteredPropsMapping = [];
        foreach ($propsMapping as $propId => $propValues) {
            $prop = $this->properties ? $this->properties[$propId] : new Material_Field($propId);
            $crossFilterGoodsIds = $crossFilterMapping[$propId] ?: $crossFilterMapping[''];
            $filterPropValuesFlipped = [];
            if (isset($filter[$propId]) && !isset($numericFieldsIds[$propId])) {
                $filterPropValuesFlipped = array_flip(array_map('trim', (array)$filter[$propId]));
            }
            foreach ($propValues as $propValue => $goodsIds) {
                $valueData = [
                    'prop' => $prop,
                    'enabled' => (bool)array_intersect_key($goodsIds, $crossFilterGoodsIds)
                ];
                if (!isset($numericFieldsIds[$propId])) {
                    if (($prop->datatype == 'checkbox') && !$prop->multiple && ($propValue === '')) {
                        $propValue = 0;
                    }
                    $valueData['value'] = $propValue;
                    $valueData['doRich'] = isset($richValues[$propId][$propValue])
                                         ? $richValues[$propId][$propValue]
                                         : $propValue;
                    $valueData['checked'] = isset($filterPropValuesFlipped[$propValue]);
                }
                $filteredPropsMapping[$propId][$propValue] = $valueData;
            }
        }
        return $filteredPropsMapping;
    }


    /**
     * Получает маппинг по сортировке
     *
     * 2019-02-06, AVS: убрали собственно сортировку, сортировать будем в build'е
     * при создании кэша
     * @param array<
     *            string[] ID# свойства => array<
     *                mixed[] значение => array<
     *                    string[] ID# товара => int ID# товара
     *                >
     *            >
     *        > $catalogPropsMapping Маппинг свойств к товарам в пределах категории
     * @param array<
     *            string[] ID# товара => int ID# товара
     *        > $goodsIds ID# товаров после применения категории и фильтра
     * @return array<
     *            string[] ID# свойства => array<
     *                string[] ID# товара => int ID# товара
     *            >
     *        > Маппинг ID# товаров, отсортированных по значениям
     */
    public function getSortMapping($catalogPropsMapping, $goodsIds)
    {
        // var_dump($catalogPropsMapping); exit;
        $catalogReduced = $this->reduceMappingToGoodsIds($catalogPropsMapping);
        $sortMapping = [];
        foreach ($catalogReduced as $propId => $propGoodsIds) {
            // 2022-12-08, AVS: добавим товары без значения свойства, чтобы не было дополнительной фильтрации
            // при сортировке. По идее такого быть не должно, т.к. все товары должны обладать свойствами
            $restGoodsIds = array_diff($goodsIds, $propGoodsIds);
            $sortMapping[$propId] = array_intersect_key($propGoodsIds, $goodsIds) + $restGoodsIds;
        }
        $sortMapping[''] = $goodsIds;
        return $sortMapping;
    }


    /**
     * Множественная сортировка списка товаров
     * @param array $sort <pre>array<
     *     string ID# поля для сортировки (предваряемое знаком ! для обратной сортировки) |
     *     [
     *         string ID# поля для сортировки,
     *         callback(string $a, string $b): int Функция для сортировки значений | callback(string $x): int Функция перегруппировки значений,
     *         bool Используется функция перегруппировки значений
     *     ]
     * ></pre> Список сортировки
     * @param array $goodsIds <pre>array<
     *     string[] ID# товара => int ID# товара
     * ></pre> Исходный список товаров, null если все
     * @return array <pre>array<
     *     string[] ID# товара => int ID# товара
     * ></pre>
     */
    public function multisort(array $sort = [], array $goodsIds = [])
    {
        for ($i = count($sort) - 1; $i >= 0; $i--) {
            $currentSort = $sort[$i];
            if (is_array($currentSort)) {
                $sortProp = $currentSort[0];
                $order = $currentSort[1];
                $regroupFunction = (bool)$currentSort[2];
            } else {
                $currentSort = trim($currentSort);
                if ($currentSort[0] == '!') {
                    $sortProp = mb_substr($currentSort, 1);
                    $order = -1;
                } else {
                    $sortProp = $currentSort;
                    $order = 1;
                }
            }
            $referencedPropMapping = (array)$this->propsMapping[$sortProp];
            $referencedPropMapping = array_map(
                function ($x) use ($goodsIds) {
                    $y = array_intersect_key($goodsIds, $x);
                    return $y;
                },
                $referencedPropMapping
            );
            // Найдем недостающие товары
            // (у которых в свойстве вообще ничего не записано)
            // и добавим их с индексом ""
            $refGoodsIds = array_reduce(
                $referencedPropMapping,
                function ($carry, $item) {
                    return ($carry + $item);
                },
                []
            );
            $restGoodsIds = array_diff_key($goodsIds, $refGoodsIds);
            $referencedPropMapping[''] = (array)$referencedPropMapping[''] + $restGoodsIds;
            if ($order == -1) {
                krsort($referencedPropMapping, SORT_NATURAL);
                // $referencedPropMapping = array_reverse($referencedPropMapping, true);
            } elseif (is_callable($order)) {
                if ($regroupFunction) {
                    $regrouped = [];
                    foreach ($referencedPropMapping as $key => $refGoodsIds) {
                        $sortedKey = $order($key);
                        $regrouped[$sortedKey] = (array)$regrouped[$sortedKey] + $refGoodsIds;
                    }
                    $regrouped = array_map(
                        function ($x) use ($goodsIds) {
                            $y = array_intersect_key($goodsIds, $x);
                            return $y;
                        },
                        $regrouped
                    );
                    ksort($regrouped);
                    $referencedPropMapping = $regrouped;
                } else {
                    uksort($referencedPropMapping, $order);
                }
            } else {
                ksort($referencedPropMapping, SORT_NATURAL);
            }
            $goodsIds = array_reduce(
                $referencedPropMapping,
                function ($carry, $item) {
                    return ($carry + $item);
                },
                []
            );
        }

        return $goodsIds;
    }



    /**
     * Получает ID# товаров с учетом (или без учета) сортировки
     * @param string $sort URN поля для сортировки, либо пустая строка
     *                     для сортировки только по порядку отображения
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
        if ($this->useAvailabilityOrder) {
            $availabilityProp = $this->propertiesByURNs[$this->useAvailabilityOrder];
        }
        if ($availabilityPropId = (int)$availabilityProp->id) {
            $ids = $this->multisort([
                'priority',
                ('!' . $availabilityPropId),
                ((($order < 0) ? '!' : '') . $sortKey)
            ], (array)$this->sortMapping[$sortKey]);
        } else {
            $ids = $this->multisort([
                'priority',
                ((($order < 0) ? '!' : '') . $sortKey)
            ], (array)$this->sortMapping[$sortKey]);
        }
        return array_values($ids);
    }


    /**
     * Получает товары с учетом (или без учета) сортировки
     * @param string $sort URN поля для сортировки, либо пустая строка
     *                     для сортировки только по порядку отображения
     * @param Pages|null $pages Постраничная разбивка
     * @return array<Material>
     */
    public function getMaterials(Pages $pages = null, $sort = '', $order = 1)
    {
        $ids = $this->getIds($sort, $order);
        if (!$ids) {
            return [];
        }
        if ($pages) {
            do {
                $pageIds = array_slice(
                    $ids,
                    (int)$pages->from,
                    (int)$pages->rows_per_page
                );
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
     * @param bool $withChildrenGoods Учитывать дочерние категории
     * @return int
     */
    public function count($page, $withChildrenGoods = true)
    {
        $count = 0;
        $pageId = (int)(($page instanceof Page) ? $page->id : $page);
        if ($withChildrenGoods) {
            $counter = $this->counter;
        } else {
            $counter = $this->selfCounter;
        }
        if (isset($counter[$pageId])) {
            $count = $counter[$pageId];
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
     *                                 int[] ID# свойства => Material_Field
     *                             > Свойства,
     *             'catalogGoodsIds' => array<
     *                                      string[] ID# товара => int ID# товара
     *                                  > ID# товаров,
     *             'propsMapping' => array<
     *                                   string[] ID# свойства => array<
     *                                       mixed[] значение => array<
     *                                           string[] ID# товара => int ID# товара
     *                                       >
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
            'richValues' => $this->richValues,
            'numericFieldsIds' => $this->numericFieldsIds,
            'counter' => $this->counter,
            'selfCounter' => $this->selfCounter,
        ];
        return $result;
    }


    /**
     * Импортирует данные
     * @param [
     *             'materialType' => Material_Type,
     *             'withChildrenGoods' => bool Учитывать товары из дочерних категорий,
     *             'ignoredFields' => array<
     *                 int ID# поля | string URN поля
     *             > Игнорируемые поля,
     *             'materialTypesIds' => int ID# всех типов материалов,
     *             'properties' => array<
     *                                 int[] ID# свойства => Material_Field
     *                             > Свойства,
     *             'catalogGoodsIds' => array<
     *                                      string[] ID# товара => int ID# товара
     *                                  > ID# товаров,
     *             'propsMapping' => array<
     *                                   string[] ID# свойства => array<
     *                                       mixed[] значение => array<
     *                                           string[] ID# товара => int ID# товара
     *                                       >
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
            'richValues',
            'numericFieldsIds',
            'counter',
            'selfCounter',
        ] as $key) {
            if (!isset($data[$key])) {
                throw new Exception(
                    'Invalid import format - property ' . $key . ' is not set'
                );
            }
        }
        $materialType = $data['materialType'];
        $filter = new static(
            $materialType,
            (bool)$data['withChildrenGoods'],
            (array)$data['ignoredFields']
        );
        foreach ([
            'materialTypesIds',
            'catalogGoodsIds',
            'propsMapping',
            'richValues',
            'numericFieldsIds',
            'counter',
            'selfCounter',
        ] as $key) {
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
     * @param string|null $filename Путь, куда сохраняем. Если не указан,
     *                              будет использован путь по умолчанию
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
        // 2022-07-07, AVS: сделал условие для удаления файла, убрал @ чтобы видно было ошибки
        if (is_file($filename)) {
            unlink($filename);
        }
        rename($tmpFilename, $filename);
        if (!is_file($filename)) {
            throw new Exception('Cannot save filter cache data');
        }
    }


    /**
     * Загружает кэш из файла
     * @param Material_Type $materialType Тип материала
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param string|null $filename Путь, откуда загружаем. Если не указан,
     *                              будет использован путь по умолчанию
     * @param string|null $useAvailabilityOrder Использовать поле наличия (URN поля)
     * @return static
     * @throws Exception Выбрасывает исключение, если не удалось загрузить файл
     *                   (или каскадно, если файл не распознан)
     */
    public static function load(
        Material_Type $materialType,
        $withChildrenGoods = false,
        $filename = null,
        $useAvailabilityOrder = null
    ) {
        if (!$filename) {
            $filename = static::getDefaultFilename($materialType->id, $withChildrenGoods);
        }
        if (!is_file($filename)) {
            throw new Exception('Cannot load filter cache data - filename ' . $filename . ' doesn\'t exist');
        }
        $data = @include $filename;
        if (!$data) {
            throw new Exception('Cannot load filter cache data - data is empty or invalid');
        }
        $filter = static::import($data);
        if ($useAvailabilityOrder) {
            $filter->useAvailabilityOrder = $useAvailabilityOrder;
        }
        return $filter;
    }


    /**
     * Загружает или строит кэш
     * @param Material_Type $materialType Тип материала
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param array $ignored <pre><code>array<
     *     int ID# поля | string URN поля | Material_Field поле
     * ></code></pre> Игнорируемые поля
     * @param string|null $filename Путь для загрузки/сохранения. Если не указан, будет использован путь по умолчанию
     * @param bool $save Сохранять кэш при построении
     * @param string|null $useAvailabilityOrder Использовать поле наличия (URN поля)
     * @return static
     */
    public static function loadOrBuild(
        Material_Type $materialType,
        $withChildrenGoods = false,
        array $ignored = [],
        $filename = null,
        $save = true,
        $useAvailabilityOrder = null
    ) {
        try {
            $filter = static::load($materialType, $withChildrenGoods, $filename, $useAvailabilityOrder);
        } catch (Exception $e) {
            $filter = new static($materialType, $withChildrenGoods, $ignored);
            if ($useAvailabilityOrder) {
                $filter->useAvailabilityOrder = $useAvailabilityOrder;
            }
            $filter->build();
            if ($save) {
                $filter->save($filename);
            }
        }
        return $filter;
    }


    /**
     * Очищает кэши фильтров
     */
    public static function clearCaches()
    {
        $glob = glob(Package::i()->cacheDir . '/system/catalogfilter*.*');
        foreach ($glob as $file) {
            unlink($file);
        }
    }
}
