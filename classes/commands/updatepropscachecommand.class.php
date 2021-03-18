<?php
/**
 * Команда внутреннего кэширования свойств материалов
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Attachment;
use RAAS\Command;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Page;
use RAAS\CMS\Page_Field;
use RAAS\CMS\PageRecursiveCache;

/**
 * Команда внутреннего кэширования свойств материалов
 *
 * Предустановленные типы:
 * <pre><code>
 * <fieldData> = [
 *     'id' => int ID# поля,
 *     'name' => string Наименование поля,
 *     'urn' => string URN поля,
 *     'multiple' => bool Множественное поле,
 *     'datatype' => string Тип данных,
 *     'field' => Material_Field поле,
 *     'richNeeded' => bool Требуется ли обработка значения,
 * ];
 * <systemFieldData> = array_merge(<fieldData>, [
 *     'field' => Material_Field поле,
 *     'richNeeded' => bool Требуется ли обработка значения,
 * ]);
 * <materialValue> = [
 *     'id' => int ID# материала,
 *     'name' => string Наименование материала,
 *     'datatype' => string Тип данных,
 *     'url' =>? string URL материала
 * ];
 * <attachmentValue> = [
 *     'id' => int ID# вложения,
 *     'name' => string Наименование вложения,
 *     'description' => string Описание вложения,
 *     'fileURL' =>? string URL файла,
 *     'tnURL' =>? string URL описанного эскиза,
 *     'smallURL' =>? string URL вписанного эскиза,
 * ] ;
 * <valuedFieldData> = array_merge(<fieldData>, [
 *     'values' => array<
 *         int[] Индекс значения => string|<materialValue>|<attachmentValue> Значение
 *     >,
 * ]);
 * </code></pre>
 */
class UpdatePropsCacheCommand extends Command
{
    /**
     * Выполняет команду
     * @param string $materialTypeURN URN типа материалов
     * @param string $fieldsetsURNs URN наборов полей, разделенных запятой
     * @param string $additionalFieldsURNs URN дополнительных полей,
     *     разделенных запятой
     * @param bool $forceUpdate Обновить даже если это не требуется
     */
    public function process(
        $materialTypeURN = 'catalog',
        $fieldsetsURNs = 'main_props',
        $additionalFieldsURNs = 'price,available,images,step,min,brand,unit,rating,reviews_counter',
        $forceUpdate = false
    ) {
        if (!$forceUpdate && !$this->updateNeeded()) {
            $this->controller->doLog('Data is actual');
            return;
        }
        $materialType = Material_Type::importByURN($materialTypeURN);
        if (!$materialType->id) {
            $logMessage = 'Material type "' . $materialTypeURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $fieldsets = $additionalFields = [];
        $page = new Page();
        $fieldsetsURNs = explode(',', $fieldsetsURNs);
        $fieldsetsURNs = array_map('trim', $fieldsetsURNs);
        foreach ($fieldsetsURNs as $fieldsetURN) {
            $fieldset = $page->fields[$fieldsetURN];
            if (!$fieldset) {
                $logMessage = 'Page field "' . $fieldsetURN . '"' .
                    ' is not found';
                $this->controller->doLog($logMessage);
                return;
            }
            $fieldsets[] = $fieldset;
        }
        $additionalFieldsURNs = explode(',', $additionalFieldsURNs);
        $additionalFieldsURNs = array_map('trim', $additionalFieldsURNs);
        foreach ($additionalFieldsURNs as $additionalFieldURN) {
            $field = $materialType->fields[$additionalFieldURN];
            if (!$field->id) {
                $logMessage = 'Material field "' . $additionalFieldURN . '"' .
                    ' is not found';
                $this->controller->doLog($logMessage);
                return;
            }
            $additionalFields[] = $field;
        }

        $cacheData = $this->getProps($materialType, $fieldsets, $additionalFields);
        $this->controller->doLog('Cache data retrieved');

        $this->save($cacheData, $materialType);
        $this->controller->doLog('Cache updated');

    }


    /**
     * Определяет, требуется ли обновление
     */
    public function updateNeeded()
    {
        $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                       FROM " . Material::_tablename()
                  . " WHERE 1";
        $lastModifiedMaterialTimestamp = Material::_SQL()->getvalue($sqlQuery);
        $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                       FROM " . Page::_tablename()
                  . " WHERE 1";
        $lastModifiedPageTimestamp = Material::_SQL()->getvalue($sqlQuery);
        $lastModified = max(
            $lastModifiedMaterialTimestamp,
            $lastModifiedPageTimestamp
        );

        $cacheUpdated = strtotime(Module::i()->registryGet('cache_shop_props_updated'));
        return $lastModified > $cacheUpdated;
    }


    /**
     * Получает маппинг ID# полей по ID# категорий (с учетом рекурсии)
     * @param Page_Field $fieldset Поле с набором полей
     * @return array <pre><code>array<
     *     string[] ID# категории => int[] ID# поля
     * ></code></pre>
     */
    public function getFieldsetMapping(Page_Field $fieldset)
    {
        $props = [];
        // 1. Найдем точки задания характеристик
        $sqlQuery = "SELECT *
                       FROM cms_data
                      WHERE fid = ?
                        AND value != 0
                   ORDER BY pid, fii";
        $sqlResult = Material::_SQL()->get([$sqlQuery, (int)$fieldset->id]);
        foreach ($sqlResult as $sqlRow) {
            if ($sqlRow['value']) {
                $props[trim($sqlRow['pid'])][] = (int)$sqlRow['value'];
            }
        }

        // 2. Найдем маппинг к точкам задания характеристик
        $ch = PageRecursiveCache::i()->getChildrenIds(0);
        do {
            foreach ($ch as $childId) {
                $childData = PageRecursiveCache::i()->cache[$childId];
                if (!isset($props[$childId]) &&
                    isset($props[$childData['pid']])
                ) {
                    $props[$childId] = $props[$childData['pid']];
                }
            }
        } while ($ch = PageRecursiveCache::i()->getChildrenIds($ch));

        return $props;
    }


    /**
     * Получает данные поля
     * @param Field $field Поле
     * @return array <pre><code><systemFieldData></code></pre>
     */
    public function getFieldData(Material_Field $field)
    {
        $result = [
            'id' => (int)$field->id,
            'name' => trim($field->name),
            'multiple' => (bool)$field->multiple,
            'datatype' => trim($field->datatype),
            'field' => $field,
            'richNeeded' => in_array(
                    $field->datatype,
                    ['material', 'select', 'radio']
                ) || (
                    ($field->datatype == 'checkbox') &&
                    $field->multiple
                ),
        ];
        return $result;
    }


    /**
     * Получает список задействованных полей
     * @param array $fieldsetMappings <pre><code>array<
     *     string[] URN набора полей у страницы => array<
     *         string[] ID# категории => int[] ID# поля
     *     >
     * ></code></pre> Список маппингов ID# полей по ID# категорий
     * @param Material_Field[] $additionalFields Список дополнительных полей
     * @return array <pre><code>array<
     *     string[] ID# поля => <systemFieldData>
     * ></code></pre>
     */
    public function getAffectedFields(
        array $fieldsetMappings,
        array $additionalFields = []
    ) {
        $result = [];
        foreach ($fieldsetMappings as $fieldsetName => $fieldsetMapping) {
            foreach ($fieldsetMapping as $pageId => $fieldsIds) {
                foreach ($fieldsIds as $fieldId) {
                    if (!isset($result[$fieldId])) {
                        $field = new Material_Field($fieldId);
                        $result[trim($fieldId)] = $this->getFieldData($field);
                    }
                }
            }
        }
        foreach ($additionalFields as $field) {
            $fieldId = $field->id;
            if (!isset($result[$fieldId])) {
                $result[trim($fieldId)] = $this->getFieldData($field);
            }
        }
        return $result;
    }


    /**
     * Получает сырые данные товаров по полям
     * @param int[] $fieldsIds ID# полей
     * @return array <pre><code>array<
     *     string[] ID# товара => array<
     *         string[] ID# поля => array<
     *             string[] Индекс значения => string Сырое значение
     *         >
     *     >
     * ></code></pre>
     */
    public function getRawData(array $fieldsIds = [])
    {
        $result = [];
        $sqlQuery = "SELECT * FROM cms_data";
        if ($fieldsIds) {
            $sqlQuery .= " WHERE fid IN (" . implode(", ", $fieldsIds) . ")";
        }
        $sqlQuery .= " ORDER BY pid, fid, fii";
        $sqlResult = Material::_SQL()->query($sqlQuery);
        foreach ($sqlResult as $sqlRow) {
            $result[trim($sqlRow['pid'])][trim($sqlRow['fid'])][trim($sqlRow['fii'])] = $sqlRow['value'];
        }
        return $result;
    }


    /**
     * Получает ассоциацию товаров к категориям
     * @param Material_Type $materialType Тип материалов
     * @return array <pre><code>array<
     *     string[] ID# товара => array<
     *         string[] ID# категории => int ID# категории
     *     >
     * ></code></pre>
     */
    public function getMaterialsPagesAssoc(Material_Type $materialType)
    {
        $mTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds((int)$materialType->id);
        $result = [];
        $sqlQuery = "SELECT tMPA.*
                       FROM cms_materials_pages_assoc AS tMPA
                       JOIN " . Material::_tablename() . " AS tM ON tM.id = tMPA.id
                      WHERE tM.pid IN (" . implode(", ", $mTypesIds) . ")";
        $sqlResult = Material::_SQL()->get($sqlQuery);
        foreach ($sqlResult as $sqlRow) {
            $result[trim($sqlRow['id'])][trim($sqlRow['pid'])] = (int)$sqlRow['pid'];
        }
        return $result;
    }


    /**
     * Форматирует материальное значение
     * @param Material $material Материал-значение для форматирования
     * @return array <materialValue>
     */
    public function formatMaterialValue(Material $material)
    {
        $value = [
            'id' => $material->id,
            'name' => $material->name,
        ];
        if ($url = $material->url) {
            $value['url'] = $url;
        }
        return $value;
    }


    /**
     * Форматирует вложение-значение
     * @param Attachment $attachment Вложение-значение для форматирования
     * @return array <attachmentValue>
     */
    public function formatAttachmentValue(Attachment $attachment)
    {
        $value = [
            'id' => $attachment->id,
            'name' => $attachment->name,
            'description' => $attachment->description,
            'fileURL' => $attachment->fileURL,
        ];
        if ($attachment->image) {
            $value['tnURL'] = $attachment->tnURL;
            $value['smallURL'] = $attachment->smallURL;
        }
        return $value;
    }


    /**
     * Форматирует данные материалов
     * @param array $rawData <pre><code>array<
     *     string[] ID# товара => array<
     *         string[] ID# поля => array<
     *             string[] Индекс значения => string Сырое значение
     *         >
     *     >
     * ></code></pre> сырые данные
     * @param array $affectedFields <pre><code>array<
     *     string[] ID# поля => <systemFieldData>
     * ></code></pre> Задействованные поля
     * @return array <pre><code>array<
     *     string[] ID# товара => array<
     *         string[] ID# поля => <valuedFieldData>[]
     *     >
     * ></code></pre>
     */
    public function formatMaterialsData(array $rawData, array $affectedFields)
    {
        $cachedMaterials = [];
        $cachedAttachments = [];
        $result = [];
        foreach ($rawData as $materialId => $materialData) {
            foreach ($materialData as $fieldId => $fieldValues) {
                if ($fieldData = $affectedFields[$fieldId]) {
                    $values = [];
                    if ($fieldData['datatype'] == 'material') {
                        foreach ($fieldValues as $fii => $val) {
                            if (isset($cachedMaterials[$val])) {
                                $value = $cachedMaterials[$val];
                            } else {
                                $material = new Material($val);
                                if ($material->id != $val) {
                                    continue;
                                }
                                $value = $this->formatMaterialValue($material);
                                $cachedMaterials[trim($val)] = $value;
                            }
                            $values[] = $value;
                        }
                    } elseif (in_array(
                        $fieldData['datatype'],
                        ['file', 'image']
                    )) {
                        foreach ($fieldValues as $fii => $val) {
                            if (isset($cachedAttachments[$val])) {
                                $value = $cachedAttachments[$val];
                            } else {
                                $val = json_decode($val, true);
                                $attachment = new Attachment($val['attachment']);
                                foreach ($val as $k => $v) {
                                    if ($k != 'attachment') {
                                        $attachment->$k = $v;
                                    }
                                }
                                if (($attachment->id != $val['attachment']) ||
                                    !$attachment->vis
                                ) {
                                    continue;
                                }
                                $value = $this->formatAttachmentValue($attachment);
                                $cachedAttachments[trim($val['attachment'])] = $value;
                            }
                            $values[] = $value;
                        }
                    } elseif ($fieldData['richNeeded']) {
                        $field = $fieldData['field'];
                        foreach ($fieldValues as $fii => $val) {
                            $value = $field->doRich($val);
                            $values[] = $value;
                        }
                    } else {
                        $values = array_values(array_filter($fieldValues));
                    }
                    $resultFieldData = $fieldData;
                    unset($resultFieldData['field'], $resultFieldData['richNeeded']);
                    $resultFieldData['values'] = $values;
                    $result[$materialId][$fieldId] = $resultFieldData;
                }
            }
        }
        return $result;
    }


    /**
     * Получает набор свойств для товаров
     * @param Material_Type $materialType Тип материалов
     * @param Page_Field[] $fieldsets Наборы полей
     * @param Material_Field[] $additionalFields Дополнительные поля
     * @return array <pre><code>array<
     *     string[] ID# товара => array<
     *         string[] URN набора полей => array<
     *             string[] ID# страницы => <valuedFieldData>[]
     *         >
     *         string[] ID# поля => <valuedFieldData>
     *     >
     * ></code></pre>
     */
    public function getProps(
        Material_Type $materialType,
        array $fieldsets = [],
        array $additionalFields = []
    ) {
        $fieldsetsMappings = [];
        foreach ($fieldsets as $fieldset) {
            $fieldsetsMappings[trim($fieldset->urn)] = $this->getFieldsetMapping($fieldset);
        }
        $affectedFields = $this->getAffectedFields(
            $fieldsetsMappings,
            $additionalFields
        );

        if (!$affectedFields) {
            return [];
        }

        $rawData = $this->getRawData(array_keys($affectedFields));
        $materialsPagesAssoc = $this->getMaterialsPagesAssoc($materialType);
        $this->controller->doLog('Raw data retrieved');

        $materialsData = $this->formatMaterialsData($rawData, $affectedFields);
        $this->controller->doLog('Materials data formatted');

        $result = [];

        foreach ($fieldsetsMappings as $fieldSetURN => $fieldsetMapping) {
            foreach ($materialsPagesAssoc as $materialId => $pagesIds) {
                foreach ($pagesIds as $pageId) {
                    $result[$materialId][$fieldSetURN][$pageId] = [];
                    if ($fieldsIds = $fieldsetMapping[$pageId]) {
                        foreach ($fieldsIds as $fieldId) {
                            if ($materialFieldData = $materialsData[$materialId][$fieldId]) {
                                if ($materialFieldData['values']) {
                                    $result[$materialId][$fieldSetURN][$pageId][] = $materialFieldData;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($additionalFields as $field) {
            $fieldId = $field->id;
            if ($affectedField = $affectedFields[$fieldId]) {
                $fieldURN = $field->urn;
                foreach ($materialsData as $materialId => $materialData) {
                    if ($materialFieldData = $materialData[$fieldId]) {
                        $result[$materialId][$fieldURN] = $materialFieldData;
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Сохраняет данные кэша
     * @param array $cacheData <pre><code>array<
     *     string[] ID# товара => array<
     *         string[] URN набора полей => array<
     *             string[] ID# страницы => <valuedFieldData>[]
     *         >
     *         string[] ID# поля => <valuedFieldData>
     *     >
     * ></code></pre> Данные кэша
     * @param Material_Type $materialType Тип материалов
     */
    public function save(array $cacheData, Material_Type $materialType)
    {
        $table = Material::_tablename();
        $sqlArr = [];
        foreach ($cacheData as $materialId => $materialCache) {
            $sqlRow = [
                'id' => (int)$materialId,
                'cache_shop_props' => json_encode(
                    $materialCache,
                    JSON_UNESCAPED_UNICODE
                )
            ];
            $sqlArr[] = $sqlRow;
            // Material::_SQL()->update(
            //     $table,
            //     "id = " . (int)$materialId,
            //     [
            //         'cache_shop_props' => json_encode(
            //             $materialCache,
            //             JSON_UNESCAPED_UNICODE
            //         )
            //     ]
            // );
        }
        Material::_SQL()->add(
            $table,
            $sqlArr,
            ['cache_shop_props' => (object)"VALUES(cache_shop_props)"]
        );


        $mTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds((int)$materialType->id);
        $sqlWhere = "pid IN (" . implode(", ", $mTypesIds) . ")";
        if ($affectedMaterialsIds = array_keys($cacheData)) {
            $sqlWhere .= " AND id NOT IN (" . implode(", ", $affectedMaterialsIds) . ")";
        }
        Material::_SQL()->update(
            Material::_tablename(),
            $sqlWhere,
            ['cache_shop_props' => '']
        );
        Module::i()->registrySet(
            'cache_shop_props_updated',
            date('Y-m-d H:i:s')
        );
    }
}
