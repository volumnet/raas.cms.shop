<?php
/**
 * Файл трейта массового поиска сущностей
 *
 * Впоследствии предлагается реализовать в SOME
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material_Field;

/**
 * Трейт массового поиска сущностей
 */
trait BatchFindTrait
{
    /**
     * Ищет ID# материалов по типам и странице
     * @param Page $root Корневая страница для поиска
     * @param array<int> $materialTypesIds ID# типов материалов
     * @param bool $isGlobal Глобальный ли тип материала
     * @return array<int> ID# материалов
     */
    public function getMaterialsIdsByTypeAndPage(Page $root, array $materialTypesIds = [], $isGlobal = false)
    {
        $sqlQuery = "SELECT tM.id FROM " . Material::_tablename() . " AS tM ";
        $sqlBind = [];
        if (!$isGlobal) {
            $sqlQuery .= " LEFT JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc
                             AS tMPA
                             ON (tMPA.id = tM.id)";
        }
        if (!$materialTypesIds) {
            $materialTypesIds = [0];
        }
        $materialTypesIdsMask = array_fill(0, count($materialTypesIds), '?');
        $sqlQuery .= " WHERE tM.pid IN (" . implode(", ", $materialTypesIdsMask) . ")";
        $sqlBind = array_merge($sqlBind, $materialTypesIds);
        if (!$isGlobal) {
            $pageAndChildrenIds = $root->selfAndChildrenIds;
            $pageAndChildrenIdsMask = array_fill(0, count($pageAndChildrenIds), '?');
            $sqlQuery .= " AND (
                                   tMPA.pid IN (" . implode(", ", $pageAndChildrenIdsMask) . ")
                                OR tMPA.pid IS NULL
                            )";
            $sqlBind = array_merge($sqlBind, $pageAndChildrenIds);

        }
        $sqlQuery .= " GROUP BY tM.id
                       ORDER BY tM.id";
        $materialsIds = Material::_SQL()->getcol([$sqlQuery, $sqlBind]);
        $materialsIds = array_map('intval', $materialsIds);
        return $materialsIds;
    }


    /**
     * Ищет ID# полей с вложенными файлами
     * @param array<int> $materialTypesIds ID# типов материалов, у которых ищем поля
     * @return array<int> ID# полей с вложенными файлами
     */
    public function getAttachmentFieldsIds(array $materialTypesIds = [])
    {
        if (!$materialTypesIds) {
            $materialTypesIds = [0];
        }
        $materialTypesIdsMask = array_fill(0, count($materialTypesIds), '?');
        $sqlQuery = "SELECT tF.id FROM " . Material_Field::_tablename() . " AS tF
                      WHERE tF.classname = ?
                        AND tF.pid IN (" . implode(", ", $materialTypesIdsMask) . ")
                        AND datatype IN (?, ?)
                   ORDER BY tF.id";
        $sqlBind = array_merge([Material_Type::class], $materialTypesIds, ['image', 'file']);
        $fieldsIds = Material::_SQL()->getcol([$sqlQuery, $sqlBind]);
        $fieldsIds = array_map('intval', $fieldsIds);
        return $fieldsIds;
    }


    /**
     * Ищет ID# вложений по материалам и полям
     * @param array<int> $materialsIds ID# материалов/страниц
     * @param array<int> $fieldsIds ID# полей с вложенными файлами
     * @return array<int> ID# вложений
     */
    public function getAttachmentsIds(array $materialsIds = [], array $fieldsIds = [])
    {
        if (!$materialsIds) {
            $materialsIds = [0];
        }
        if (!$fieldsIds) {
            $fieldsIds = [0];
        }
        $materialsIdsMask = array_fill(0, count($materialsIds), '?');
        $fieldsIdsMask = array_fill(0, count($fieldsIds), '?');
        $attachmentsIds = [];
        $sqlQuery = "SELECT value FROM " . Material::_dbprefix() . "cms_data
                       WHERE pid IN (" . implode(", ", $materialsIdsMask) . ")
                         AND fid IN (" . implode(", ", $fieldsIdsMask) . ")";
        $sqlBind = array_merge($materialsIds, $fieldsIds);
        $sqlResult = Material::_SQL()->getcol([$sqlQuery, $sqlBind]);
        foreach ($sqlResult as $val) {
            if (preg_match('/"attachment":(\\d+)/i', $val, $regs)) {
                $attachmentsIds[] = (int)$regs[1];
            }
        }
        sort($attachmentsIds);
        return $attachmentsIds;
    }
}
