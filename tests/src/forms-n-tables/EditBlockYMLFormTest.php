<?php
/**
 * Тест класса EditBlockYMLForm
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Application;
use RAAS\Field as RAASField;
use RAAS\FormTab;
use RAAS\User as RAASUser;
use RAAS\CMS\InterfaceField;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса EditBlockYMLForm
 * @covers RAAS\CMS\Shop\EditBlockYMLForm
 */
class EditBlockYMLFormTest extends BaseTest
{
    public static $tables = [
        'cms_access',
        'cms_blocks',
        'cms_blocks_pages_assoc',
        'cms_fields',
        'cms_groups',
        'cms_material_types',
        'cms_pages',
        'cms_shop_blocks_yml',
        'cms_shop_blocks_yml_currencies',
        'cms_shop_blocks_yml_material_types_assoc',
        'cms_shop_blocks_yml_pages_assoc',
        'cms_snippet_folders',
        'cms_snippets',
    ];

    /**
     * Тест получения свойства view
     */
    public function testGetView()
    {
        $form = new EditBlockYMLForm();

        $result = $form->view;

        $this->assertInstanceOf(ViewSub_Main::class, $result);
    }


    /**
     * Тест получения наследуемых свойств
     */
    public function testGetDefault()
    {
        $block = new Block_YML();
        $form = new EditBlockYMLForm(['Item' => $block]); // 39 - блок информера корзины

        $result = $form->Item;

        $this->assertEquals($block, $result);
    }

    /**
     * Тест конструктора класса
     */
    public function testConstruct()
    {
        $form = new EditBlockYMLForm();
        $interfaceField = $form->children['serviceTab']->children['interface_id'];

        $this->assertInstanceOf(InterfaceField::class, $interfaceField);
        $this->assertEquals(YMLInterface::class, $interfaceField->default);
        $this->assertEquals(YMLInterface::class, $interfaceField->meta['rootInterfaceClass']);

        $this->assertEquals('shop_name', $form->children['commonTab']->children['shop_name']->name);
        $this->assertEquals('checkbox', $form->children['catsTab']->children['meta_cats']->type);
    }


    /**
     * Тест конструктора класса - случай экспорта
     */
    public function testConstructWithExport()
    {
        $oldPost = $_POST;
        $oldServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'name' => 'Тестовый блок',
            'cats' => [1],
            'location' => 'content',
            'delivery_options@cost' => ['111', '222', '333'],
            'delivery_options@days' => ['1', '2', '3'],
            'delivery_options@order_before' => ['11', '22', '33'],
            'rate' => ['USD' => 'CBRF', 'EUR' => '-1'],
            'rate_txt' => ['EUR' => '99.99'],
            'plus' => ['USD' => '2'],
            'meta_cats' => [15], // Корень каталога
        ];
        $block = new Block_YML();
        $form = new EditBlockYMLForm(['Item' => $block]);
        $result = $form->process();

        $this->assertEmpty($result['localError']);

        $deliveryOptions = json_decode((string)$block->delivery_options, true);
        $this->assertEquals('111', $deliveryOptions[0]['cost']);
        $this->assertEquals('1', $deliveryOptions[0]['days']);
        $this->assertEquals('11', $deliveryOptions[0]['order_before']);
        $this->assertEquals('222', $deliveryOptions[1]['cost']);
        $this->assertEquals('2', $deliveryOptions[1]['days']);
        $this->assertEquals('22', $deliveryOptions[1]['order_before']);
        $this->assertEquals('333', $deliveryOptions[2]['cost']);
        $this->assertEquals('3', $deliveryOptions[2]['days']);
        $this->assertEquals('33', $deliveryOptions[2]['order_before']);
        $this->assertEquals('CBRF', $block->currencies['USD']['rate']);
        $this->assertEquals(2, $block->currencies['USD']['plus']);
        $this->assertEquals(99.99, $block->currencies['EUR']['rate']);
        $this->assertEquals(0, $block->currencies['EUR']['plus']);

        $_POST = $oldPost;
        $_SERVER = $oldServer;
        Block_YML::delete($block);
    }


    /**
     * Тест конструктора класса - случай пропуска категорий
     */
    public function testConstructWithNoCatsError()
    {
        $oldPost = $_POST;
        $oldServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'name' => 'Тестовый блок',
            'cats' => [1],
            'location' => 'content',
            'delivery_options@cost' => ['111', '222', '333'],
            'delivery_options@days' => ['1', '2', '3'],
            'delivery_options@order_before' => ['11', '22', '33'],
            'rate' => ['USD' => 'CBRF', 'EUR' => '-1'],
            'rate_txt' => ['EUR' => '99.99'],
            'plus' => ['USD' => '2'],
        ];
        $block = new Block_YML();
        $form = new EditBlockYMLForm(['Item' => $block]);
        $result = $form->process();

        $this->assertEquals('MISSED', $result['localError'][0]['name']);
        $this->assertEquals('meta_cats', $result['localError'][0]['value']);

        $_POST = $oldPost;
        $_SERVER = $oldServer;
        Block_YML::delete($block);
    }


    /**
     * Тест конструктора класса - случай импорта
     */
    public function testConstructWithImport()
    {
        $block = new Block_YML([
            'cats' => [3], // Услуги
            'shop_name' => 'Test shop',
            'company' => 'Test company',
            'agency' => 'Test agency',
            'email' => 'test@test.org',
            'cpa' => 1,
            'default_currency' => 'RUB',
            'delivery_options' => '[{"cost":"111","days":1,"order_before":11},{"cost":"222","days":2,"order_before":22}]',
            'pickup_options' => '[]',
            'meta_cats' => [3], // Услуги
            'meta_currencies' => [
                ['currency_name' => 'RUB', 'currency_rate' => 1, 'currency_plus' => 2],
                ['currency_name' => 'EUR', 'currency_rate' => 'CBRF'],
            ],
        ]);
        $block->commit();
        $form = new EditBlockYMLForm(['Item' => $block]);
        $result = $form->process();
        // var_dump($form->DATA);

        $this->assertEquals('111', $form->DATA['delivery_options'][0]['cost']);
        $this->assertEquals('1', $form->DATA['delivery_options'][0]['days']);
        $this->assertEquals('11', $form->DATA['delivery_options'][0]['order_before']);
        $this->assertEquals('222', $form->DATA['delivery_options'][1]['cost']);
        $this->assertEquals('2', $form->DATA['delivery_options'][1]['days']);
        $this->assertEquals('22', $form->DATA['delivery_options'][1]['order_before']);
        $this->assertEquals('-1', $form->DATA['rate[RUB]']); // Пока непонятно почему так, но работает
        $this->assertEquals('1', $form->DATA['rate_txt[RUB]']); // Пока непонятно почему так, но работает
        $this->assertEquals(2, $form->DATA['plus[RUB]']); // Пока непонятно почему так, но работает (возвращает строку 2.00 из decimal)
        $this->assertEquals('CBRF', $form->DATA['rate[EUR]']); // Пока непонятно почему так, но работает
        $this->assertEquals('', $form->DATA['rate_txt[EUR]']); // Пока непонятно почему так, но работает
        $this->assertEquals(0, $form->DATA['plus[EUR]']); // Пока непонятно почему так, но работает (возвращает строку 0.00 из decimal)

        Block_YML::delete($block);
    }
}
