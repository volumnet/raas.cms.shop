<?php
/**
 * Файл теста интерфейса Альфа-банка
 */
namespace RAAS\CMS\Shop;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\Form_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест интерфейса Альфа-банка
 */
#[CoversClass(AlfaBankInterface::class)]
class AlfaBankInterfaceTest extends BaseTest
{
    public static $tables = [
        'cms_data',
        'cms_fields',
        'cms_forms',
        'cms_materials',
        'cms_pages',
        'cms_shop_blocks_cart',
        'cms_shop_cart_types',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_orders_goods',
        'cms_shop_orders_history',
        'cms_shop_priceloaders',
        'cms_snippets',
    ];

    public static function setUpBeforeClass(): void
    {
        ControllerFrontend::i()->exportLang(Application::i(), 'ru');
        ControllerFrontend::i()->exportLang(Package::i(), 'ru');
        ControllerFrontend::i()->exportLang(Module::i(), 'ru');
        // ControllerFrontend::i()->exportLang(UsersModule::i(), 'ru');
        parent::setUpBeforeClass();
    }


    /**
     * Тест метода getURL
     * @param bool $test Тестовый режим
     * @param string $expected Ожидаемое значение
     */
    #[TestWith([true, 'https://alfa.rbsuat.com/payment/rest/'])]
    #[TestWith([false, 'https://payment.alfabank.ru/payment/rest/'])]
    public function testGetURL(bool $test, string $expected)
    {
        $interface = new AlfaBankInterface();

        $result = $interface->getURL($test);

        $this->assertEquals($expected, $result);
    }
}
