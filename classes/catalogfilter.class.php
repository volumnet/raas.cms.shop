<?php
/**
 * Файл класса фильтра каталога
 * @package RAAS.CMS
 * @version 4.3
 * @author Alex V. Surnin <info@volumnet.ru>
 * @copyright 2018, Volume Networks
 */
namespace RAAS\CMS\Shop;

use SOME\Singleton;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\Timer;

/**
 * Класс фильтра каталога
 * @property-read Page $catalog Текущий каталог
 * @property-read array<string[] URN свойства => int ID# свойства> $propertiesIdsByURNs Список ID# свойств по URN
 * @property-read array<
 *                    mixed[] ID# свойства => array<
 *                        mixed[] значение => array<int ID# товара>
 *                    >
 *                > $propsMapping Маппинг свойств к товарам
 * @property-read array<int> $catalogGoodsIds ID# товаров, доступные по странице без учета фильтров
 * @property-read array<mixed[] имя свойства => mixed|array<mixed> значение или набор значений> $filter Значения фильтра
 * @property-read array<
 *                    mixed[] ID# свойства => array<
 *                        mixed[] значение => array(
 *                            'value' => mixed значение,
 *                            'doRich' => string обработанное значение
 *                            'enabled' => bool Активно ли значение
 *                        )
 *                    >
 *                > $availableProperties Доступные для фильтра свойства
 * @property-read array<int> $goodsIds Доступные после фильтрации ID# товаров
 */
class CatalogFilter extends Singleton
{
    /**
     * Экземпляр фильтра
     */
    protected static $instance;

    /**
     * Каталог
     * @var Page
     */
    protected $catalog;

    /**
     * ID# товаров, доступные по странице без учета фильтров
     * @var array<int>
     */
    protected $catalogGoodsIds = array();

    /**
     * Маппинг свойств к товарам
     * @var array<
     *          mixed[] ID# свойства => array<
     *              mixed[] значение => array<int ID# товара>
     *          >
     *      >
     */
    protected $propsMapping = array();

    /**
     * Список ID# свойств по URN
     * @var array<string[] URN свойства => int ID# свойства>
     */
    protected $propertiesIdsByURNs = array();

    /**
     * Значения фильтра
     * @var array<mixed[] имя свойства => mixed|array<mixed> значение или набор значений>
     */
    protected $filter = array();

    /**
     * Доступные для фильтра свойства
     * @var array<
     *          mixed[] ID# свойства => array<
     *              mixed[] значение => array(
     *                  'value' => mixed значение,
     *                  'enabled' => bool Активно ли значение
     *              )
     *          >
     *      >
     */
    protected $availableProperties = array();

    /**
     * Доступные после фильтрации ID# товаров
     * @var array<int>
     */
    protected $goodsIds = array();

    public function __get($var)
    {
        switch ($var) {
            case 'catalog':
            case 'propertiesIdsByURNs':
            case 'propsMapping':
            case 'catalogGoodsIds':
            case 'filter':
            case 'availableProperties':
            case 'goodsIds':
                return $this->$var;
                break;
        }
    }


    /**
     * Инициализация фильтра через каталог
     * @param Material_Type $mtype Тип материала
     * @param Page|null $catalog Текущий каталог (null для всех)
     * @param bool $withChildrenGoods Учитывать товары из дочерних категорий
     * @param array<string[] => mixed> $params Аналог $_GET
     */
    public function build(Material_Type $mtype, Page $catalog = null, $withChildrenGoods = false, array $params = array())
    {
        $mtypesIds = $mtype->selfAndChildrenIds;
        $pagesIds = array();
        if ($catalog && !$mtype->global_type) {
            if ($withChildrenGoods) {
                $pagesIds = $catalog->selfAndChildrenIds;
            } else {
                $pagesIds = array($catalog->id);
            }
        }
        $this->propsMapping = $this->buildCache($mtypesIds, $pagesIds);

        $this->catalog = $catalog;
        $this->filter = $this->getFilter($params);
        $filteredMapping = $this->applyFilter($this->propsMapping, $this->filter);
        $goodsIdsMapping = $this->reduceMappingToGoodsIds($filteredMapping);
        $crossFilterMapping = $this->applyCrossFilter($goodsIdsMapping);
        $this->goodsIds = $crossFilterMapping[''];
        $this->availableProperties = $this->getAvailableProperties($this->propsMapping, $crossFilterMapping, $this->filter);
    }



    /**
     * Получает исходную таблицу свойств
     * @param array<int> $mtypesIds ID# всех учитываемых типов материалов
     * @param array<int> $pagesIds ID# всех учитываемых страниц
     * @return array<
     *          mixed[] ID# свойства => array<
     *              mixed[] значение => array<int ID# товара>
     *          >
     *      >
     */
    protected function buildCache(array $mtypesIds = array(), array $pagesIds = array())
    {
        $result = array();
        if ($mtypesIds) {
            $sqlQuery = "SELECT id, datatype, urn
                           FROM " . Material_Field::_tablename()
                      . " WHERE classname = 'RAAS\\\\CMS\\\\Material_Type'
                            AND pid IN (" . implode(", ", $mtypesIds) . ")";
            $sqlResult = Material_Field::_SQL()->query($sqlQuery);
            foreach ($sqlResult as $sqlRow) {
                if (!in_array($sqlRow['datatype'], array('image', 'file'))) {
                    $result['propertiesIdsByURNs'][$sqlRow['urn']] = $sqlRow['id'];
                }
            }
        }
        $propsMapping = array();
        $sqlQuery = "SELECT tM.id, tMPA.pid
                       FROM " . Material::_tablename() . " AS tM ";
        // if ($pagesIds) {
            $sqlQuery .= " LEFT JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id ";
        // }
        $sqlQuery .= " WHERE tM.vis
                         AND tM.pid IN (" . implode(", ", $mtypesIds) . ") ";
        // if ($pagesIds) {
        //     $sqlQuery .= " AND tMPA.pid IN (" . implode(", ", $pagesIds) . ") ";
        // }
        $sqlQuery .= " GROUP BY tM.id";
        $sqlResult = Material::_SQL()->query($sqlQuery);
        foreach ($sqlResult as $sqlRow) {
            // @todo
        }

        $this->catalogGoodsIds = Material::_SQL()->getcol($sqlQuery);
        if ($this->catalogGoodsIds && $this->propertiesIdsByURNs) {
            $sqlQuery = "SELECT *
                           FROM cms_data
                          WHERE pid IN (" . implode(", ", $this->catalogGoodsIds) . ")
                            AND fid IN (" . implode(", ", $this->propertiesIdsByURNs) . ")";
            $sqlResult = Material::_SQL()->query($sqlQuery);
            foreach ($sqlResult as $sqlRow) {
                $propsMapping[$sqlRow['fid']][trim($sqlRow['value'])][] = $sqlRow['pid'];
            }
        }
        $result['propsMapping'] = $propsMapping;
        return $result;
    }


    /**
     * Применить фильтр к маппингу
     * @param array<
     *          mixed[] ID# свойства => array<
     *              mixed[] значение => array<int ID# товара>
     *          >
     *      > $propsMapping Старый маппинг
     * @param array<
     *            mixed[] ID# свойства => array<mixed>|array(
     *                'from' => mixed,
     *                'to' => mixed
     *            )> $filter Значения для фильтрации
     * @return array<
     *          mixed[] ID# свойства (только те, которые присутствуют в фильтре) => array<
     *              mixed[] значение => array<int ID# товара>
     *          >
     *      > Новый маппинг (после применения фильтра)
     */
    public function applyFilter(array $propsMapping, array $filter = array())
    {
        $filteredMapping = array();
        foreach ($filter as $filterId => $filterValues) {
            $prop = new Material_Field($filterId);
            if ($prop->id) {
                if ($filterValues['from'] || $filterValues['to']) {
                    $newMappingValues = array();
                    foreach ($propsMapping[$filterId] as $value => $ids) {
                        if ($filterValues['from'] &&
                            ($this->compare(
                                (float)$value,
                                (float)$filterValues['from'],
                                $prop
                            ) < 0)
                        ) {
                            continue;
                        }
                        if ($filterValues['to'] &&
                            ($this->compare(
                                (float)$value,
                                (float)$filterValues['to'],
                                $prop
                            ) > 0)
                        ) {
                            continue;
                        }
                        $newMappingValues[$value] = $ids;
                    }
                } else {
                    $newMappingValues = array_intersect_ukey(
                        $propsMapping[$filterId],
                        array_flip($filterValues),
                        array($this, 'compare')
                    );
                }
                $filteredMapping[$filterId] = $newMappingValues;
            }
        }
        return $filteredMapping;
    }


    /**
     * Сравнение значений
     * @param mixed $value1 Значение 1
     * @param mixed $value2 Значение 2
     * @param Material_Field $field Поле, относительно которого проверяем
     * @return int 0, если совпадают, -1, если $value1 < $value2, 1, если $value1 > $value2
     */
    public function compare($value1, $value2, Material_Field $field)
    {
        $result = strnatcasecmp($value1, $value2);
        return $result;
    }


    /**
     * Получает список доступных ID# товаров по свойствам,
     * если бы применялись только ограничения этого свойства
     *
     * @param array<
     *            mixed[] ID# свойства => array<
     *                mixed[] значение => array<int ID# товара>
     *            >
     *        > $propsMapping Маппинг свойств
     * @return array<
     *             mixed[] ID# свойства => array<int ID# товара>
     *         >
     */
    public function reduceMappingToGoodsIds(array $propsMapping)
    {
        $propsToGoodsIds = array();
        foreach ($propsMapping as $propsVar => $propsValues) {
            $propsToGoodsIds[$propsVar] = array_reduce($propsValues, 'array_merge', array());
            $propsToGoodsIds[$propsVar] = array_values(array_unique($propsToGoodsIds[$propsVar]));
        }
        return $propsToGoodsIds;
    }


    /**
     * Получает список доступных ID# товаров по свойствам,
     * если бы для каждого свойства применялись ограничения всех остальных свойств кроме него
     *
     * @param array<mixed[] ID# свойства => array<int ID# товара>> $goodsIdsMapping Маппинг свойств
     * @return array<mixed[] ID# свойства => array<int ID# товара>>
     */
    public function applyCrossFilter(array $goodsIdsMapping)
    {
        $crossFilterMapping = array();
        $goodsIdsMapping[''] = $this->catalogGoodsIds;
        foreach ($goodsIdsMapping as $propVar => $propIds) {
            $newMapping = $goodsIdsMapping;
            unset($newMapping[$propVar]);
            if ($newMapping) {
                $goodsIds = array_reduce($newMapping, 'array_intersect', $this->catalogGoodsIds);
            } else {
                $goodsIds = $this->catalogGoodsIds;
            }
            $crossFilterMapping[$propVar] = $goodsIds;
        }
        return $crossFilterMapping;
    }


    /**
     * Составляет фильтр по переменным окружения
     * @param array<string[] => mixed> $params Аналог $_GET
     * @return array<
     *             mixed[] ID# свойства => array<mixed>|array(
     *                 'from' => mixed,
     *                 'to' => mixed
     *             )>
     *         > значение или набор значений>
     */
    public function getFilter(array $params)
    {
        $filter = array();
        foreach ($params as $key => $val) {
            if (stristr($key, '_from')) {
                $propURN = str_ireplace('_from', '', $key);
                $suffix = 'from';
            } elseif (stristr($key, '_to')) {
                $propURN = str_ireplace('_to', '', $key);
                $suffix = 'to';
            } else {
                $propURN = $key;
                $suffix = '';
            }
            $propId = $this->propertiesIdsByURNs[$propURN];
            if ($propId) {
                if ($suffix) {
                    $filter[$propId][$suffix] = trim($val);
                } else {
                    $filter[$propId] = array_unique((array)$val);
                }
            }
        }
        return $filter;
    }


    /**
     * Получает доступные свойства
     * @param array<
     *            mixed[] ID# свойства => array<
     *                mixed[] значение => array<int ID# товара>
     *            >
     *        > $propsMapping Маппинг свойств
     * @param array<
     *            mixed[] ID# свойства => array<int ID# товара>
     *        > $crossFilterMapping Список доступных ID# товаров по свойствам,
     *                              если бы для каждого свойства применялись ограничения
     *                              всех остальных свойств кроме него
     * @param array<
     *            mixed[] ID# свойства => array<mixed>|array(
     *                'from' => mixed,
     *                'to' => mixed
     *            )>
     *        > $filter Фильтр для проверки установленности
     * @return array<
     *             mixed[] ID# свойства => array<
     *                 mixed[] значение => array(
     *                     'value' => PropertyValue значение,
     *                     'prop' => Property свойство, к которому относится значение
     *                     'checked' => bool Установлено ли значение
     *                     'enabled' => bool Активно ли значение
     *                 )
     *             >
     *         >
     */
    public function getAvailableProperties(array $propsMapping, array $crossFilterMapping, array $filter)
    {
        $filteredPropsMapping = array();
        foreach ($propsMapping as $propId => $propValues) {
            $prop = new Material_Field($propId);
            foreach ($propValues as $propValue => $goodsIds) {
                $crossFilterGoodsIds = $crossFilterMapping[$propId] ?: $crossFilterMapping[''];
                $filteredPropsMapping[$propId][$propValueId] = array(
                    'value' => trim($propValue),
                    'prop' => $prop,
                    'checked' => in_array($propValueId, (array)$filter[$propId]),
                    'enabled' => (bool)array_intersect($goodsIds, $crossFilterGoodsIds)
                );
            }
        }
        return $filteredPropsMapping;
    }
}
