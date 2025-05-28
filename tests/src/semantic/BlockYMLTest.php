<?php
/**
 * Тест класса Block_YML
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as RAASControllerFrontend;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

/**
 * Тест класса Block_YML
 */
#[CoversClass(Block_YML::class)]
class BlockYMLTest extends BaseTest
{
    public static $tables = [
        'cms_access',
        'cms_access_pages_cache',
        'cms_blocks',
        'cms_blocks_material',
        'cms_blocks_pages_assoc',
        'cms_blocks_search_pages_assoc',
        'cms_data',
        'cms_fieldgroups',
        'cms_fields',
        'cms_material_types',
        'cms_material_types_affected_pages_for_materials_cache',
        'cms_material_types_affected_pages_for_self_cache',
        'cms_materials',
        'cms_materials_affected_pages_cache',
        'cms_materials_pages_assoc',
        'cms_menus',
        'cms_pages',
        'cms_shop_blocks_yml',
        'cms_shop_blocks_yml_currencies',
        'cms_shop_blocks_yml_fields',
        'cms_shop_blocks_yml_ignored_fields',
        'cms_shop_blocks_yml_material_types_assoc',
        'cms_shop_blocks_yml_pages_assoc',
        'cms_shop_blocks_yml_params',
        'cms_shop_orders',
        'cms_shop_priceloaders',
    ];


    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        RAASControllerFrontend::i()->exportLang(Application::i(), 'ru');
        RAASControllerFrontend::i()->exportLang(Package::i(), 'ru');
        RAASControllerFrontend::i()->exportLang(Module::i(), 'ru');
    }


    /**
     * Тест метода commit
     */
    public function testCommit()
    {
        $block = new Block_YML([
            'cats' => [3], // Услуги
            'shop_name' => 'Test shop',
            'company' => 'Test company',
            'agency' => 'Test agency',
            'email' => 'test@test.org',
            'cpa' => 1,
            'default_currency' => 'RUB',
            'delivery_options' => '[]',
            'pickup_options' => '[]',
            'meta_cats' => [3], // Услуги
            'meta_currencies' => [['currency_name' => 'RUB', 'currency_rate' => 1, 'currency_plus' => 2]],
        ]);
        $block->commit();
        $blockId = $block->id;

        $block = new Block_YML($blockId);
        $catsIds = array_map(function ($x) {
            return (int)$x->id;
        }, $block->catalog_cats);
        $this->assertStringContainsString('Яндекс', $block->name);
        $this->assertContains(3, $catsIds);

        Block_YML::delete($block);
    }


    /**
     * Тест получения свойства currencies
     */
    public function testGetCurrencies()
    {
        $block = new Block_YML([
            'cats' => [3], // Услуги
            'shop_name' => 'Test shop',
            'company' => 'Test company',
            'agency' => 'Test agency',
            'email' => 'test@test.org',
            'cpa' => 1,
            'default_currency' => 'RUB',
            'delivery_options' => '[]',
            'pickup_options' => '[]',
            'meta_cats' => [3], // Услуги
            'meta_currencies' => [['currency_name' => 'RUB', 'currency_rate' => 1, 'currency_plus' => 2]],
        ]);
        $block->commit();

        $result = $block->currencies;

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result['RUB']['rate']);
        $this->assertEquals(2, $result['RUB']['plus']);

        Block_YML::delete($block);
    }


    /**
     * Тест методов addType(), removeType(), а также получения свойства types
     */
    public function testAddTypeAndRemoveTypeAndGetTypes()
    {
        $block = new Block_YML([
            'cats' => [3], // Услуги
            'shop_name' => 'Test shop',
            'company' => 'Test company',
            'agency' => 'Test agency',
            'email' => 'test@test.org',
            'cpa' => 1,
            'default_currency' => 'RUB',
            'delivery_options' => '[]',
            'pickup_options' => '[]',
            'meta_cats' => [3], // Услуги
            'meta_currencies' => [['currency_name' => 'RUB', 'currency_rate' => 1, 'currency_plus' => 2]],
        ]);
        $block->commit();
        $blockId = $block->id;

        $block = new Block_YML($blockId);
        $this->assertEmpty($block->types);

        $block->addType(
            new Material_Type(4), // Каталог продукции
            'vendor.model',
            [
                'available' => ['field_id' => 31],
                'delivery' => ['field_static_value' => 1],
                'price' => ['field_callback' => 'return (int)$x;'],
                'name' => ['field_id' => 'name'],
            ],
            [
                [
                    'param_name' => 'Спецпредложение',
                    'field_id' => 30,
                    'field_callback' => 'return $x ? \'true\' : \'false\';'
                ],
                [
                    'param_name' => 'Тест',
                    'field_id' => 'description',
                    'param_unit' => 'Шт.',
                    'param_static_value' => 1,
                ],
            ],
            ['name', 'urn', 35], // Игнорируемые параметры, 35 - связанные товары
            1, // Использовать все неиспользованные поля
            'return null;', // callback параметров
        );

        $block = new Block_YML($blockId);

        $result = $block->types;

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Material_Type::class, $result[4]);
        $this->assertEquals('vendor.model', $result[4]->settings['type']);
        $this->assertEquals(true, $result[4]->settings['param_exceptions']);
        $this->assertEquals('return null;', $result[4]->settings['params_callback']);
        $this->assertInstanceOf(Material_Field::class, $result[4]->settings['fields']['available']['field']);
        $this->assertEquals(31, $result[4]->settings['fields']['available']['field']->id);
        $this->assertEquals(1, $result[4]->settings['fields']['delivery']['value']);
        $this->assertEquals('return (int)$x;', $result[4]->settings['fields']['price']['callback']);
        $this->assertEquals('name', $result[4]->settings['fields']['name']['field_id']);
        $this->assertEquals('Спецпредложение', $result[4]->settings['params'][0]['name']);
        $this->assertEquals(30, $result[4]->settings['params'][0]['field']->id);
        $this->assertEquals('return $x ? \'true\' : \'false\';', $result[4]->settings['params'][0]['callback']);
        $this->assertEquals('Тест', $result[4]->settings['params'][1]['name']);
        $this->assertEquals('description', $result[4]->settings['params'][1]['field_id']);
        $this->assertEquals('Шт.', $result[4]->settings['params'][1]['unit']);
        $this->assertEquals(1, $result[4]->settings['params'][1]['value']);
        $ignoredFieldsIds = array_map(function ($x) {
            return ($x instanceof Material_Field) ? (int)$x->id : $x;
        }, $result[4]->settings['ignored']);
        $this->assertContains('name', $ignoredFieldsIds);
        $this->assertContains('urn', $ignoredFieldsIds);
        $this->assertContains(35, $ignoredFieldsIds);

        $block->removeType(new Material_Type(4)); // Каталог продукции
        $block = new Block_YML($blockId);

        $this->assertEmpty($block->types);

        Block_YML::delete($block);
    }


    /**
     * Тест метода getAddData()
     */
    public function testGetAddData()
    {
        $block = new Block_YML([
            'cats' => [3], // Услуги
            'shop_name' => 'Test shop',
            'company' => 'Test company',
            'agency' => 'Test agency',
            'email' => 'test@test.org',
            'cpa' => 1,
            'default_currency' => 'RUB',
            'delivery_options' => '[]',
            'pickup_options' => '[]',
            'meta_cats' => [3], // Услуги
            'meta_currencies' => [['currency_name' => 'RUB', 'currency_rate' => 1, 'currency_plus' => 2]],
        ]);

        $result = $block->getAddData();

        $this->assertEquals(0, $result['id']);
        $this->assertEquals('Test shop', $result['shop_name']);
        $this->assertEquals('Test company', $result['company']);
        $this->assertEquals('Test agency', $result['agency']);
        $this->assertEquals('test@test.org', $result['email']);
        $this->assertEquals(1, $result['cpa']);
        $this->assertEquals('RUB', $result['default_currency']);
        $this->assertEquals('[]', $result['delivery_options']);
        $this->assertEquals('[]', $result['pickup_options']);

        $block->commit();
        $blockId = $block->id;

        $result = $block->getAddData();

        $this->assertEquals($blockId, $result['id']);
        $this->assertEquals('Test shop', $result['shop_name']);
        $this->assertEquals('Test company', $result['company']);
        $this->assertEquals('Test agency', $result['agency']);
        $this->assertEquals('test@test.org', $result['email']);
        $this->assertEquals(1, $result['cpa']);
        $this->assertEquals('RUB', $result['default_currency']);
        $this->assertEquals('[]', $result['delivery_options']);
        $this->assertEquals('[]', $result['pickup_options']);

        Block_YML::delete($block);
    }


    /**
     * Тест метода pageCommitEventListener()
     */
    public function testPageCommitEventListener()
    {
        $block = new Block_YML([
            'cats' => [3], // Услуги
            'shop_name' => 'Test shop',
            'company' => 'Test company',
            'agency' => 'Test agency',
            'email' => 'test@test.org',
            'cpa' => 1,
            'default_currency' => 'RUB',
            'delivery_options' => '[]',
            'pickup_options' => '[]',
            'meta_cats' => [3], // Услуги
            'meta_currencies' => [['currency_name' => 'RUB', 'currency_rate' => 1, 'currency_plus' => 2]],
        ]);
        $block->commit();
        $blockId = $block->id;
        $page = new Page(['name' => 'test', 'pid' => 3]);
        $page->commit();
        $block = new Block_YML($blockId);

        $catsIds = array_map(function ($x) {
            return (int)$x->id;
        }, $block->catalog_cats);
        $this->assertContains((int)$page->id, $catsIds);

        Page::delete($page);
        Block_YML::delete($block);
    }
}
