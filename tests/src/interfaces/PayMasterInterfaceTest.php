<?php
/**
 * Файл теста интерфейса PayMaster
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
 * Тест интерфейса PayMaster
 */
#[CoversClass(PayMasterInterface::class)]
class PayMasterInterfaceTest extends BaseTest
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
    #[TestWith([true, 'https://paymaster.ru/api/v2/'])]
    #[TestWith([false, 'https://paymaster.ru/api/v2/'])]
    public function testGetURL(bool $test, string $expected)
    {
        $interface = new PayMasterInterface();

        $result = $interface->getURL($test);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода checkWebhook - положительный результат
     */
    public function testCheckWebhook()
    {
        $interface = new PayMasterInterface(
            new Block_Cart(),
            new Page(),
            [],
            [
                '@raw' => pack('CCC', 0xef, 0xbb, 0xbf) . '{
                  "id": "12769",
                  "created":"2021-09-01T08:20:00Z",
                  "testMode": false,
                  "status": "Settled",
                  "merchantId": "96e809e9-8bce-40fd-86cb-d34db39b4668",
                  "amount": {
                    "value": 10.5000,
                    "currency": "RUB"
                  },
                  "invoice": {
                    "description": "test payment",
                    "params": {
                      "BT_USR": "34"
                    }
                  },
                  "paymentData": {
                    "paymentMethod": "BankCard",
                    "paymentInstrumentTitle": "410000XXXXXXX0000"
                  }
                }'
            ],
            [],
            [],
            ['REQUEST_METHOD' => 'POST']
        );

        $result = $interface->checkWebhook();

        $this->assertEquals(['paymentId' => '12769'], $result);
    }


    /**
     * Тест метода checkWebhook - отрицательный результат
     */
    public function testCheckWebhookWithNegative()
    {
        $interface = new PayMasterInterface(new Block_Cart(), new Page(), ['orderId' => 123]);

        $result = $interface->checkWebhook();

        $this->assertNull($result);
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
        $interface = new PayMasterInterface(
            new Block_Cart(['epay_interface_id' => $paymentInterfaceId]),
            null,
            ['orderId' => $orderId] // Передавали в возвратном URL
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
        $interface = new PayMasterInterface(new Block_Cart(), new Page(), [], [], [], ['orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }


    /**
     * Тест метода exec
     */
    public function testExec()
    {
        $interface = $this->getMockBuilder(PayMasterInterface::class)
            ->onlyMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');

        $result = $interface->exec('payments', [], true);

        $this->assertEquals([], $result);
    }


    /**
     * Тест метода exec - случай с POST
     */
    public function testExecWithPost()
    {
        $interface = $this->getMockBuilder(PayMasterInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'login', 'epay_pass1' => 'pass'])])
            ->onlyMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');
        $result = $interface->exec('payments', ['aaa' => 'bbb'], true);

        $this->assertEquals([], $result);
    }


    /**
     * Тест метода exec - случай с PUT
     */
    public function testExecWithPut()
    {
        $interface = $this->getMockBuilder(PayMasterInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'login', 'epay_pass1' => 'pass'])])
            ->onlyMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');
        $result = $interface->exec('payments', ['aaa' => 'bbb'], true, true);

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
        $order->paymentMethod = 'paymentMethodExample';
        $order->paymentSubject = 'paymentSubjectExample';
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new PayMasterInterface(
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


        $this->assertEquals('Заказ #' . $orderId . ' на сайте test', $result['invoice']['description']);
        $this->assertEquals($orderId, $result['invoice']['orderNo']);
        $this->assertEquals(12600, $result['amount']['value']);
        $this->assertEquals('RUB', $result['amount']['currency']);
        $this->assertEquals('http://test/cart/result/?orderId=' . $orderId, $result['protocol']['returnUrl']);
        $this->assertEquals('http://test/cart/result/?orderId=' . $orderId, $result['protocol']['callbackUrl']);
        $this->assertEquals('192.168.0.2', $result['customer']['ip']);
        $this->assertEquals('test@test.org', $result['customer']['email']);
        $this->assertEquals('79990000000', $result['customer']['phone']);
        $this->assertEquals(2, $result['customer']['account']);

        $this->assertEquals('test@test.org', $result['receipt']['client']['email']);
        $this->assertEquals('79990000000', $result['receipt']['client']['phone']);
        $this->assertEquals('1234567890', $result['receipt']['client']['inn']);

        $this->assertCount(3, $result['receipt']['items']);
        $this->assertEquals('Товар 2', $result['receipt']['items'][1]['name']);
        $this->assertEquals(2, $result['receipt']['items'][1]['quantity']);
        $this->assertEquals(1800, $result['receipt']['items'][1]['price']);
        $this->assertEquals('None', $result['receipt']['items'][1]['vatType']);
        $this->assertEquals('paymentSubjectExample', $result['receipt']['items'][1]['paymentSubject']);
        $this->assertEquals('paymentMethodExample', $result['receipt']['items'][1]['paymentMethod']);

        Order::delete($order);
        Form_Field::delete($materialField);
    }


    /**
     * Тест метода getRegisterOrderData - случай с сырым IP
     */
    public function testGetRegisterOrderDataWithRawIp()
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
        $interface = new PayMasterInterface(
            null,
            null,
            [],
            [],
            [],
            [],
            ['HTTP_HOST' => 'test', 'REMOTE_ADDR' => '127.0.0.1']
        );

        $result = $interface->getRegisterOrderData($order, $block, $page);

        $this->assertEquals('127.0.0.1', $result['customer']['ip']);

        Order::delete($order);
    }


    /**
     * Тест метода registerOrderWithData()
     */
    public function testRegisterOrderWithData()
    {
        $interface = $this->getMockBuilder(PayMasterInterface::class)
            ->onlyMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('payments', ['aaa'], false);

        $result = $interface->registerOrderWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseResponseCommonErrors()
     */
    public function testParseResponseCommonErrors()
    {
        $interface = new PayMasterInterface();

        $result = $interface->parseResponseCommonErrors(json_decode('{
          "code": "validation_error",
          "message": "One or many validation errors. See errors list.",
          "errors": [
            "Specify required MerchantId value."
          ]
        }', true));

        $this->assertEquals([
            ['code' => 'validation_error', 'message' => 'Specify required MerchantId value.']
        ], $result);
    }


    /**
     * Тест метода parseInitResponse()
     */
    public function testParseInitResponse()
    {
        $interface = new PayMasterInterface();

        $result = $interface->parseInitResponse(json_decode('{
          "id": "12769",
          "created":"2021-09-01T08:20:00Z",
          "testMode": false,
          "status": "Confirmation",
          "resultCode": "Success",
          "merchantId": "96e809e9-8bce-40fd-86cb-d34db39b4668",
          "amount": {
            "value": 10.5000,
            "currency": "RUB"
          },
          "invoice": {
            "description": "test payment",
            "params": {
              "BT_USR": "34"
            }
          },
          "paymentData": {
            "paymentMethod": "BankCard"
          },
          "confirmation": {
            "type": "ThreeDSv1",
            "paymentUrl": "https://paymaster.ru/acs/pareq",
            "PAReq": "eJxVUtuO0...v4BOrji7g=="
          }
        }', true));

        $this->assertEquals([
            'errors' => [],
            'paymentId' => '12769',
            'paymentURL' => 'https://paymaster.ru/acs/pareq',
        ], $result);
    }

    /**
     * Тест метода getOrderStatusWithData()
     */
    public function testGetOrderStatusWithData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $interface = $this->getMockBuilder(PayMasterInterface::class)
            ->onlyMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('payments/aaaa-bbbb-cccc-dddd', [], false);

        $result = $interface->getOrderStatusWithData($order, new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseOrderStatusResponse()
     */
    public function testParseOrderStatusResponse()
    {
        $interface = new PayMasterInterface();

        $result = $interface->parseOrderStatusResponse(json_decode('{
          "id": "12769",
          "created":"2021-09-01T08:20:00Z",
          "testMode": false,
          "status": "Confirmation",
          "resultCode": "Success",
          "merchantId": "96e809e9-8bce-40fd-86cb-d34db39b4668",
          "amount": {
            "value": 10.5000,
            "currency": "RUB"
          },
          "invoice": {
            "description": "test payment",
            "params": {
              "BT_USR": "34"
            }
          },
          "paymentData": {
            "paymentMethod": "BankCard"
          },
          "confirmation": {
            "type": "ThreeDSv1",
            "paymentUrl": "https://paymaster.ru/acs/pareq",
            "PAReq": "eJxVUtuO0...v4BOrji7g=="
          }
        }', true));

        $this->assertEquals([
            'errors' => [],
            'status' => 'Confirmation',
        ], $result);
    }
}
