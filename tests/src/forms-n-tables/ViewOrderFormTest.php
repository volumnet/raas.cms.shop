<?php
/**
 * Тест класса ViewOrderForm
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Field as RAASField;
use RAAS\FormTab;
use RAAS\User as RAASUser;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса ViewOrderForm
 */
#[CoversClass(ViewOrderForm::class)]
class ViewOrderFormTest extends BaseTest
{
    public static $tables = [
        'cms_data',
        'cms_fieldgroups',
        'cms_fields',
        'cms_forms',
        'cms_materials',
        'cms_shop_cart_types',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_orders_goods',
        'cms_shop_orders_history',
        'cms_shop_orders_statuses',
        'cms_shop_priceloaders',
        'cms_snippets',
    ];


    public static function setUpBeforeClass(): void
    {
        Application::i()->initPackages();
        parent::setUpBeforeClass();
    }


    /**
     * Тест конструктора класса
     */
    public function testConstruct()
    {
        $paymentInterfaceSnippet = new Snippet(['urn' => 'payment_test']);
        $paymentInterfaceSnippet->commit();
        $order = new Order(['payment_interface_id' => $paymentInterfaceSnippet->id]);
        $order->commit();
        $form = new ViewOrderForm(['Item' => $order]);
        $interfaceField = $form->children['common']->children['payment_interface_id'];

        $this->assertInstanceOf(RAASField::class, $interfaceField);
        $this->assertStringContainsString($order->id, $form->caption);

        Snippet::delete($paymentInterfaceSnippet);
        Order::delete($order);
    }


    /**
     * Тест получения свойства view
     */
    public function testGetView()
    {
        $form = new ViewOrderForm();

        $result = $form->view;

        $this->assertInstanceOf(ViewSub_Orders::class, $result);
    }


    /**
     * Тест получения наследуемых свойств
     */
    public function testGetDefault()
    {
        $order = new Order();
        $form = new ViewOrderForm(['Item' => $order]);

        $result = $form->Item;

        $this->assertEquals($order, $result);
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
            'status_id' => '2', // Обработан
            'paid' => '1',
            'description' => 'Комментарий',
        ];
        $order = new Order(['pid' => 1]); // 1 - корзина
        $order->commit();
        $form = new ViewOrderForm(['Item' => $order]);
        $form->process();

        $this->assertCount(1, $order->history);
        $this->assertEquals('Комментарий', $order->history[0]->description);
        $this->assertEquals(2, $order->history[0]->status_id);
        $this->assertEquals(true, $order->history[0]->paid);

        $this->assertEquals(true, $order->paid);
        $this->assertEquals(2, $order->status_id);

        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $form = new ViewOrderForm(['Item' => $order]);
        $form->process();

        $this->assertEquals('', $form->DATA['paid']);

        $_POST = $oldPost;
        $_SERVER = $oldServer;
        Order::delete($order);
    }
}
