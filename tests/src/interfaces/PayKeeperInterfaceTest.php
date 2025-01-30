<?php
/**
 * Файл теста интерфейса PayKeeper
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
 * Тест интерфейса PayKeeper
 */
#[CoversClass(PayKeeperInterface::class)]
class PayKeeperInterfaceTest extends BaseTest
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
     * @param string $payKeeperHost Хост PayKeeper в явном виде
     * @param string $expected Ожидаемое значение
     */
    #[TestWith([true, '', 'https://demo.paykeeper.ru'])]
    #[TestWith([false, 'test123.paykeeper.ru', 'https://test123.paykeeper.ru'])]
    #[TestWith([false, '', 'https://test.server.paykeeper.ru'])]
    public function testGetURL(bool $test, string $payKeeperHost, string $expected)
    {
        $block = new Block_Cart();
        if ($payKeeperHost) {
            $block->params = http_build_query(['paykeeperHost' => $payKeeperHost]);
        }
        $interface = new PayKeeperInterface($block, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getURL($test);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода checkWebhook - положительный результат
     */
    public function testCheckWebhook()
    {
        $interface = new PayKeeperInterface(
            new Block_Cart(),
            new Page(),
            [],
            [
                'id' => 'aaaa-bbbb-cccc-dddd',
                'orderid' => '123',
                'key' => 'aaa',
            ],
            [],
            [],
            [],
            ['REQUEST_METHOD' => 'POST', 'HTTP_HOST' => 'test'],
        );

        $result = $interface->checkWebhook();

        $this->assertEquals(['paymentId' => 'aaaa-bbbb-cccc-dddd', 'orderId' => '123'], $result);
    }


    /**
     * Тест метода checkWebhook - отрицательный результат
     */
    public function testCheckWebhookWithNegative()
    {
        $interface = new PayKeeperInterface(new Block_Cart(), new Page(), ['orderId' => 123]);

        $result = $interface->checkWebhook();

        $this->assertNull($result);
    }


    /**
     * Тест метода processWebhookResponse()
     */
    public function testProcessWebhookResponse()
    {
        $order = new Order();
        $block = new Block_Cart(['epay_pass2' => 'verysecretseed', 'epay_test' => 1]);
        $page = new Page();
        $webhookResponse = ['paymentId' => 'aaaa-bbbb-cccc-dddd', 'orderId' => '123'];
        $checkKey = md5('aaaa-bbbb-cccc-dddd10000Test client123verysecretseed');
        $returnHash = md5('aaaa-bbbb-cccc-ddddverysecretseed');
        $interface = $this->getMockBuilder(PayKeeperInterface::class)
            ->setConstructorArgs([
                $block,
                $page,
                [],
                [
                    'id' => 'aaaa-bbbb-cccc-dddd',
                    'orderid' => '123',
                    'sum' => '10000',
                    'clientid' => 'Test client',
                    'key' => $checkKey,
                ]
            ])
            ->onlyMethods(['doLog'])
            ->getMock();
        $matcher = $this->exactly(2);
        $interface
            ->expects($matcher)
            ->method('doLog')
            ->willReturnCallback(function ($value) use ($matcher, $checkKey, $returnHash) {
                match ($matcher->numberOfInvocations()) {
                    1 =>  $this->assertEquals('checkKey ' . $checkKey . ' / ' . $checkKey, $value),
                    2 =>  $this->assertEquals('returnHash OK ' . $returnHash, $value),
                };
            });

        ob_start();
        $interface->processWebhookResponse($order, $block, $page, $webhookResponse);
        $result = ob_get_clean();

        $this->assertEquals('OK ' . $returnHash, $result);
    }


    /**
     * Тест метода findOrder() - случай возврата на страницу
     */
    public function testFindOrder()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd', 'payment_interface_id' => $paymentInterfaceId]);
        $order->commit();
        $orderId = $order->id;
        $interface = new PayKeeperInterface(
            new Block_Cart(['epay_interface_id' => $paymentInterfaceId]),
            null,
            ['payment_id' => 'aaaa-bbbb-cccc-dddd'] // Передавали в возвратном URL
        );

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Snippet::delete($paymentInterface);
        Order::delete($order);
    }


    /**
     * Тест метода findOrder() - случай с сессией
     */
    public function testFindOrderDefault()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $interface = new PayKeeperInterface(new Block_Cart(), new Page(), [], [], [], ['orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }


    /**
     * Тест метода exec
     */
    public function testExec()
    {
        $interface = $this->getMockBuilder(PayKeeperInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass'])])
            ->onlyMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');

        $result = $interface->exec('/info/settings/token/', [], true);

        $this->assertEquals([], $result);
    }


    /**
     * Тест метода exec - случай с POST
     */
    public function testExecWithPost()
    {
        $interface = $this->getMockBuilder(PayKeeperInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'login', 'epay_pass1' => 'pass'])])
            ->onlyMethods(['doLog'])
            ->getMock();
        $interface->expects($this->exactly(2))->method('doLog'); // Один на получение токена, один - на собственно запрос
        $result = $interface->exec('/change/invoice/preview/', ['aaa' => 'bbb'], true);

        $this->assertEquals([], $result);
    }


    /**
     * Тест метода getRegisterOrderData
     */
    public function testGetRegisterOrderData()
    {
        $materialField = new Form_Field([
            'pid' => 3,
            'datatype' => 'material',
            'urn' => 'associtem',
            'name' => 'Ассоциированный товар',
        ]);
        $materialField->commit();
        $order = new Order([
            'uid' => 2,
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
                ['name' => 'Скидка', 'realprice' => -1400, 'amount' => 1],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $order->fields['email']->addValue('test@test.org');
        $order->fields['phone']->addValue('+7 (999) 000-00-00');
        $order->fields['last_name']->addValue('Тестовый');
        $order->fields['first_name']->addValue('Пользователь');
        $order->fields['associtem']->addValue(10);
        $order->address = 'Тестовый адрес';
        $order->city = 'Город';
        $order->country = 'EU';
        $order->inn = '1234567890';
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new PayKeeperInterface(
            null,
            null,
            [],
            [],
            [],
            [],
            [
                'HTTP_HOST' => 'test',
                'HTTP_X_FORWARDED_FOR' => '192.168.0.2, 192.168.0.1, 127.0.0.1',
                'REMOTE_ADDR' => '127.0.0.1'
            ]
        );

        $result = $interface->getRegisterOrderData($order, $block, $page);


        $this->assertEquals(12600, $result['pay_amount']);
        $this->assertEquals('Тестовый Пользователь', $result['clientid']);
        $this->assertEquals($orderId, $result['orderid']);
        $this->assertEquals('test@test.org', $result['client_email']);
        $this->assertEquals('79990000000', $result['client_phone']);
        $this->assertEquals('http://test/cart/result/', $result['user_result_callback']); // Система сама добавляет GET-параметры https://docs.paykeeper.ru/metody-integratsii/html-forma/

        $fz54Data = json_decode($result['service_name'], true);
        $this->assertEquals('Заказ #' . $orderId . ' на сайте test', $fz54Data['service_name']);
        $this->assertEquals('Тестовый Пользователь', $fz54Data['receipt_properties']['client']['identity']);
        $this->assertEquals('1234567890', $fz54Data['receipt_properties']['client']['inn']);

        $this->assertCount(3, $fz54Data['cart']);
        $this->assertEquals('Товар 2', $fz54Data['cart'][1]['name']);
        $this->assertEquals(1800, $fz54Data['cart'][1]['price']);
        $this->assertEquals(2, $fz54Data['cart'][1]['quantity']);
        $this->assertEquals(3600, $fz54Data['cart'][1]['sum']);
        $this->assertEquals('vat0', $fz54Data['cart'][1]['tax']);
        $this->assertEquals('goods', $fz54Data['cart'][1]['item_type']);
        $this->assertEquals('prepay', $fz54Data['cart'][1]['payment_mode']);

        Order::delete($order);
        Form_Field::delete($materialField);
    }


    /**
     * Тест метода getRegisterOrderData - случай с полем полного имени
     */
    public function testGetRegisterOrderDataWithFullName()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $order->full_name = 'Тестовый пользователь 123';
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new PayKeeperInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getRegisterOrderData($order, $block, $page);

        $fz54Data = json_decode($result['service_name'], true);
        $this->assertEquals('Тестовый пользователь 123', $fz54Data['receipt_properties']['client']['identity']);

        Order::delete($order);
    }


    /**
     * Тест метода registerOrderWithData()
     */
    public function testRegisterOrderWithData()
    {
        $interface = $this->getMockBuilder(PayKeeperInterface::class)->onlyMethods(['exec'])->getMock();
        $interface->expects($this->once())->method('exec')->with('/change/invoice/preview/', ['aaa'], false);

        $result = $interface->registerOrderWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseResponseCommonErrors()
     */
    public function testParseResponseCommonErrors()
    {
        $interface = new PayKeeperInterface();

        $result = $interface->parseResponseCommonErrors(json_decode('{
            "result":"fail",
            "msg":"Error description"
        }', true));

        $this->assertEquals([
            ['message' => 'Error description']
        ], $result);
    }


    /**
     * Тест метода parseInitResponse()
     */
    public function testParseInitResponse()
    {
        $interface = new PayKeeperInterface();

        $result = $interface->parseInitResponse(json_decode('{
          "invoice_id" : "20120229133742255",
          "invoice_url" : "https://pay.example.com/bill/20120229133742255",
          "invoice"    : "&lt;HTML&gt;&lt;HEAD&gt;&lt;META http-equiv=Content-Type ..."
        }', true));

        $this->assertEquals([
            'errors' => [],
            'paymentId' => '20120229133742255',
            'paymentURL' => 'https://pay.example.com/bill/20120229133742255',
        ], $result);
    }

    /**
     * Тест метода getOrderStatusWithData()
     */
    public function testGetOrderStatusWithData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $interface = $this->getMockBuilder(PayKeeperInterface::class)
            ->onlyMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with(
            '/info/invoice/byid/?id=aaaa-bbbb-cccc-dddd',
            [],
            false
        );

        $result = $interface->getOrderStatusWithData($order, new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseOrderStatusResponse()
     */
    public function testParseOrderStatusResponse()
    {
        $interface = new PayKeeperInterface();

        $result = $interface->parseOrderStatusResponse(json_decode('{
            "id"                : "20110115085347329",
            "clientid"          : "Ivanov Ivan Ivanovich",
            "orderid"          : "1337",
            "paymentid"        : "7331",
            "service_name"     : "Document delivery",
            "client_email"     : "ivanov@ivan.ivanovich.com",
            "client_phone"     : "+79254973590",
            "created_datetime" : "2011-01-15 08:53:47",
            "paid_datetime"    : "2011-01-16 14:12:00",
            "expiry_datetime"  : "2011-01-19 00:00:00",
            "pay_amount"       : "1205.98",
            "status"           : "paid",
            "user_id"           : 9
        }', true));

        $this->assertEquals([
            'errors' => [],
            'status' => 'paid',
        ], $result);
    }
}
