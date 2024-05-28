<?php
/**
 * Тест класса PriceLoader
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса PriceLoader
 * @covers RAAS\CMS\Shop\PriceLoader
 */
class PriceLoaderTest extends BaseTest
{
    public static $tables = [
        'cms_blocks',
        'cms_blocks_material',
        'cms_blocks_pages_assoc',
        'cms_blocks_search_material_types_assoc',
        'cms_fields',
        'cms_fields_form_vis',
        'cms_forms',
        'cms_material_types',
        'cms_material_types_affected_pages_for_materials_cache',
        'cms_material_types_affected_pages_for_self_cache',
        'cms_materials',
        'cms_materials_affected_pages_cache',
        'cms_materials_pages_assoc',
        'cms_pages',
        'cms_shop_cart_types_material_types_assoc',
        'cms_shop_imageloaders',
        'cms_shop_priceloaders',
        'cms_shop_priceloaders_columns',
        'cms_snippets',
    ];

    /**
     * Тест метода commit()
     */
    public function testCommit()
    {
        $materialType = new Material_Type(['name' => 'Тестовый тип материалов']);
        $materialType->commit();
        $materialTypeId = $materialType->id;

        $loader = new PriceLoader(['mtype' => $materialTypeId]);
        $loader->commit();

        $this->assertEquals('Тестовый тип материалов', $loader->name);
        $this->assertEquals('testovyi_tip_materialov', $loader->urn);

        PriceLoader::delete($loader);
        Material_Type::delete($materialType);
    }


    /**
     * Тест метода upload()
     */
    public function testUpload()
    {
        $interface = new Snippet([
            'urn' => 'priceloadertestinterface',
            'description' => '<' . '?php return [
                    "Loader" => $Loader,
                    "file" => $file,
                    "Page" => $Page,
                    "test" => $test,
                    "clear" => $clear,
                    "rows" => $rows,
                    "cols" => $cols,
                ];' . "\n"
        ]);
        $interface->commit();
        $interfaceId = $interface->id;
        $loader = new PriceLoader([
            'mtype' => 4, // Каталог продукции
            'cat_id' => 15, // Каталог продукции
            'interface_id' => $interfaceId,
            'rows' => 2,
            'cols' => 1,
        ]);
        $loader->commit();

        $result = $loader->upload(
            ['name' => 'test.xls', 'tmp_name' => $this->getResourcesDir() . '/test.xls'],
            null,
            true,
            true
        );

        $this->assertEquals($loader, $result['Loader']);
        $this->assertEquals('test.xls', $result['file']['name']);
        $this->assertEquals($this->getResourcesDir() . '/test.xls', $result['file']['tmp_name']);
        $this->assertInstanceOf(Page::class, $result['Page']);
        $this->assertEquals(15, $result['Page']->id);
        $this->assertTrue($result['test']);
        $this->assertTrue($result['clear']);
        $this->assertEquals(2, $result['rows']);
        $this->assertEquals(1, $result['cols']);

        PriceLoader::delete($loader);
        Snippet::delete($interface);
    }


    /**
     * Тест метода download()
     */
    public function testDownload()
    {
        $interface = new Snippet([
            'urn' => 'priceloadertestinterface',
            'description' => '<' . '?php return [
                    "Loader" => $Loader,
                    "Page" => $Page,
                    "rows" => $rows,
                    "cols" => $cols,
                    "type" => $type,
                    "encoding" => $encoding,
                ];' . "\n"
        ]);
        $interface->commit();
        $interfaceId = $interface->id;
        $loader = new PriceLoader([
            'mtype' => 4, // Каталог продукции
            'cat_id' => 15, // Каталог продукции
            'interface_id' => $interfaceId,
            'rows' => 2,
            'cols' => 1,
        ]);
        $loader->commit();

        $result = $loader->download(
            null,
            null,
            null,
            'csv',
            'utf-8'
        );

        $this->assertEquals($loader, $result['Loader']);
        $this->assertInstanceOf(Page::class, $result['Page']);
        $this->assertEquals(15, $result['Page']->id);
        $this->assertEquals(2, $result['rows']);
        $this->assertEquals(1, $result['cols']);
        $this->assertEquals('csv', $result['type']);
        $this->assertEquals('utf-8', $result['encoding']);

        PriceLoader::delete($loader);
        Snippet::delete($interface);
    }
}
