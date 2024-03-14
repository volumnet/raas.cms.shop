<?php
/**
 * Файл теста массового удаления сущностей
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Attachment;
use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Field;

/**
 * Класс теста массового удаления сущностей
 */
class BatchDeleteTraitTest extends BaseTest
{
    public static $tables = [
        'cms_pages',
        'cms_materials',
        'attachments',
        'cms_data',
        'cms_fields',
        'cms_material_types',
        'cms_materials_pages_assoc',
    ];

    /**
     * Тест удаления страниц по ID#
     */
    public function testDeletePagesByIds()
    {
        $trait = $this->getMockForTrait(BatchDeleteTrait::class);
        $page = new Page(20);

        $this->assertEquals(20, $page->id);

        $result = $trait->deletePagesByIds([20]);

        $page = new Page(20);

        $this->assertEmpty($page->id);
    }


    /**
     * Тест удаления вложений по ID#
     */
    public function testDeleteAttachmentsByIds()
    {
        $trait = $this->getMockForTrait(BatchDeleteTrait::class);
        $att = new Attachment(58);
        $file = $att->file;
        touch($file);

        $this->assertEquals(58, $att->id);
        $this->assertFileExists($att->file);

        $result = $trait->deleteAttachmentsByIds([58]);

        $item = new Attachment(58);

        $this->assertEmpty($item->id);
        $this->assertFileDoesNotExist($file);
    }


    /**
     * Тест удаления материалов по ID#
     */
    public function testDeleteMaterialsByIds()
    {
        $trait = $this->getMockForTrait(BatchDeleteTrait::class);
        $item = new Material(19);

        $this->assertEquals(19, $item->id);

        $result = $trait->deleteMaterialsByIds([19]);

        $item = new Material(19);

        $this->assertEmpty($item->id);
    }


    /**
     * Тест поиска собственно материалов для удаления, их задействованных attachment-поля и вложений для удаления
     */
    public function testFindMaterialsFieldsAndAttachmentsToClear()
    {
        $trait = $this->getMockForTrait(BatchDeleteTrait::class);

        $result = $trait->findMaterialsFieldsAndAttachmentsToClear(
            new Material_Type(4),
            new Page(15),
            [10, 11, 12, 13, 14, 16, 17, 18, 19]
        );

        $this->assertEquals([
            Material::class => [15],
            Field::class => [27, 29],
            Attachment::class => [46, 47, 48],
        ], $result);
    }


    /**
     * Тест поиска собственно страниц для удаления, их задействованных attachment-поля и вложений для удаления
     */
    public function testFindPagesFieldsAndAttachmentsToClear()
    {
        $trait = $this->getMockForTrait(BatchDeleteTrait::class);

        $result = $trait->findPagesFieldsAndAttachmentsToClear(new Page(15), [16, 17, 18, 19, 20, 21, 22, 23]);

        $this->assertEquals([
            Page::class => [24],
            Field::class => [2, 4],
            Attachment::class => [27],
        ], $result);
    }
}
