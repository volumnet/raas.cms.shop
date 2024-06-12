<?php
/**
 * Файл теста менеджера обновлений
 * (поскольку мы не можем хранить все предыдущие версии, тестируем текущее состояние, без покрытия менеджера обновлений)
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Application;
use RAAS\CMS\Block;
use RAAS\CMS\Snippet;

/**
 * Класс теста обновлений
 */
class UpdaterTest extends BaseTest
{
    public static $tables = [
        'cms_blocks',
        'cms_shop_blocks_cart',
        'cms_shop_blocks_yml',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_priceloaders',
        'cms_snippets',
    ];


    /**
     * Тест состояния версии 4.3.71 - чтобы не было сниппетов интерфейсов и в блоках поменялись на классы
     */
    public function testState040371ReplaceSnippetsWithInterfacesClassnames()
    {
        $snippet = Snippet::importByURN('__raas_shop_cart_interface');
        $block = Block::spawn(38); // Блок корзины

        $this->assertNull($snippet);
        $this->assertEmpty($block->interface_id);
        $this->assertEquals(CartInterface::class, $block->interface_classname);


        $snippet = Snippet::importByURN('__raas_shop_imageloader_interface');
        $loader = new ImageLoader(1); // Загрузчик изображений

        $this->assertNull($snippet);
        $this->assertEmpty($loader->interface_id);
        $this->assertEquals(ImageloaderInterface::class, $loader->interface_classname);


        $snippet = Snippet::importByURN('__raas_shop_priceloader_interface');
        $loader = new PriceLoader(1); // Загрузчик прайсов

        $this->assertNull($snippet);
        $this->assertEmpty($loader->interface_id);
        $this->assertEquals(PriceloaderInterface::class, $loader->interface_classname);


        $snippet = Snippet::importByURN('__raas_shop_yml_interface');
        $block = Block::spawn(44); // Блок Яндекс.Маркета

        $this->assertNull($snippet);
        $this->assertEmpty($block->interface_id);
        $this->assertEquals(YMLInterface::class, $block->interface_classname);


        $snippet = Snippet::importByURN('__raas_my_orders_interface');

        $this->assertNull($snippet);


        $snippet = Snippet::importByURN('__raas_shop_compare_interface');

        $this->assertNull($snippet);


        $snippet = Snippet::importByURN('__raas_shop_goods_comments_interface');

        $this->assertNull($snippet);


        $snippet = Snippet::importByURN('__raas_shop_spec_interface');

        $this->assertNull($snippet);
    }


    /**
     * Тест состояния версии 4.3.71
     * - чтобы в cms_shop_orders было поле payment_interface_classname и ключ к нему
     * - чтобы в cms_shop_priceloaders было поле interface_classname и ключ к нему
     * - чтобы в cms_shop_imageloaders было поле interface_classname и ключ к нему
     * - чтобы в cms_shop_blocks_cart было поле epay_interface_classname и ключ к нему
     */
    public function testState040371BlocksInterfaceClassname()
    {
        $sqlQuery = "SHOW FIELDS FROM cms_shop_orders";
        $sqlResult = Application::i()->SQL->get($sqlQuery);
        $result = [];
        foreach ($sqlResult as $sqlRow) {
            $result[$sqlRow['Field']] = $sqlRow;
        }

        $this->assertEquals('payment_interface_classname', $result['payment_interface_classname']['Field']);
        $this->assertNotEmpty($result['payment_interface_classname']['Key']);


        $sqlQuery = "SHOW FIELDS FROM cms_shop_priceloaders";
        $sqlResult = Application::i()->SQL->get($sqlQuery);
        $result = [];
        foreach ($sqlResult as $sqlRow) {
            $result[$sqlRow['Field']] = $sqlRow;
        }

        $this->assertEquals('interface_classname', $result['interface_classname']['Field']);
        $this->assertNotEmpty($result['interface_classname']['Key']);


        $sqlQuery = "SHOW FIELDS FROM cms_shop_imageloaders";
        $sqlResult = Application::i()->SQL->get($sqlQuery);
        $result = [];
        foreach ($sqlResult as $sqlRow) {
            $result[$sqlRow['Field']] = $sqlRow;
        }

        $this->assertEquals('interface_classname', $result['interface_classname']['Field']);
        $this->assertNotEmpty($result['interface_classname']['Key']);


        $sqlQuery = "SHOW FIELDS FROM cms_shop_blocks_cart";
        $sqlResult = Application::i()->SQL->get($sqlQuery);
        $result = [];
        foreach ($sqlResult as $sqlRow) {
            $result[$sqlRow['Field']] = $sqlRow;
        }

        $this->assertEquals('epay_interface_classname', $result['epay_interface_classname']['Field']);
        $this->assertNotEmpty($result['epay_interface_classname']['Key']);
    }


    /**
     * Тест состояния версии 4.3.71 - чтобы в cms_shop_orders.user_agent по умолчанию была пустая строка
     */
    public function testState040371UserAgent()
    {
        $sqlQuery = "SELECT DEFAULT(user_agent) FROM cms_shop_orders";
        $sqlResult = Application::i()->SQL->getvalue($sqlQuery);

        $this->assertEquals('', $sqlResult);
    }
}
