<?php
/**
 * Тест класса ImageLoader
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса ImageLoader
 * @covers RAAS\CMS\Shop\ImageLoader
 */
class ImageLoaderTest extends BaseTest
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

        $loader = new ImageLoader(['mtype' => $materialTypeId]);
        $loader->commit();

        $this->assertEquals('Тестовый тип материалов', $loader->name);
        $this->assertEquals('testovyi_tip_materialov', $loader->urn);

        ImageLoader::delete($loader);
        Material_Type::delete($materialType);
    }


    /**
     * Тест метода upload()
     */
    public function testUpload()
    {
        $interface = new Snippet([
            'urn' => 'imageloadertestinterface',
            'description' => '<' . '?php return [
                    "Loader" => $Loader,
                    "files" => $files,
                    "test" => $test,
                    "clear" => $clear,
                ];' . "\n"
        ]);
        $interface->commit();
        $interfaceId = $interface->id;
        $loader = new ImageLoader([
            'mtype' => 4, // Каталог продукции
            'interface_id' => $interfaceId,
        ]);
        $loader->commit();

        $result = $loader->upload(
            [['name' => 'test.xls', 'tmp_name' => $this->getResourcesDir() . '/test.xls']],
            true,
            true
        );

        $this->assertEquals($loader, $result['Loader']);
        $this->assertEquals('test.xls', $result['files'][0]['name']);
        $this->assertEquals($this->getResourcesDir() . '/test.xls', $result['files'][0]['tmp_name']);
        $this->assertTrue($result['test']);
        $this->assertTrue($result['clear']);

        ImageLoader::delete($loader);
        Snippet::delete($interface);
    }


    /**
     * Тест метода download()
     */
    public function testDownload()
    {
        $interface = new Snippet([
            'urn' => 'imageloadertestinterface',
            'description' => '<' . '?php return [
                    "Loader" => $Loader,
                ];' . "\n"
        ]);
        $interface->commit();
        $interfaceId = $interface->id;
        $loader = new ImageLoader([
            'mtype' => 4, // Каталог продукции
            'interface_id' => $interfaceId,
        ]);
        $loader->commit();

        $result = $loader->download();

        $this->assertEquals($loader, $result['Loader']);

        ImageLoader::delete($loader);
        Snippet::delete($interface);
    }
}
