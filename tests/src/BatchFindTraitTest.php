<?php
/**
 * Файл теста массового поиска сущностей
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\CMS\Page;

/**
 * Класс теста массового поиска сущностей
 */
class BatchFindTraitTest extends BaseTest
{
    public static $tables = [
        'cms_pages',
        'cms_materials',
        'cms_data',
        'cms_fields',
        'cms_materials_pages_assoc',
    ];

    /**
     * Провайдер данных для функции testGetMaterialsIdsByTypeAndPage
     * @return array<array<
     *             Page Корневая страница для поиска
     *             array<int> ID# типов материалов
     *             bool Глобальный ли тип материала
     *             mixed Ожидаемый результат
     *         >>
     */
    public static function getMaterialsIdsByTypeAndPageDataProvider()
    {
        $page = new Page(15);
        return [
            [$page, [4], false, [10, 11, 12, 13, 14, 15, 16, 17, 18, 19]],
            [$page, [2], true, [4, 5, 6]],
            [$page, [], true, []],
        ];
    }

    /**
     * Тест поиска ID# материалов по типам и странице
     * @param Page $root Корневая страница для поиска
     * @param array<int> $materialTypesIds ID# типов материалов
     * @param bool $isGlobal Глобальный ли тип материала
     * @param mixed $expected Ожидаемый результат
     */
    #[DataProvider('getMaterialsIdsByTypeAndPageDataProvider')]
    public function testGetMaterialsIdsByTypeAndPage(Page $root, array $materialTypesIds, $isGlobal, $expected)
    {
        $trait = new class {
            use BatchFindTrait;
        };

        $result = $trait->getMaterialsIdsByTypeAndPage($root, $materialTypesIds, $isGlobal);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест поиска ID# полей с вложенными файлами
     */
    public function testGetAttachmentFieldsIds()
    {
        $trait = new class {
            use BatchFindTrait;
        };

        $result = $trait->getAttachmentFieldsIds([4]);

        $this->assertEquals([27, 29], $result);
    }


    /**
     * Тест поиска ID# полей с вложенными файлами (случай с пустым массивом)
     */
    public function testGetAttachmentFieldsIdsWithEmptyMaterialTypesIds()
    {
        $trait = new class {
            use BatchFindTrait;
        };

        $result = $trait->getAttachmentFieldsIds([]);

        $this->assertEquals([2, 4], $result);
    }


    /**
     * Тест поиска ID# вложений по материалам и полям
     */
    public function testGetAttachmentsIds()
    {
        $trait = new class {
            use BatchFindTrait;
        };

        $result = $trait->getAttachmentsIds([10, 11, 12], [27, 29]);

        $this->assertEquals([28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39], $result);
    }


    /**
     * Тест поиска ID# вложений по материалам и полям (случай с пустым массивом)
     */
    public function testGetAttachmentsIdsWithEmptyMaterialsIdsAndFieldsIds()
    {
        $trait = new class {
            use BatchFindTrait;
        };

        $result = $trait->getAttachmentsIds([], []);

        $this->assertEquals([], $result);
    }
}
