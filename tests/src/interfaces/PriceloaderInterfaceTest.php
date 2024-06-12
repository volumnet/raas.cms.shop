<?php
/**
 * Файл теста интерфейса загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use SOME\CSV;
use RAAS\Attachment;
use RAAS\Exception;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс теста интерфейса загрузчика прайсов
 * @covers RAAS\CMS\Shop\PriceloaderInterface
 */
class PriceloaderInterfaceTest extends BaseTest
{
    public static $tables = [
        'attachments',
        'cms_access',
        'cms_access_materials_cache',
        'cms_access_pages_cache',
        'cms_blocks',
        'cms_blocks_form',
        'cms_blocks_html',
        'cms_blocks_material',
        'cms_blocks_material_filter',
        'cms_blocks_material_sort',
        'cms_blocks_menu',
        'cms_blocks_pages_assoc',
        'cms_blocks_search_pages_assoc',
        'cms_data',
        'cms_fields',
        'cms_forms',
        'cms_material_types',
        'cms_material_types_affected_pages_for_materials_cache',
        'cms_material_types_affected_pages_for_self_cache',
        'cms_materials',
        'cms_materials_affected_pages_cache',
        'cms_materials_pages_assoc',
        'cms_menus',
        'cms_pages',
        'cms_shop_blocks_yml_pages_assoc',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_priceloaders',
        'cms_shop_priceloaders_columns',
        'cms_snippets',
        'cms_templates',
        'cms_users', // Только для одиночного теста
        'registry',
    ];

    /**
     * Получает интерфейс загрузчика прайсов
     * @return PriceLoader
     */
    public function getInterface()
    {
        $loader = new PriceLoader(1);
        $interface = new PriceloaderInterface($loader);
        return $interface;
    }


    /**
     * Тест функции process (заглушки)
     */
    public function testProcess()
    {
        $interface = $this->getInterface();

        $result = $interface->process();

        $this->assertNull($result);
    }


    /**
     * Тест получения загрузчика
     */
    public function testTestGetLoader()
    {
        $loader = new PriceLoader(1);

        $interface = new PriceloaderInterface($loader);

        $this->assertEquals($loader, $interface->loader);
    }


    /**
     * Тест получения несуществующего свойства
     */
    public function testTestGetWithNonExistingProperty()
    {
        $loader = new PriceLoader(1);

        $interface = new PriceloaderInterface($loader);

        $this->assertNull($interface->aaa);
    }


    /**
     * Тест приведения данных к нужному виду
     */
    public function testAdjustData()
    {
        $interface = $this->getInterface();
        $data = [
            ['Данные 1'],
            ['', 'Данные 2'],
            ['', '', 'Данные 3']
        ];

        $data2 = $interface->adjustData($data, -3, -2);

        $this->assertEquals([
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', 'Данные 1'],
            ['', '', '', 'Данные 2'],
            ['', '', '', '', 'Данные 3']
        ], $data2);
    }


    /**
     * Тест приведения данных к нужному виду (случай с отрицательными значениями)
     */
    public function testAdjustDataWithNegativeValues()
    {
        $interface = $this->getInterface();
        $data = [
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', 'Данные 1'],
            ['', '', '', 'Данные 2'],
            ['', '', '', '', 'Данные 3']
        ];

        $data2 = $interface->adjustData($data, 2, 1);

        $this->assertEquals([
            ['', 'Данные 1'],
            ['', '', 'Данные 2'],
            ['', '', '', 'Данные 3']
        ], $data2);
    }


    /**
     * Провайдер данных для метода testGetUniqueColumnIndex
     * @return array<[
     *             string|null Установить уникальное поле с именем (для нативного)
     *                         или ID (для кастомного) равным значению
     *                         (null - не устанавливать)
     *             int Ожидаемое значение индекса уникальной колонки
     *         ]>
     */
    public function getUniqueColumnIndexDataProvider()
    {
        return [
            [null, 0],
            ['name', 1],
            ['26', 6]
        ];
    }


    /**
     * Тест получения номера уникальной колонки
     * @param string|null $setUFID Установить уникальное поле с именем (для нативного)
     *                             или ID (для кастомного) равным значению
     *                             (null - не устанавливать)
     * @param int $expectedUniqueColumnIndex Ожидаемое значение индекса уникальной колонки
     * @dataProvider getUniqueColumnIndexDataProvider
     */
    public function testGetUniqueColumnIndex($setUFID, $expectedUniqueColumnIndex)
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        if ($setUFID) {
            $interface->loader->ufid = $setUFID;
        }
        $i = $interface->getUniqueColumnIndex($loader);

        $this->assertEquals($expectedUniqueColumnIndex, $i);
    }


    /**
     * Тест получения номера уникальной колонки - случай, когда уникальная колонка не установлена
     */
    public function testGetUniqueColumnIndexWithNoUniqueColumn()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->ufid = '';

        $i = $interface->getUniqueColumnIndex($loader);

        $this->assertNull($i);
    }


    /**
     * Тест получения номера уникальной колонки - случай, когда уникальная колонка некорректна
     */
    public function testGetUniqueColumnIndexWithInvalidColumn()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->ufid = 'abc';

        $i = $interface->getUniqueColumnIndex($loader);

        $this->assertNull($i);
    }


    /**
     * Тест проверки, относится ли строка к товару
     */
    public function testIsItemDataRow()
    {
        $interface = $this->getInterface();

        $result = $interface->isItemDataRow(['', 'aaa', '', 'bbb', 'ccc']);

        $this->assertTrue($result);
    }


    /**
     * Тест проверки, относится ли строка к товару (случай строки с одним значением)
     */
    public function testIsItemDataRowWithOneValueRow()
    {
        $interface = $this->getInterface();

        $result = $interface->isItemDataRow(['', '', '', 'bbb', '']);

        $this->assertFalse($result);
    }


    /**
     * Тест проверки, относится ли строка к товару (случай строки с одним значением и без использования категорий)
     */
    public function testIsItemDataRowWithOneValueRowAndNoCatsUsage()
    {
        $loader = new PriceLoader(1);
        $loader->cats_usage = PriceLoader::CATS_USAGE_DONT_USE;
        $interface = new PriceloaderInterface($loader);

        $result = $interface->isItemDataRow(['', '', '', 'bbb', '']);

        $this->assertTrue($result);
    }


    /**
     * Провайдер данных для метода testConvertCell
     * @return array<[
     *             PriceLoader_Column Колонка загрузчика прайсов
     *             mixed Значение для преобразования
     *             mixed Ожидаемое значение
     *         ]>
     */
    public function convertCellDataProvider()
    {
        $customMaterialCol = new PriceLoader_Column(4);
        $customMaterialCol->callback = 'namespace RAAS\CMS;
                                        return [new Material(10), new Material(11), new Material(12)];';
        return [
            [new PriceLoader_Column(['pid' => 1, 'fid' => 'vis']), 'aaa', 1],
            [new PriceLoader_Column(5), 'в наличии', 1],
            [new PriceLoader_Column(5), 'под заказ', 0],
            [new PriceLoader_Column(4), 'f4dbdf21, 83dcefb7, 1ad5be0d', [10, 11, 12]],
            [$customMaterialCol, '', [10, 11, 12]],
        ];
    }


    /**
     * Тест применения к ячейке данных callback-преобразования
     * @param PriceLoader_Column $col Колонка загрузчика прайсов
     * @param mixed $valueToConvert Значение для преобразования
     * @param mixed $expected Ожидаемое значение
     * @dataProvider convertCellDataProvider
     */
    public function testConvertCell(PriceLoader_Column $col, $valueToConvert, $expected)
    {
        $interface = $this->getInterface();

        $result = $interface->convertCell($col, $valueToConvert);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест применения к ячейке данных callback-преобразования - случай с полем priority
     */
    public function testConvertCellWithPriorityColumn()
    {
        $col = new PriceLoader_Column(['pid' => 1, 'fid' => 'priority', 'priority' => 10]);
        $interface = $this->getInterface();

        $result = $interface->convertCell($col, '20aaa');

        $this->assertEquals(20, $result);
    }


    /**
     * Тест применения к ячейке данных callback-преобразования - случай с множественным полем и пустыми значениями
     */
    public function testConvertCellWithMultipleFieldWithEmptyValues()
    {
        $field = new Field(25); // Артикул
        $field->multiple = true;
        $field->commit();
        $col = new PriceLoader_Column(['pid' => 1, 'fid' => 25]);
        $interface = $this->getInterface();

        $result = $interface->convertCell($col, ['', 'aaa', '', 'bbb']);

        $this->assertEquals(['', 'aaa', '', 'bbb'], $result);

        $field->multiple = false;
        $field->commit();
    }


    /**
     * Провайдер данных для метода testGetItemsByUniqueField
     * @return array<[
     *             string|null Установить уникальное поле с именем (для нативного)
     *                         или ID (для кастомного) равным значению
     *                         (null - не устанавливать)
     *             mixed Значение для поиска
     *             mixed Ожидаемое значение
     *         ]>
     */
    public function getItemsByUniqueFieldDataProvider()
    {
        return [
            [null, '1ad5be0d', ['Товар 3']],
            [32, 2, ['Товар 1', 'Товар 5', 'Товар 9']],
            ['name', 'Товар 1', ['Товар 1']],
            ['name', 'asweoisdjklfn', []],
        ];
    }


    /**
     * Тест поиска товара по уникальному полю
     * @param string|null $setUFID Установить уникальное поле с именем (для нативного)
     *                             или ID (для кастомного) равным значению
     *                             (null - не устанавливать)
     * @param mixed $valueToFind Значение для поиска
     * @param mixed $expected Ожидаемое значение имени товара
     * @dataProvider getItemsByUniqueFieldDataProvider
     */
    public function testGetItemsByUniqueField($setUFID, $valueToFind, $expected)
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        if ($setUFID) {
            $interface->loader->ufid = $setUFID;
        }
        $result = $interface->getItemsByUniqueField($loader, $valueToFind);

        $this->assertIsArray($result);

        $result = array_map(function ($x) {
            return $x->name;
        }, $result);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест поиска товара по уникальному полю - случай, когда нет данных или не установлена уникальная колонка
     */
    public function testGetItemsByUniqueFieldWithEmptyValue()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $result = $interface->getItemsByUniqueField($loader, '');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }


    /**
     * Тест поиска товара по всем полям
     */
    public function testGetItemsByEntireRow()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $row = ['f4dbdf21', 'Товар 1', '', '', 0, 0, 83620];

        $result = $interface->getItemsByEntireRow($loader, $row);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Товар 1', $result[0]->name);
    }


    /**
     * Тест поиска товара по всем полям - случай, когда не найдено
     */
    public function testGetItemsByEntireRowWithNoItemsFound()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $row = ['aaa', 'bbb', '', '', 0, 0, 83620];

        $result = $interface->getItemsByEntireRow($loader, $row);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }


    /**
     * Тест создания материала (без коммита) согласно настройкам загрузчика
     */
    public function testCreateItem()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $materialType = $loader->Material_Type;

        $item = $interface->createItem($loader);

        $this->assertEquals(1, $item->vis);
        $this->assertEquals($materialType->id, $item->pid);
        $this->assertNull($item->id);
    }


    /**
     * Провайдер данных для метода testApplyNativeField
     * @return array<[
     *             PriceLoader_Column Колонка загрузчика прайсов
     *             Material Материал, который нужно обновить
     *             mixed Значение для установки
     *             bool Поле уникальное
     *             array<string[] поле => mixed значение> Массив для проверки
     *         ]>
     */
    public function applyNativeFieldDataProvider()
    {
        return [
            [
                new PriceLoader_Column(2),
                new Material(['pid' => 4, 'vis' => 1]),
                'Новый товар',
                true,
                ['id' => null, 'name' => 'Новый товар']
            ],
            [
                new PriceLoader_Column(2),
                new Material(10),
                'Новый товар',
                false,
                ['id' => 10, 'name' => 'Новый товар']
            ],
            [
                new PriceLoader_Column(2),
                new Material(10),
                'Новый товар',
                true,
                ['id' => 10, 'name' => 'Товар 1']
            ]
        ];
    }


    /**
     * Тест применения нативного поля
     * @param PriceLoader_Column $col Колонка загрузчика прайсов
     * @param Material $material Материал, который нужно обновить
     * @param mixed $valueToSet Значение для установки
     * @param bool $isUnique Поле уникальное
     * @param array<string[] поле => mixed значение> $expected Массив для проверки
     * @dataProvider applyNativeFieldDataProvider
     */
    public function testApplyNativeField(PriceLoader_Column $col, Material $material, $valueToSet, $isUnique, array $expected)
    {
        $interface = $this->getInterface();

        $interface->applyNativeField($col, $material, $valueToSet, $isUnique);

        foreach ($expected as $key => $val) {
            $this->assertEquals($val, $material->$key);
        }
    }


    /**
     * Тест применения нативного поля - случай с колонкой vis или priority
     */
    public function testApplyNativeFieldWithVisOrPriorityField()
    {
        $interface = $this->getInterface();
        $col = new PriceLoader_Column(['pid' => 1, 'fid' => 'priority']);
        $material = new Material(10);

        $interface->applyNativeField($col, $material, '999sdlkfjs', false);

        $this->assertEquals(999, $material->priority);
    }


    /**
     * Провайдер данных для метода testCheckAssoc
     * @return array<[
     *             Material Материал для обработки
     *             Page Страница, к которой привязываем (ранее ее не было)
     *             bool Материал считается новым
     *             bool Ожидается ли установка новой страницы
     *         ]>
     */
    public function checkAssocDataProvider()
    {
        return [
            [new Material(10), new Page(15), new Page(16), false, true], // Старый в новую страницу
            [new Material(11), new Page(15), new Page(15), false, false], // Старый в корень каталога
            [new Material(11), new Page(15), new Page(15), true, true], // Новый в корень каталога
            [new Material(7), new Page(15), new Page(15), true, false], // Глобальный материал
        ];
    }


    /**
     * Тест проверки и, при необходимости, размещения материала на странице
     * @param Material $material Материал для обработки
     * @param Page $root Корень каталога, куда загружаем
     * @param Page $context Страница, к которой привязываем (ранее ее не было)
     * @param bool $isNew Материал считается новым
     * @param bool $expectedContains Ожидается ли установка новой страницы
     * @dataProvider checkAssocDataProvider
     */
    public function testCheckAssoc(Material $material, Page $root, Page $context, $isNew, $expectedContains)
    {
        $interface = $this->getInterface();

        $this->assertNotContains($context->id, $material->pages_ids);

        $interface->checkAssoc($material, $root, $context, $isNew);
        $material->rollback();

        if ($expectedContains) {
            $this->assertContains($context->id, $material->pages_ids);
            $sqlQuery = "DELETE FROM cms_materials_pages_assoc WHERE pid = ? AND id = ?";
            Material::_SQL()->query([$sqlQuery, [$context->id, $material->id]]);
        } else {
            $this->assertNotContains($context->id, $material->pages_ids);
        }
    }


    /**
     * Тест применения произвольного поля (без учета callback-преобразований)
     */
    public function testApplyCustomField()
    {
        $interface = $this->getInterface();
        $material = new Material(10);
        $col = new PriceLoader_Column(7);

        $result = $interface->applyCustomField($col, $material, '40000', false, false);

        $this->assertEquals('price', $result);
        $this->assertEquals(40000, $material->price);

        $material->fields['price']->deleteValues();
        $material->fields['price']->addValue(83620);
    }


    /**
     * Тест применения произвольного поля - случай с нативным полем - этот метод не изменяет нативных полей
     */
    public function testApplyCustomFieldWithNativeField()
    {
        $interface = $this->getInterface();
        $material = new Material(10);
        $col = new PriceLoader_Column(2);

        $result = $interface->applyCustomField($col, $material, 'aaa', false, true);

        $this->assertNull($result);
        $this->assertEquals('Товар 1', $material->name);
    }


    /**
     * Тест применения произвольного поля - случай с уникальным полем - артикулом
     */
    public function testApplyCustomFieldWithUniqueField()
    {
        $interface = $this->getInterface();
        $material = new Material(10);
        $col = new PriceLoader_Column(1);

        $result = $interface->applyCustomField($col, $material, 'aaa', false, true);

        $this->assertNull($result);
        $this->assertEquals('f4dbdf21', $material->article);
    }


    /**
     * Тест применения произвольного поля - случай с новым материалом
     */
    public function testApplyCustomFieldWithNewMaterial()
    {
        $interface = $this->getInterface();
        $material = new Material(['pid' => 4, 'vis' => 1]);
        $col = new PriceLoader_Column(1);

        $material->commit();
        $result = $interface->applyCustomField($col, $material, 'aaa', true, true);

        $this->assertEquals('article', $result);
        $this->assertEquals('aaa', $material->article);

        Material::delete($material);
    }


    /**
     * Тест применения произвольного поля (без учета callback-преобразований) - случай с пустым файловым полем
     */
    public function testApplyCustomFieldWithEmptyFileField()
    {
        $interface = $this->getInterface();
        $material = new Material(10);
        $col = new PriceLoader_Column(['pid' => 1, 'fid' => 29]);

        $result = $interface->applyCustomField($col, $material, null, false, false);

        $this->assertNull($result);
    }


    /**
     * Тест применения произвольного поля (без учета callback-преобразований) - случай с непустым файловым полем и удалением старых файлов
     */
    public function testApplyCustomFieldWithFileFieldAndReplaceMode()
    {
        $interface = $this->getInterface();
        $material = new Material(16);
        $col = new PriceLoader_Column(['pid' => 1, 'fid' => 29]);
        $col->Parent->media_action = PriceLoader::MEDIA_FIELDS_REPLACE;
        $col->Parent->commit(); // Сохраним значение замены, иначе старые файлы не удалятся, а новые не сохранятся
        $att1 = new Attachment(49);
        $att2 = new Attachment(50);

        $this->assertEquals(49, $att1->id);
        $this->assertEquals(50, $att2->id);

        $result = $interface->applyCustomField($col, $material, '{"vis":1,"name":"","description":"","attachment":999}', false, false);
        $att1 = new Attachment(49);
        $att2 = new Attachment(50);
        $sqlQuery = "SELECT value FROM cms_data WHERE pid = ? AND fid = ?";
        $sqlResult = Material::_SQL()->getcol([$sqlQuery, [16, 29]]);

        $this->assertEquals('files', $result);
        $this->assertEmpty($att1->id);
        $this->assertEmpty($att2->id);
        $this->assertIsArray($sqlResult);
        $this->assertCount(1, $sqlResult);
        $this->assertEquals('{"vis":1,"name":"","description":"","attachment":999}', $sqlResult[0]);

        $col->Parent->media_action = PriceLoader::MEDIA_FIELDS_APPEND_IF_EMPTY;
        $col->Parent->commit();
        PriceLoader_Column::delete($col);
    }


    /**
     * Тест применения произвольного поля (без учета callback-преобразований) - случай с непустым файловым полем
     * и применением только к новым
     */
    public function testApplyCustomFieldWithFileFieldAndAppendToNewMode()
    {
        $interface = $this->getInterface();
        $material = new Material(17);
        $col = new PriceLoader_Column(['pid' => 1, 'fid' => 29]);
        $col->Parent->media_action = PriceLoader::MEDIA_FIELDS_APPEND_TO_NEW_ONLY;
        $col->Parent->commit(); // Сохраним значение замены, иначе старые файлы не удалятся, а новые не сохранятся
        $att = new Attachment(52);

        $this->assertEquals(52, $att->id);

        $result = $interface->applyCustomField($col, $material, '{"vis":1,"name":"","description":"","attachment":999}', false, false);
        $att = new Attachment(52);
        $sqlQuery = "SELECT value FROM cms_data WHERE pid = ? AND fid = ?";
        $sqlResult = Material::_SQL()->getcol([$sqlQuery, [17, 29]]);

        $this->assertNull($result);
        $this->assertEquals(52, $att->id);
        $this->assertIsArray($sqlResult);
        $this->assertCount(2, $sqlResult);
        $this->assertEquals('{"vis":1,"name":"","description":"","attachment":52}', $sqlResult[0]);

        $col->Parent->media_action = PriceLoader::MEDIA_FIELDS_APPEND_IF_EMPTY;
        $col->Parent->commit();
        PriceLoader_Column::delete($col);
    }


    /**
     * Тест метода convertMediaData
     */
    public function testConvertMediaData()
    {
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
        $preprocessor = new Snippet(['urn' => 'testpreprocessor', 'description' => '<' . '?php' . ' $GLOBALS["preprocessorData"][] = $files; ']);
        $preprocessor->commit();
        $postprocessor = new Snippet(['urn' => 'testpostprocessor', 'description' => '<' . '?php' . ' $GLOBALS["postprocessorData"][] = $files; ']);
        $postprocessor->commit();
        $field = new Material_Field(29); // Файлы

        $interface = $this->getInterface();
        $data = [
            '',
            'http://test/files/cms/common/image/nophoto.jpg',
            'http://test123/notexisting.jpg',
            '/vendor/volumnet/raas.cms.shop/tests/resources/test.xls',
        ];
        $addedAttachments = [];

        $result = $interface->convertMediaData($data, $field, $addedAttachments, $preprocessor, $postprocessor);

        $this->assertCount(2, $addedAttachments);
        $this->assertInstanceOf(Attachment::class, $addedAttachments[0]);
        $this->assertInstanceOf(Attachment::class, $addedAttachments[1]);
        $this->assertNotEmpty($addedAttachments[0]->id);
        $this->assertEquals($addedAttachments[0]->id + 1, $addedAttachments[1]->id);
        $this->assertEquals('nophoto.jpg', $addedAttachments[0]->filename);
        $this->assertEquals('test.xls', $addedAttachments[1]->filename);
        $this->assertCount(2, $result);
        $this->assertIsString($result[0]);
        $this->assertIsString($result[1]);
        $json1 = json_decode($result[0], true);
        $json2 = json_decode($result[1], true);
        $this->assertEquals($addedAttachments[0]->id, $json1['attachment']);
        $this->assertEquals(1, $json1['vis']);
        $this->assertEquals($addedAttachments[1]->id, $json2['attachment']);
        $this->assertEquals(1, $json2['vis']);
        $this->assertEquals([
            [sys_get_temp_dir() . '/nophoto.jpg'],
            [sys_get_temp_dir() . '/test.xls'],
        ], $GLOBALS['preprocessorData']);
        $this->assertEquals([
            [$addedAttachments[0]->file],
            [$addedAttachments[1]->file],
        ], $GLOBALS['postprocessorData']);

        Snippet::delete($preprocessor);
        Snippet::delete($postprocessor);
        foreach ($addedAttachments as $att) {
            Attachment::delete($att);
        }
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
    }


    /**
     * Тест метода convertMediaData - случай с указанием классов файловых процессоров
     */
    public function testConvertMediaDataWithProcessorsClassnames()
    {
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
        $preprocessor = PreprocessorMock::class;
        $postprocessor = PostprocessorMock::class;
        $field = new Material_Field(29); // Файлы

        $interface = $this->getInterface();
        $data = [
            '',
            'http://test/files/cms/common/image/nophoto.jpg',
            'http://test123/notexisting.jpg',
            '/vendor/volumnet/raas.cms.shop/tests/resources/test.xls',
        ];
        $addedAttachments = [];

        $result = $interface->convertMediaData($data, $field, $addedAttachments, $preprocessor, $postprocessor);

        $this->assertEquals([
            [sys_get_temp_dir() . '/nophoto.jpg'],
            [sys_get_temp_dir() . '/test.xls'],
        ], $GLOBALS['preprocessorData']);
        $this->assertEquals([
            [$addedAttachments[0]->file],
            [$addedAttachments[1]->file],
        ], $GLOBALS['postprocessorData']);

        foreach ($addedAttachments as $att) {
            Attachment::delete($att);
        }
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
    }


    /**
     * Тест проверки, относится ли строка к странице
     */
    public function testIsPageDataRow()
    {
        $interface = $this->getInterface();

        $result = $interface->isPageDataRow(['', '', '', 'bbb', '']);

        $this->assertTrue($result);
    }


    /**
     * Тест проверки, относится ли строка к странице (случай строки со множеством значений)
     */
    public function testIsPageDataRowWithMultipleValuesRow()
    {
        $interface = $this->getInterface();

        $result = $interface->isPageDataRow(['', 'aaa', '', 'bbb', 'ccc']);

        $this->assertFalse($result);
    }


    /**
     * Тест проверки, относится ли строка к странице (случай без использования категорий)
     */
    public function testIsPageDataRowWithNoCatsUsage()
    {
        $loader = new PriceLoader(1);
        $loader->cats_usage = PriceLoader::CATS_USAGE_DONT_USE;
        $interface = new PriceloaderInterface($loader);

        $result = $interface->isPageDataRow(['', '', '', 'bbb', '']);

        $this->assertFalse($result);
    }


    /**
     * Тест разбора строки категории (случай с отступом ячейками)
     */
    public function testParseCategoryRowWithCellPadding()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $loader->catalog_offset = 0;
        $result = $interface->parseCategoryRow($loader, array('', '', ' Категория 1 '));

        $this->assertEquals([2, 'Категория 1'], $result);
    }


    /**
     * Тест разбора строки категории (случай с отступом пробелами)
     */
    public function testParseCategoryRowWithSpacePadding()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $loader->catalog_offset = 1;
        $result = $interface->parseCategoryRow($loader, array('   Категория 1 '));

        $this->assertEquals([3, 'Категория 1'], $result);
    }


    /**
     * Тест усечения backtrace - случай с отступом, попадающим в backtrace
     */
    public function testCropBacktraceWithOffsetExactlyInBacktrace()
    {
        $interface = $this->getInterface();
        $backtrace = [1 => new Page(16), 4 => new Page(17), 7 => new Page(18)];

        $result = $interface->cropBackTrace($backtrace, 4);

        $this->assertEquals([1 => 16], array_map(function ($x) {
            return $x->id;
        }, $result));
    }


    /**
     * Тест усечения backtrace - случай с отступом между значениями backtrace
     */
    public function testCropBacktraceWithOffsetBetweenBacktrace()
    {
        $interface = $this->getInterface();
        $backtrace = [1 => new Page(16), 4 => new Page(17), 7 => new Page(18)];

        $result = $interface->cropBackTrace($backtrace, 5);

        $this->assertEquals([1 => 16, 4 => 17], array_map(function ($x) {
            return $x->id;
        }, $result));
    }


    /**
     * Тест возврата последней категории из backtrace
     */
    public function testLastCat()
    {
        $interface = $this->getInterface();
        $backtrace = [1 => new Page(16), 4 => new Page(17), 7 => new Page(18)];

        $result = $interface->lastCat(new Page(15), $backtrace);

        $this->assertEquals(18, $result->id);
    }


    /**
     * Тест возврата последней категории из backtrace - случай с пустым backtrace
     */
    public function testLastCatWithEmptyBacktrace()
    {
        $interface = $this->getInterface();

        $result = $interface->lastCat(new Page(15), []);

        $this->assertEquals(15, $result->id);
    }


    /**
     * Тест поиска страницы с заданным именем в заданном контексте
     */
    public function testGetPage()
    {
        $interface = $this->getInterface();

        $result = $interface->getPage(new Page(15), 'Категория 1');

        $this->assertEquals(16, $result->id);
    }


    /**
     * Тест поиска страницы с заданным именем в заданном контексте - случай с несуществующей страницей
     */
    public function testGetPageWithNotExistingName()
    {
        $interface = $this->getInterface();

        $result = $interface->getPage(new Page(15), 'Несуществующая категория');

        $this->assertNull($result);
    }


    /**
     * Тест создания страницы
     */
    public function testCreatePage()
    {
        $interface = $this->getInterface();

        $result = $interface->createPage(new Page(15), 'Новая категория', false);

        $this->assertEquals('Новая категория', $result->name);
        $this->assertEquals(1, $result->vis);
        $this->assertEquals(1, $result->pvis);
        $this->assertEquals(15, $result->pid);
        $this->assertEquals(1, $result->template);
        $this->assertEquals(1, $result->cache);
        $this->assertEquals(1, $result->inherit_lang);
        $this->assertNotNull($result->id);

        Page::delete($result);
    }


    /**
     * Тест создания страницы - тестовый режим
     */
    public function testCreatePageWithTestMode()
    {
        $interface = $this->getInterface();

        $result = $interface->createPage(new Page(15), 'Новая категория', true);

        $this->assertEquals('Новая категория', $result->name);
        $this->assertEquals(1, $result->vis);
        $this->assertEquals(1, $result->pvis);
        $this->assertEquals(15, $result->pid);
        $this->assertNull($result->id);
    }


    /**
     * Тест возврата последнего уровня смещения из backtrace
     */
    public function testLastLevel()
    {
        $interface = $this->getInterface();
        $backtrace = [1 => new Page(16), 4 => new Page(17), 7 => new Page(18)];

        $result = $interface->lastLevel($backtrace);

        $this->assertEquals(7, $result);
    }


    /**
     * Тест возврата последнего уровня смещения из backtrace - случай с пустым backtrace
     */
    public function testLastLevelWithEmptyBacktrace()
    {
        $interface = $this->getInterface();

        $result = $interface->lastLevel([]);

        $this->assertNull($result);
    }


    /**
     * Тест записи в лог (в тестовом режиме) данныъ об удалении полей и вложений
     */
    public function testLogDeleteFieldsAndAttachments()
    {
        $interface = $this->getInterface();
        $log = array();

        $interface->logDeleteFieldsAndAttachments($log, [27, 29], [30, 31]);

        $this->assertCount(4, $log);
        $this->assertStringContainsString('Изображение', $log[0]['text'] ?? '');
        $this->assertIsFloat($log[0]['time']);
        $this->assertStringContainsString('Файлы', $log[1]['text']);
        $this->assertIsFloat($log[1]['time']);
        $this->assertStringContainsString('0', $log[2]['text']);
        $this->assertStringContainsString('0', $log[3]['text']);
    }


    /**
     * Тест разбора данных из файла
     */
    public function testParse()
    {
        $interface = $this->getInterface();

        $result = $interface->parse($this->getResourcesDir() . '/test.xls', 'xls');

        $this->assertEquals('Категория 1', $result[0][0]);
        $this->assertEquals('Категория 11', $result[1][1]);
    }


    /**
     * Тест применения к строке данных callback-преобразования
     */
    public function testConvertRow()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $row = ['f4dbdf21', 'Товар 1', '', '', 'под заказ', 0, '83 620, 00'];

        $result = $interface->convertRow($loader, $row);

        $this->assertEquals(['f4dbdf21', 'Товар 1', '', [], 0, 0, 83620], $result);
    }


    /**
     * Тест получения массива совпадающих материалов либо по уникальному полю,
     * либо по всей строке данных (без учета callback-преобразований)
     */
    public function testGetItems()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $row = ['f4dbdf21', 'Товар 1', '', [], 0, 0, 83620];

        $result = $interface->getItems($loader, $row, 0);

        $this->assertCount(1, $result);
        $this->assertEquals(10, $result[0]->id);
    }


    /**
     * Тест получения массива совпадающих материалов либо по уникальному полю,
     * либо по всей строке данных (без учета callback-преобразований) - случай без уникального поля
     */
    public function testGetItemsWithNoUniqueField()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $row = ['f4dbdf21', 'Товар 1', '', [], 0, 0, 83620];

        $result = $interface->getItems($loader, $row, null);

        $this->assertCount(1, $result);
        $this->assertEquals(10, $result[0]->id);
    }


    /**
     * Тест применения нативных полей (без сохранения)
     */
    public function testApplyNativeFields()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $item = new Material(10);
        $row = ['f4dbdf21', 'Товар 111', 'Описание товара 111', '', 'под заказ', 0, '83 620, 00'];

        $interface->applyNativeFields($loader, $item, $row, 0);

        $this->assertEquals('Товар 111', $item->name);
        $this->assertEquals('Описание товара 111', $item->description);
    }


    /**
     * Тест применения дополнительных полей (с сохранением)
     */
    public function testApplyCustomFields()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $item = new Material(10);
        $row = ['f4dbdf21aaa', 'Товар 111', 'Описание товара 111', '', 1, 0, '11111'];

        $interface->applyCustomFields($loader, $item, $row, false, 0);

        $this->assertEquals('Товар 1', $item->name);
        $this->assertEquals('f4dbdf21', $item->article);
        $this->assertEquals('', $item->description);
        $this->assertEquals(1, $item->available);
        $this->assertEquals(11111, $item->price);
        $this->assertEquals(2, $item->step);
        $item->fields['available']->deleteValues();
        $item->fields['available']->addValue(0);
        $item->fields['price']->deleteValues();
        $item->fields['price']->addValue(83620);
    }


    /**
     * Тест применения дополнительных полей (с сохранением) - случай с новым материалом
     */
    public function testApplyCustomFieldsWithNewMaterial()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $item = new Material(['pid' => 4, 'vis' => 1]);
        $row = ['f4dbdf21aaa', 'Товар 111', 'Описание товара 111', '', 1, 0, '11111'];

        $item->commit();
        $interface->applyCustomFields($loader, $item, $row, true, 0);

        $this->assertEquals('f4dbdf21aaa', $item->article);
        $this->assertEquals(1, $item->available);
        $this->assertEquals(11111, $item->price);
        $this->assertEquals(1, $item->step);
        Material::delete($item);
    }


    /**
     * Тест применения строки данных к материалу (без учета callback-преобразований)
     */
    public function testProcessItem()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $root = new Page(15);
        $context = new Page(16);
        $row = ['f4dbdf21aaa', 'Товар 111', 'Описание товара 111', [], 1, 0, '11111'];
        $item = new Material(10);

        $interface->processItem($loader, $item, $root, $context, $row, 0, false);

        $item->rollback();
        $this->assertNotNull($item->id);
        $this->assertEquals('Товар 111', $item->name);
        $this->assertEquals('f4dbdf21', $item->article);
        $this->assertEquals('Описание товара 111', $item->description);
        $this->assertEquals(1, $item->available);
        $this->assertEquals(11111, $item->price);
        $this->assertEquals(2, $item->step);
        $this->assertEquals([1, 16, 18, 19, 20, 21, 22, 23, 24], $item->pages_ids);

        $item->name = 'Товар 1';
        $item->description = '';
        $item->commit();
        $item->fields['available']->deleteValues();
        $item->fields['available']->addValue(0);
        $item->fields['price']->deleteValues();
        $item->fields['price']->addValue(83620);
    }


    /**
     * Тест применения строки данных к материалу (без учета callback-преобразований) - случай с новым материалом
     */
    public function testProcessItemWithNewMaterial()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $root = new Page(15);
        $context = new Page(16);
        $row = ['f4dbdf21aaa', 'Товар 111', 'Описание товара 111', [], 1, 0, '11111'];
        $item = new Material(['pid' => 4, 'vis' => 1]);

        $interface->processItem($loader, $item, $root, $context, $row, 0, false);

        $item->rollback();
        $this->assertNotNull($item->id);
        $this->assertEquals('Товар 111', $item->name);
        $this->assertEquals('f4dbdf21aaa', $item->article);
        $this->assertEquals('Описание товара 111', $item->description);
        $this->assertEquals(1, $item->available);
        $this->assertEquals(11111, $item->price);
        $this->assertEquals(1, $item->step);
        $this->assertEquals([16], $item->pages_ids);

        Material::delete($item);
    }


    /**
     * Тест применения строки данных к материалу (без учета callback-преобразований) - тестовый режим
     */
    public function testProcessItemWithTestMode()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $root = new Page(15);
        $context = new Page(16);
        $row = ['f4dbdf21aaa', 'Товар 111', 'Описание товара 111', [], 1, 0, '11111'];
        $item = new Material();

        $interface->processItem($loader, $item, $root, $context, $row, 0, true);

        $this->assertNull($item->id);
        $this->assertEquals('Товар 111', $item->name);
        $this->assertEquals('Описание товара 111', $item->description);
        $this->assertEmpty($item->available);
        $this->assertEmpty($item->price);
        $this->assertEmpty($item->step);
        $this->assertEmpty($item->pages_ids);
    }


    /**
     * Тест обработки строки данных товара (без callback-преобразований)
     */
    public function testProcessItemRow()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $root = new Page(15);
        $context = new Page(16);
        $affectedMaterialsIds = [];
        $log = [];
        $row = ['f4dbdf21', 'Товар 111', 'Описание товара 111', [], 1, 0, '11111'];

        $interface->processItemRow($loader, $row, $root, $context, $affectedMaterialsIds, $log, 0, false, 1, 0, 2);

        $item = new Material(10);

        $this->assertEquals('Товар 111', $item->name);
        $this->assertEquals('f4dbdf21', $item->article);
        $this->assertEquals('Описание товара 111', $item->description);
        $this->assertEquals(1, $item->available);
        $this->assertEquals(11111, $item->price);
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Товар 1', $log[0]['text'] ?? '');
        $this->assertEquals(2, $log[0]['row']);
        $this->assertEquals(3, $log[0]['realrow']);
        $this->assertCount(1, $affectedMaterialsIds);
        $this->assertContains(10, $affectedMaterialsIds);

        $item->name = 'Товар 1';
        $item->description = '';
        $item->commit();
        $item->fields['available']->deleteValues();
        $item->fields['available']->addValue(0);
        $item->fields['price']->deleteValues();
        $item->fields['price']->addValue(83620);
    }


    /**
     * Тест обработки строки данных товара (без обновления товаров)
     */
    public function testProcessItemRowWithoutUpdateMaterials()
    {
        $loader = new PriceLoader(1);
        $loader->update_materials = false;
        $interface = new PriceloaderInterface($loader);
        $root = new Page(15);
        $context = new Page(16);
        $affectedMaterialsIds = [];
        $log = [];
        $row = ['f4dbdf21', 'Товар 111', 'Описание товара 111', [], 1, 0, '11111'];

        $interface->processItemRow($loader, $row, $root, $context, $affectedMaterialsIds, $log, 0, false, 1, 0, 2);

        $item = new Material(10);

        $this->assertEquals('Товар 1', $item->name);
        $this->assertEquals('f4dbdf21', $item->article);
        $this->assertEquals('', $item->description);
        $this->assertEquals(0, $item->available);
        $this->assertEquals(83620, $item->price);
        $this->assertCount(0, $log);
        $this->assertCount(0, $affectedMaterialsIds);
    }


    /**
     * Тест обработки строки данных категории
     */
    public function testProcessPageRow()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $context = new Page(16);
        $virtualLevel = 0;
        $row = array('', 'Категория 11');
        $backtrace = array(0 => new Page(16));
        $affectedPagesIds = [];
        $log = [];

        $interface->processPageRow(
            $loader,
            $row,
            new Page(15),
            $context,
            $virtualLevel,
            $backtrace,
            $affectedPagesIds,
            $log,
            true
        );

        $this->assertContains(17, $affectedPagesIds);
        $this->assertEquals([0 => 16, 1 => 17], array_map(function ($x) {
            return $x->id;
        }, $backtrace));
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Категория 11', $log[0]['text'] ?? '');
        $this->assertEquals(0, $virtualLevel);
        $this->assertEquals(17, $context->id);
    }


    /**
     * Тест обработки строки данных категории
     */
    public function testProcessPageRowWith0LevelPage()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $context = new Page(16);
        $virtualLevel = 0;
        $row = array('Категория 1');
        $backtrace = array(0 => new Page(16));
        $affectedPagesIds = [];
        $log = [];

        $interface->processPageRow(
            $loader,
            $row,
            new Page(15),
            $context,
            $virtualLevel,
            $backtrace,
            $affectedPagesIds,
            $log,
            true
        );

        $this->assertContains(16, $affectedPagesIds);
        $this->assertEquals([0 => 16], array_map(function ($x) {
            return $x->id;
        }, $backtrace));
        $this->assertCount(1, $log);
        $this->assertStringContainsString('Категория 1', $log[0]['text'] ?? '');
        $this->assertEquals(0, $virtualLevel);
        $this->assertEquals(16, $context->id);
    }


    /**
     * Тест обработки строки данных категории - случай с новой категорией, тестовый режим, отключено создание страниц
     */
    public function testProcessPageRowWithTestModeNewPage()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $context = new Page(16);
        $virtualLevel = 0;
        $row = array('', 'Новая категория');
        $backtrace = array(0 => new Page(16));
        $affectedPagesIds = [];
        $log = [];

        $interface->processPageRow(
            $loader,
            $row,
            new Page(15),
            $context,
            $virtualLevel,
            $backtrace,
            $affectedPagesIds,
            $log,
            true
        );

        $this->assertEmpty($affectedPagesIds);
        $this->assertEquals([0 => 16], array_map(function ($x) {
            return $x->id;
        }, $backtrace));
        $this->assertCount(1, $log);
        $this->assertEquals(1, $virtualLevel);
        $this->assertEquals(16, $context->id);
    }


    /**
     * Тест обработки строки данных категории - случай новой категории, боевой режим, включено создание страниц
     */
    public function testProcessPageRowWithWorkingModeNewPageAllowPageCreation()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->create_pages = 1;
        $context = new Page(16);
        $virtualLevel = 0;
        $row = array('', 'Новая категория');
        $backtrace = array(0 => new Page(16));
        $affectedPagesIds = [];
        $log = [];

        $interface->processPageRow(
            $loader,
            $row,
            new Page(15),
            $context,
            $virtualLevel,
            $backtrace,
            $affectedPagesIds,
            $log,
            false
        );

        $this->assertNotEquals(16, $context->id);
        $this->assertContains((int)$context->id, $affectedPagesIds);
        $this->assertEquals([0 => 16, 1 => $context->id], array_map(function ($x) {
            return $x->id;
        }, $backtrace));
        $this->assertCount(1, $log);
        $this->assertEquals(0, $virtualLevel);
        Page::delete($context);
    }


    /**
     * Тест обработки файла
     */
    public function testProcessData()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $affectedMaterialsIds = [];
        $affectedPagesIds = [];
        $log = [];
        $rawData = [];
        $converter = PriceloaderDataConverter::spawn('xls');
        $data = $converter->load($this->getResourcesDir() . '/test.xls');

        Material::delete(new Material(18));
        Material::delete(new Material(19));

        $item = Material::importBy('name', 'Товар 9');
        $this->assertNull($item->id);
        $item = Material::importBy('name', 'Товар 10');
        $this->assertNull($item->id);

        $interface->processData(
            $loader,
            $data,
            new Page(15),
            $affectedMaterialsIds,
            $affectedPagesIds,
            $log,
            $rawData,
            false,
            0,
            0,
            0
        );

        $item = Material::importBy('name', 'Товар 9');

        $this->assertNotNull($item->id);

        $item = Material::importBy('name', 'Товар 10');

        $this->assertNotNull($item->id);

        $this->assertContains(10, $affectedMaterialsIds);
        $this->assertContains(11, $affectedMaterialsIds);
        $this->assertContains(12, $affectedMaterialsIds);
        $this->assertNotContains(18, $affectedMaterialsIds);
        $this->assertNotContains(19, $affectedMaterialsIds);
        $this->assertContains(16, $affectedPagesIds);
        $this->assertContains(17, $affectedPagesIds);
        $this->assertContains(18, $affectedPagesIds);
        $this->assertNotContains(15, $affectedPagesIds);
        $this->assertNotContains(1, $affectedPagesIds);
        $this->assertEquals('Категория 1', $rawData[0][0]);
        $this->assertEquals(0, $rawData[3][4]);
        $this->assertEquals(1, $rawData[4][4]);
        $this->assertStringContainsString('Категория 1', $log[0]['text'] ?? '');
        $this->assertEquals(0, $log[0]['row']);
        $this->assertStringContainsString('Товар 2', $log[4]['text']);
        $this->assertEquals(4, $log[4]['row']);
    }


    /**
     * Тест очистки материалов - тестовый режим
     */
    public function testClearMaterialsWithTestMode()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 16, 17, 18];

        $interface->clearMaterials($loader->Material_Type, new Page(15), $log, $affectedMaterialsIds, true);

        $item = new Material(15);
        $attachment = new Attachment(46);

        $this->assertStringContainsString('Товар 6', $log[0]['text'] ?? '');
        $this->assertEquals(15, $item->id);
        $this->assertEquals(46, $attachment->id);
    }


    /**
     * Тест очистки материалов
     */
    public function testClearMaterials()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 16, 17, 18];

        $interface->clearMaterials($loader->Material_Type, new Page(15), $log, $affectedMaterialsIds, false);

        $item = new Material(15);
        $attachment = new Attachment(46);

        $this->assertEmpty($item->id);
        $this->assertEmpty($attachment->id);
    }


    /**
     * Тест очистки страниц - тестовый режим
     */
    public function testClearPagesWithTestMode()
    {
        $interface = $this->getInterface();
        $log = [];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 22, 23];

        $interface->clearPages(new Page(15), $log, $affectedPagesIds, true);

        $page = new Page(24);

        $this->assertEquals(24, $page->id);
        $this->assertStringContainsString('Категория 3', $log[0]['text'] ?? '');
    }


    /**
     * Тест очистки страниц
     */
    public function testClearPages()
    {
        $interface = $this->getInterface();
        $log = [];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 22, 23];

        $interface->clearPages(new Page(15), $log, $affectedPagesIds, false);

        $page = new Page(24);

        $this->assertEmpty($page->id);
    }


    /**
     * Тест очистки незатронутых материалов и категорий - случай, когда ничего не очищается
     */
    public function testClearWithClearNone()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 15, 16, 19];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 23, 24];

        $interface->clear($loader, new Page(15), $log, $affectedMaterialsIds, $affectedPagesIds, 0, true);

        $page = new Page(22);
        $item = new Material(17);

        $this->assertEquals(22, $page->id);
        $this->assertEquals(17, $item->id);
        $this->assertCount(1, $log);
        $this->assertStringContainsString('удалены', $log[0]['text'] ?? '');
    }


    /**
     * Тест очистки незатронутых материалов и категорий - очищаем только материалы в тестовом режиме
     */
    public function testClearWithTestModeMaterialsOnly()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 15, 16, 19];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 23, 24];

        $interface->clear(
            $loader,
            new Page(15),
            $log,
            $affectedMaterialsIds,
            $affectedPagesIds,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY,
            true
        );

        $page = new Page(22);
        $item = new Material(17);

        $this->assertEquals(22, $page->id);
        $this->assertEquals(17, $item->id);
        $this->assertStringContainsString('Товар 8', $log[0]['text'] ?? '');
    }


    /**
     * Тест очистки незатронутых материалов и категорий - материалы и страницы в тестовом режиме
     */
    public function testClearWithTestModeMaterialsAndPages()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 15, 16, 19];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 23, 24];

        $interface->clear(
            $loader,
            new Page(15),
            $log,
            $affectedMaterialsIds,
            $affectedPagesIds,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES,
            true
        );

        $page = new Page(22);
        $item = new Material(17);

        $this->assertEquals(22, $page->id);
        $this->assertEquals(17, $item->id);
        $this->assertStringContainsString('Товар 8', $log[0]['text'] ?? '');
        $this->assertStringContainsString('Поля для удаления: Изображение', $log[1]['text']);
        $this->assertStringContainsString('Поля для удаления: Файлы', $log[2]['text']);
        $this->assertStringContainsString('test_14.doc', $log[3]['text']);
        $this->assertStringContainsString('test_15.pdf', $log[4]['text']);
        $this->assertStringContainsString('/common/0', $log[5]['text']);
        $this->assertStringContainsString('Категория 13', $log[6]['text']);
    }


    /**
     * Тест очистки незатронутых материалов и категорий - только материалы
     */
    public function testClearWithMaterialsOnly()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 15, 16, 19];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 23, 24];

        $interface->clear(
            $loader,
            new Page(15),
            $log,
            $affectedMaterialsIds,
            $affectedPagesIds,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY,
            false
        );

        $page = new Page(22);
        $item = new Material(17);

        $this->assertEquals(22, $page->id);
        $this->assertEmpty($item->id);
    }


    /**
     * Тест очистки незатронутых материалов и категорий - с несовпадающими страницами
     */
    public function testClearWithNotAffectedPages()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 15, 16, 17, 19];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 23, 24];

        $interface->clear(
            $loader,
            new Page(17),
            $log,
            $affectedMaterialsIds,
            $affectedPagesIds,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES,
            false
        );

        $page = new Page(22);

        $this->assertEquals(22, $page->id);
    }


    /**
     * Тест очистки незатронутых материалов и категорий - материалы и страницы в боевом режиме
     */
    public function testClear()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $log = [];
        $affectedMaterialsIds = [10, 11, 12, 13, 14, 15, 16, 17, 19];
        $affectedPagesIds = [16, 17, 18, 19, 20, 21, 23, 24];

        $interface->clear(
            $loader,
            new Page(15),
            $log,
            $affectedMaterialsIds,
            $affectedPagesIds,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES,
            false
        );

        $page = new Page(22);

        $this->assertEmpty($page->id);
    }


    /**
     * Тест загрузки прайса на сервер
     */
    public function testUpload()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->create_pages = 1;

        $result = $interface->upload(
            $this->getResourcesDir() . '/test.xls',
            'xls',
            new Page(15),
            false,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES
        );

        $page = Page::importBy('name', 'Категория 13');
        $item = Material::importBy('name', 'Товар 8');

        $this->assertNotEmpty($page->id);
        $this->assertNotEmpty($item->id);
        $this->assertNotEquals(22, $page->id);
        $this->assertNotEquals(17, $item->id);
        $this->assertStringContainsString('Категория 1', $result['log'][0]['text'] ?? '');
        $this->assertEquals(0, $result['log'][0]['row']);
        $this->assertEquals('Категория 1', $result['raw_data'][0][0]);
        $this->assertTrue($result['ok']);
    }


    /**
     * Тест загрузки прайса на сервер - случай несуществующего файла
     */
    public function testUploadWithNotExistingFile()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->create_pages = 1;

        $result = $interface->upload(
            $this->getResourcesDir() . '/aaa.xls',
            'xls',
            new Page(15),
            false,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES
        );

        $this->assertEquals('MISSING', $result['localError'][0]['name']);
        $this->assertEquals('file', $result['localError'][0]['value']);
    }


    /**
     * Тест загрузки прайса на сервер - случай некорректного файла
     */
    public function testUploadWithInvalidFile()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->create_pages = 1;

        $result = $interface->upload(
            $this->getResourcesDir() . '/test.xls',
            'csv',
            new Page(15),
            false,
            PriceLoader::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES
        );

        $this->assertEquals('INVALID', $result['localError'][0]['name']);
        $this->assertEquals('file', $result['localError'][0]['value']);
    }


    /**
     * Тест выгрузки заголовка
     */
    public function testExportHeader()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $column = new PriceLoader_Column(['pid' => 1, 'fid' => '', 'priority' => 8]);
        $column->commit();
        $column = new PriceLoader_Column(['pid' => 1, 'fid' => 'vis', 'priority' => 9]);
        $column->commit();
        $column = new PriceLoader_Column(['pid' => 1, 'fid' => 'urn', 'priority' => 10]);
        $column->commit();

        $result = $interface->exportHeader($loader);

        $this->assertEquals('Артикул', $result[0]);
        $this->assertEquals('Название', $result[1]);
        $this->assertEmpty($result[7]);
        $this->assertEquals('Видимость', $result[8]);
        $this->assertEquals('URN', $result[9]);
    }


    /**
     * Тест выгрузки строки категории
     */
    public function testExportPageRow()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $page = new Page(16);
        $level = 2;

        $result = $interface->exportPageRow($loader, $page, $level);

        $this->assertEquals(['', 'Категория 1'], $result);
    }


    /**
     * Тест выгрузки строки категории - случай с отступами пробелами
     */
    public function testExportPageRowWithSpaceOffset()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $loader->catalog_offset = 1;
        $page = new Page(16);
        $level = 3;

        $result = $interface->exportPageRow($loader, $page, $level);

        $this->assertEquals(['  Категория 1'], $result);
    }


    /**
     * Тест выгрузки ячейки материала с нативным полем
     */
    public function testExportMaterialColumnWithNativeField()
    {
        $column = new PriceLoader_Column(2);
        $item = new Material(10);
        $interface = $this->getInterface();

        $result = $interface->exportMaterialColumn($column, $item);

        $this->assertEquals('Товар 1', $result);
    }


    /**
     * Тест выгрузки ячейки материала с одиночным кастомным полем
     */
    public function testExportMaterialColumnWithSingleCustomField()
    {
        $column = new PriceLoader_Column(5);
        $item = new Material(10);
        $interface = $this->getInterface();

        $result = $interface->exportMaterialColumn($column, $item);

        $this->assertEquals('под заказ', $result);
    }


    /**
     * Тест выгрузки ячейки материала с множественным числовым кастомным полем
     */
    public function testExportMaterialColumnWithMultipleNumericField()
    {
        $item = new Material(10);
        $field = $item->fields['price'];
        $field->multiple = true;
        $field->commit();
        $field->deleteValues();
        $field->addValue('123aaa');
        $field->addValue('456bbb');
        $column = new PriceLoader_Column(7);
        $interface = $this->getInterface();

        $result = $interface->exportMaterialColumn($column, $item);

        $this->assertEquals('123, 456', $result);

        $field->multiple = false;
        $field->commit();
        $field->deleteValues();
        $field->addValue('83620');
    }


    /**
     * Тест выгрузки ячейки материала с множественным кастомным полем
     */
    public function testExportMaterialColumnWithMultipleCustomField()
    {
        $column = new PriceLoader_Column(4);
        $column->callback_download = ' $temp = array();
                                    foreach ((array)$x as $y) {
                                        if ($y && $y->fields["article"]) {
                                            $temp[] = $y->article;
                                        }
                                    }
                                    return $temp;';
        $column->commit();
        $item = new Material(10);
        $item->fields['related']->deleteValues();
        $item->fields['related']->addValue(10);
        $item->fields['related']->addValue(11);
        $item->fields['related']->addValue(12);
        $interface = $this->getInterface();

        $result = $interface->exportMaterialColumn($column, $item);

        $this->assertEquals('f4dbdf21, 83dcefb7, 1ad5be0d', $result);
    }


    /**
     * Тест выгрузки строки материала
     */
    public function testExportMaterialRow()
    {
        $col = new PriceLoader_Column(4);
        $col->callback_download = ' $temp = array();
                                    foreach ((array)$x as $y) {
                                        if ($y && $y->fields["article"]) {
                                            $temp[] = $y->article;
                                        }
                                    }
                                    return $temp;';
        $col->commit();
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $item = new Material(10);
        $item->fields['related']->deleteValues();
        $item->fields['related']->addValue(10);
        $item->fields['related']->addValue(11);
        $item->fields['related']->addValue(12);

        $result = $interface->exportMaterialRow($loader, $item);

        $this->assertEquals([
            'f4dbdf21',
            'Товар 1',
            '',
            'f4dbdf21, 83dcefb7, 1ad5be0d',
            'под заказ',
            '0',
            '83620',
            '',
            '1',
            'tovar_1',
        ], $result);
    }


    /**
     * Тест выгрузки данных
     */
    public function testExportData()
    {
        $this->installTables(); // Для восстановления изначального состояния (чтобы легче было проверять)
        $col1 = new PriceLoader_Column(3); // Описание
        $col1->pid = 0;
        $col1->commit();
        $col2 = new PriceLoader_Column(4); // Связанные товары - убираем, т.к. некорректная функция выгрузки
        $col2->pid = 0;
        $col2->commit();
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $result = $interface->exportData($loader, null, 1);

        $csv = new CSV($result);
        $goodsBlock = 'f4dbdf21;"Товар 1";"под заказ";0;83620' . "\n"
                    . '83dcefb7;"Товар 2";"в наличии";75907;67175' . "\n"
                    . '1ad5be0d;"Товар 3";"в наличии";86635;71013' . "\n"
                    . '6dd28e9b;"Товар 4";"в наличии";0;30450' . "\n"
                    . 'f3b61b38;"Товар 5";"под заказ";0;25712' . "\n"
                    . '84b12bae;"Товар 6";"в наличии";0;54096' . "\n"
                    . '1db87a14;"Товар 7";"в наличии";58091;49651' . "\n"
                    . '6abf4a82;"Товар 8";"в наличии";73494;61245' . "\n"
                    . 'fa005713;"Товар 9";"под заказ";6506;5609' . "\n"
                    . '8d076785;"Товар 10";"в наличии";0;85812';
        $expected = 'Артикул;Название;"В наличии";"Старая цена";Стоимость' . "\n"
                  . '"Категория 1"' . "\n"
                  . ';"Категория 11"' . "\n"
                  . ';;"Категория 111"' . "\n"
                  . $goodsBlock . "\n"
                  . ';;"Категория 112"' . "\n"
                  . $goodsBlock . "\n"
                  . ';;"Категория 113"' . "\n"
                  . $goodsBlock . "\n"
                  . ';"Категория 12"' . "\n"
                  . $goodsBlock . "\n"
                  . ';"Категория 13"' . "\n"
                  . $goodsBlock . "\n"
                  . '"Категория 2"' . "\n"
                  . $goodsBlock . "\n"
                  . '"Категория 3"' . "\n"
                  . $goodsBlock;
        $this->assertEquals($expected, $csv->csv);

        $col1->pid = 1;
        $col1->commit();
        $col2->pid = 1;
        $col2->commit();
    }


    /**
     * Тест выгрузки данных - случай без повтора товаров
     */
    public function testExportDataWithNoRepeat()
    {
        $this->installTables(); // Для восстановления изначального состояния (чтобы легче было проверять)
        $col1 = new PriceLoader_Column(3); // Описание
        $col1->pid = 0;
        $col1->commit();
        $col2 = new PriceLoader_Column(4); // Связанные товары - убираем, т.к. некорректная функция выгрузки
        $col2->pid = 0;
        $col2->commit();
        $loader = new PriceLoader(1);
        $loader->cats_usage = PriceLoader::CATS_USAGE_DONT_REPEAT;
        $interface = $this->getInterface($loader);

        $result = $interface->exportData($loader, null, 1);

        $csv = new CSV($result);
        $expected = 'Артикул;Название;"В наличии";"Старая цена";Стоимость' . "\n"
                  . '"Категория 1"' . "\n"
                  . ';"Категория 11"' . "\n"
                  . ';;"Категория 111"' . "\n"
                  . 'f4dbdf21;"Товар 1";"под заказ";0;83620' . "\n"
                  . '83dcefb7;"Товар 2";"в наличии";75907;67175' . "\n"
                  . '1ad5be0d;"Товар 3";"в наличии";86635;71013' . "\n"
                  . '6dd28e9b;"Товар 4";"в наличии";0;30450' . "\n"
                  . 'f3b61b38;"Товар 5";"под заказ";0;25712' . "\n"
                  . '84b12bae;"Товар 6";"в наличии";0;54096' . "\n"
                  . '1db87a14;"Товар 7";"в наличии";58091;49651' . "\n"
                  . '6abf4a82;"Товар 8";"в наличии";73494;61245' . "\n"
                  . 'fa005713;"Товар 9";"под заказ";6506;5609' . "\n"
                  . '8d076785;"Товар 10";"в наличии";0;85812' . "\n"
                  . ';;"Категория 112"' . "\n"
                  . ';;"Категория 113"' . "\n"
                  . ';"Категория 12"' . "\n"
                  . ';"Категория 13"' . "\n"
                  . '"Категория 2"' . "\n"
                  . '"Категория 3"';
        $this->assertEquals($expected, $csv->csv);

        $col1->pid = 1;
        $col1->commit();
        $col2->pid = 1;
        $col2->commit();
    }


    /**
     * Тест выгрузки данных - случай без категорий
     */
    public function testExportDataWithNoCats()
    {
        $this->installTables(); // Для восстановления изначального состояния (чтобы легче было проверять)
        $col1 = new PriceLoader_Column(3); // Описание
        $col1->pid = 0;
        $col1->commit();
        $col2 = new PriceLoader_Column(4); // Связанные товары - убираем, т.к. некорректная функция выгрузки
        $col2->pid = 0;
        $col2->commit();
        $loader = new PriceLoader(1);
        $loader->cats_usage = PriceLoader::CATS_USAGE_DONT_USE;
        $interface = $this->getInterface($loader);

        $result = $interface->exportData($loader, null, 1);

        $csv = new CSV($result);
        $expected = 'Артикул;Название;"В наличии";"Старая цена";Стоимость' . "\n"
                  . 'f4dbdf21;"Товар 1";"под заказ";0;83620' . "\n"
                  . '83dcefb7;"Товар 2";"в наличии";75907;67175' . "\n"
                  . '1ad5be0d;"Товар 3";"в наличии";86635;71013' . "\n"
                  . '6dd28e9b;"Товар 4";"в наличии";0;30450' . "\n"
                  . 'f3b61b38;"Товар 5";"под заказ";0;25712' . "\n"
                  . '84b12bae;"Товар 6";"в наличии";0;54096' . "\n"
                  . '1db87a14;"Товар 7";"в наличии";58091;49651' . "\n"
                  . '6abf4a82;"Товар 8";"в наличии";73494;61245' . "\n"
                  . 'fa005713;"Товар 9";"под заказ";6506;5609' . "\n"
                  . '8d076785;"Товар 10";"в наличии";0;85812';
        $this->assertEquals($expected, $csv->csv);

        $col1->pid = 1;
        $col1->commit();
        $col2->pid = 1;
        $col2->commit();
    }


    /**
     * Тест выгрузки прайса с сервера
     */
    public function testDownload()
    {
        $this->installTables(); // Для восстановления изначального состояния (чтобы легче было проверять)
        $col1 = new PriceLoader_Column(3); // Описание
        $col1->pid = 0;
        $col1->commit();
        $col2 = new PriceLoader_Column(4); // Связанные товары - убираем, т.к. некорректная функция выгрузки
        $col2->pid = 0;
        $col2->commit();
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $result = $interface->download(null, 1, 1, 'csv', 'UTF-8', true);

        $goodsBlock = ';f4dbdf21;"Товар 1";"под заказ";0;83620' . "\n"
                    . ';83dcefb7;"Товар 2";"в наличии";75907;67175' . "\n"
                    . ';1ad5be0d;"Товар 3";"в наличии";86635;71013' . "\n"
                    . ';6dd28e9b;"Товар 4";"в наличии";0;30450' . "\n"
                    . ';f3b61b38;"Товар 5";"под заказ";0;25712' . "\n"
                    . ';84b12bae;"Товар 6";"в наличии";0;54096' . "\n"
                    . ';1db87a14;"Товар 7";"в наличии";58091;49651' . "\n"
                    . ';6abf4a82;"Товар 8";"в наличии";73494;61245' . "\n"
                    . ';fa005713;"Товар 9";"под заказ";6506;5609' . "\n"
                    . ';8d076785;"Товар 10";"в наличии";0;85812';
        $expected = ';Артикул;Название;"В наличии";"Старая цена";Стоимость' . "\n"
                  . ';"Категория 1"' . "\n"
                  . ';;"Категория 11"' . "\n"
                  . ';;;"Категория 111"' . "\n"
                  . $goodsBlock . "\n"
                  . ';;;"Категория 112"' . "\n"
                  . $goodsBlock . "\n"
                  . ';;;"Категория 113"' . "\n"
                  . $goodsBlock . "\n"
                  . ';;"Категория 12"' . "\n"
                  . $goodsBlock . "\n"
                  . ';;"Категория 13"' . "\n"
                  . $goodsBlock . "\n"
                  . ';"Категория 2"' . "\n"
                  . $goodsBlock . "\n"
                  . ';"Категория 3"' . "\n"
                  . $goodsBlock;
        $this->assertEquals($expected, $result);

        // $csv = new CSV($result);
        // $result = $csv->data;

        // $this->assertEquals('Артикул', $result[0][1]);
        // $this->assertEquals('Название', $result[0][2]);
        // $this->assertEmpty($result[0][8]);
        // $this->assertEquals('Видимость', $result[0][9]);
        // $this->assertEquals('URN', $result[0][10]);
        // $this->assertEquals('Категория 1', $result[1][1]);
        // $this->assertEquals('Категория 11', $result[2][2]);
        // $this->assertEquals('Товар 1', $result[4][2]);
        // $this->assertEquals('под заказ', $result[4][5]);
        // $this->assertEmpty($result[4][8]);
        // $this->assertEquals(1, $result[4][9]);
        // $this->assertEquals('tovar_1', $result[4][10]);

        $col1->pid = 1;
        $col1->commit();
        $col2->pid = 1;
        $col2->commit();
    }
}
