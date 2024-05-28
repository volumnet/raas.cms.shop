<?php
/**
 * Файл трейта массового удаления сущностей
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Attachment;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Page_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Field;

/**
 * Трейт массового удаления сущностей
 */
trait BatchDeleteTrait
{
    use BatchFindTrait;

    /**
     * Удаляем страницы по ID#
     * @param array<int> $ids ID# страниц для удаления
     */
    public function deletePagesByIds(array $ids = [])
    {
        if ($ids) {
            $idsMask = array_fill(0, count($ids), '?');
            $sqlQuery = "DELETE FROM " . Page::_tablename()
                      . " WHERE id IN (" . implode(", ", $idsMask) . ")";
            Page::_SQL()->query([$sqlQuery, $ids]);

            // Очищаем привязку к страницам
            $sqlQuery = "DELETE FROM " . Material::_dbprefix() . "cms_materials_pages_assoc
                          WHERE pid IN (" . implode(", ", $idsMask) . ")";
            Material::_SQL()->query([$sqlQuery, $ids]);

            // Очищаем данные
            $sqlQuery = "DELETE tD
                            FROM " . Page::_dbprefix() . "cms_data AS tD
                            JOIN " . Page_Field::_tablename() . " AS tF ON tF.id = tD.fid
                           WHERE tF.classname = ?
                             AND tF.pid = ?
                             AND tD.pid IN (" . implode(", ", $idsMask) . ")";
            Page::_SQL()->query([$sqlQuery, array_merge([Material_Type::class, 0], $ids)]);
        }
    }


    /**
     * Удаляем вложения по ID#
     * @param array<int> $ids ID# вложений
     */
    public function deleteAttachmentsByIds(array $ids = [])
    {
        if ($ids) {
            $idsMask = array_fill(0, count($ids), '?');
            $sqlQuery = "SELECT realname
                           FROM " . Attachment::_tablename()
                      . " WHERE id IN (" . implode(", ", $idsMask) . ")";
            $filesToClear = Material::_SQL()->getcol([$sqlQuery, $ids]);

            // Чистим файлы
            foreach ($filesToClear as $val) {
                $val = realpath(Package::i()->filesDir) . '/' . str_replace('.', '*.', $val);
                $arr = glob($val);
                foreach ($arr as $row) {
                    unlink($row);
                }
            }

            // Чистим сами attachment'ы
            $sqlQuery = "DELETE FROM " . Attachment::_tablename()
                      . " WHERE id IN (" . implode(", ", $idsMask) . ")";
            Material::_SQL()->query([$sqlQuery, $ids]);
        }
    }


    /**
     * Удаляем материалы по ID#
     * @param array<int> $ids ID# материалов для удаления
     */
    public function deleteMaterialsByIds(array $ids = [])
    {
        if ($ids) {
            $idsMask = array_fill(0, count($ids), '?');
            $sqlQuery = "DELETE FROM " . Material::_tablename()
                      . " WHERE id IN (" . implode(", ", $idsMask) . ")";
            Material::_SQL()->query([$sqlQuery, $ids]);

            $sqlQuery = "DELETE FROM " . Material::_dbprefix() . "cms_materials_pages_assoc
                          WHERE id IN (" . implode(", ", $idsMask) . ")";
            Material::_SQL()->query([$sqlQuery, $ids]);

            $sqlQuery = "DELETE tD
                           FROM " . Material::_dbprefix() . "cms_data AS tD
                           JOIN " . Material_Field::_tablename() . " AS tF ON tF.id = tD.fid
                          WHERE tF.classname = ?
                            AND tF.pid > ?
                            AND tD.pid IN (" . implode(", ", $idsMask) . ")";
            Material::_SQL()->query([$sqlQuery, array_merge([Material_Type::class, 0], $ids)]);
        }
    }


    /**
     * Ищет собственно материалы для удаления, их задействованные attachment-поля и вложения для удаления
     * @param Material_Type $materialType Тип материалов
     * @param Page $deleteRoot Корень для удаления материалов
     * @param array<int> $affectedMaterialsIds Массив ID# материалов, не подлежащих удалению
     * @return [
     *             Material::class => array<int>,
     *             Field::class => array<int>,
     *             Attachment::class => array<int>
     *         ]
     */
    public function findMaterialsFieldsAndAttachmentsToClear(
        Material_Type $materialType,
        Page $deleteRoot,
        array $affectedMaterialsIds = []
    ) {
        // Ищем задействованные типы
        $materialTypesIds = array_map('intval', $materialType->selfAndChildrenIds);

        // Ищем материалы для удаления
        $materialsToClearIds = $this->getMaterialsIdsByTypeAndPage(
            $deleteRoot,
            $materialTypesIds,
            $materialType->is_global
        );
        $materialsToClearIds = array_diff($materialsToClearIds, array_map('intval', $affectedMaterialsIds));
        $materialsToClearIds = array_values($materialsToClearIds);

        // Ищем поля картинок и файлов (с attachment'ами)
        $fieldsToClearIds = $this->getAttachmentFieldsIds($materialTypesIds);

        // Ищем attachment'ы для удаления
        $attachmentsToClearIds = $this->getAttachmentsIds($materialsToClearIds, $fieldsToClearIds);
        return [
            Material::class => $materialsToClearIds,
            Field::class => $fieldsToClearIds,
            Attachment::class => $attachmentsToClearIds,
        ];
    }


    /**
     * Ищет собственно страницы для удаления, их задействованные attachment-поля и вложения для удаления
     * @param Page $deleteRoot Корень для удаления материалов
     * @param array<int> $affectedPagesIds Массив ID# страниц, не подлежащих удалению
     * @return [
     *             Page::class => array<int>,
     *             Field::class => array<int>,
     *             Attachment::class => array<int>
     *         ]
     */
    public function findPagesFieldsAndAttachmentsToClear(Page $deleteRoot, array $affectedPagesIds = [])
    {
        // Ищем страницы для удаления
        $pagesToClearIds = array_diff($deleteRoot->all_children_ids, array_map('intval', $affectedPagesIds));
        $pagesToClearIds = array_values($pagesToClearIds);

        // Ищем поля картинок и файлов (с attachment'ами)
        $fieldsToClearIds = $this->getAttachmentFieldsIds([0]);

        // Ищем attachment'ы для удаления
        $attachmentsToClearIds = $this->getAttachmentsIds($pagesToClearIds, $fieldsToClearIds);

        return [
            Page::class => $pagesToClearIds,
            Field::class => $fieldsToClearIds,
            Attachment::class => $attachmentsToClearIds,
        ];
    }
}
