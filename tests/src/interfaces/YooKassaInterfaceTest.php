<?php
/**
 * Файл теста интерфейса ЮКаssа
 */
namespace RAAS\CMS\Shop;

use Exception;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\Form_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест интерфейса ЮКаssа
 * @covers RAAS\CMS\Shop\YooKassaInterface
 */
class YooKassaInterfaceTest extends BaseTest
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
     * Провайдер данных для метода testGetURL
     * @return array <pre><code>array<[bool Тестовый режим, string Ожидаемое значение]></code></pre>
     */
    public function getURLDataProvider()
    {
        return [
            [true, 'https://api.yookassa.ru/v3/'],
            [false, 'https://api.yookassa.ru/v3/'],
        ];
    }


    /**
     * Тест метода getURL
     * @param bool $test Тестовый режим
     * @param string $expected Ожидаемое значение
     * @dataProvider getURLDataProvider
     */
    public function testGetURL(bool $test, string $expected)
    {
        $interface = new YooKassaInterface();

        $result = $interface->getURL($test);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода checkWebhook - положительный результат
     */
    public function testCheckWebhook()
    {
        $interface = new YooKassaInterface(
            new Block_Cart(),
            new Page(),
            [],
            ['@raw' => pack('CCC', 0xef, 0xbb, 0xbf) . '{
                  "type": "notification",
                  "event": "payment.waiting_for_capture",
                  "object": {
                    "id": "22d6d597-000f-5000-9000-145f6df21d6f",
                    "status": "waiting_for_capture",
                    "paid": true,
                    "amount": {
                      "value": "2.00",
                      "currency": "RUB"
                    },
                    "authorization_details": {
                      "rrn": "10000000000",
                      "auth_code": "000000",
                      "three_d_secure": {
                        "applied": true
                      }
                    },
                    "created_at": "2018-07-10T14:27:54.691Z",
                    "description": "Заказ №72",
                    "expires_at": "2018-07-17T14:28:32.484Z",
                    "metadata": {},
                    "payment_method": {
                      "type": "bank_card",
                      "id": "22d6d597-000f-5000-9000-145f6df21d6f",
                      "saved": false,
                      "card": {
                        "first6": "555555",
                        "last4": "4444",
                        "expiry_month": "07",
                        "expiry_year": "2021",
                        "card_type": "MasterCard",
                      "issuer_country": "RU",
                      "issuer_name": "Sberbank"
                      },
                      "title": "Bank card *4444"
                    },
                    "refundable": false,
                    "test": false
                  }
                }
            '],
            [],
            [],
            ['REQUEST_METHOD' => 'POST']
        );

        $result = $interface->checkWebhook();

        $this->assertEquals(['paymentId' => '22d6d597-000f-5000-9000-145f6df21d6f'], $result);
    }


    /**
     * Тест метода checkWebhook - отрицательный результат
     */
    public function testCheckWebhookWithNegative()
    {
        $interface = new YooKassaInterface(new Block_Cart(), new Page(), ['orderId' => 123]);

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
        $interface = new YooKassaInterface(
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
        $interface = new YooKassaInterface(new Block_Cart(), new Page(), [], [], [], ['orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }


    /**
     * Тест метода exec
     */
    public function testExec()
    {
        $interface = $this->getMockBuilder(YooKassaInterface::class)
            ->setMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');
        $result = $interface->exec('payments', [], true);

        $this->assertEquals('error', $result['type']);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals('invalid_credentials', $result['code']);
        $this->assertEquals('Authentication by given credentials failed', $result['description']);
        $this->assertEquals('Authorization', $result['parameter']);
    }


    /**
     * Тест метода exec - случай с POST
     */
    public function testExecWithPost()
    {
        $interface = $this->getMockBuilder(YooKassaInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'login', 'epay_pass1' => 'pass'])])
            ->setMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');
        $result = $interface->exec('payments', ['aaa' => 'bbb'], true);

        $this->assertEquals('error', $result['type']);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals('invalid_request', $result['code']);
        $this->assertEquals('Invalid request parameter', $result['description']);
        $this->assertEquals('amount', $result['parameter']);
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
        // $order->tax_type = 'taxTypeExample';
        // $order->tax_system = 'taxSystemExample';
        $order->inn = '1234567890';
        $order->paymentMethod = 'paymentMethodExample';
        $order->paymentSubject = 'paymentSubjectExample';
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new YooKassaInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getRegisterOrderData($order, $block, $page);

        $this->assertEquals(12600, $result['amount']['value']);
        $this->assertEquals('RUB', $result['amount']['currency']);
        $this->assertTrue($result['capture']);
        $this->assertEquals('redirect', $result['confirmation']['type']);
        $this->assertEquals('http://test/cart/result/?orderId=' . $orderId, $result['confirmation']['return_url']);
        $this->assertEquals('Заказ #' . $orderId . ' на сайте test', $result['description']);
        $this->assertEquals($orderId, $result['metadata']['order_id']);

        $this->assertEquals('Тестовый Пользователь', $result['receipt']['customer']['full_name']);
        $this->assertEquals('1234567890', $result['receipt']['customer']['inn']);
        $this->assertEquals('test@test.org', $result['receipt']['customer']['email']);
        $this->assertEquals('79990000000', $result['receipt']['customer']['phone']);

        $this->assertCount(3, $result['receipt']['items']);
        $this->assertEquals('Товар 2', $result['receipt']['items'][1]['description']);
        $this->assertEquals(['value' => 1800, 'currency' => 'RUB'], $result['receipt']['items'][1]['amount']);
        $this->assertEquals(1, $result['receipt']['items'][1]['vat_code']);
        $this->assertEquals(2, $result['receipt']['items'][1]['quantity']);
        $this->assertEquals('paymentSubjectExample', $result['receipt']['items'][1]['payment_subject']);
        $this->assertEquals('paymentMethodExample', $result['receipt']['items'][1]['payment_mode']);

        $this->assertEquals(2, $result['receipt']['tax_system_code']);


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
        $interface = new YooKassaInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getRegisterOrderData($order, $block, $page);

        $this->assertEquals('Тестовый пользователь 123', $result['receipt']['customer']['full_name']);

        Order::delete($order);
    }


    /**
     * Тест метода registerOrderWithData()
     */
    public function testRegisterOrderWithData()
    {
        $interface = $this->getMockBuilder(YooKassaInterface::class)
            ->setMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('payments', ['aaa'], false);

        $result = $interface->registerOrderWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseResponseCommonErrors()
     */
    public function testParseResponseCommonErrors()
    {
        $interface = new YooKassaInterface();

        $result = $interface->parseResponseCommonErrors([
            'type' => 'error',
            'id' => '1e3ed1fb-0faf-4dab-a3aa-55ea0fc98e93',
            'code' => 'invalid_credentials',
            'description' => 'Authentication by given credentials failed',
            'parameter' => 'Authorization',
        ]);

        $this->assertEquals([
            ['code' => 'invalid_credentials', 'message' => 'Authentication by given credentials failed']
        ], $result);
    }


    /**
     * Тест метода parseInitResponse()
     */
    public function testParseInitResponse()
    {
        $interface = new YooKassaInterface();

        $result = $interface->parseInitResponse(json_decode('{
          "id": "2419a771-000f-5000-9000-1edaf29243f2",
          "status": "pending",
          "paid": false,
          "amount": {
            "value": "100.00",
            "currency": "RUB"
          },
          "confirmation": {
            "type": "redirect",
            "confirmation_url": "https://yoomoney.ru/api-pages/v2/payment-confirm/epl?orderId=2419a771-000f-5000-9000-1edaf29243f2"
          },
          "created_at": "2019-03-12T11:10:41.802Z",
          "description": "Заказ №37",
          "metadata": {
            "order_id": "37"
          },
          "recipient": {
            "account_id": "100500",
            "gateway_id": "100700"
          },
          "refundable": false,
          "test": false
        }', true));

        $this->assertEquals([
            'errors' => [],
            'paymentId' => '2419a771-000f-5000-9000-1edaf29243f2',
            'paymentURL' => 'https://yoomoney.ru/api-pages/v2/payment-confirm/epl?orderId=2419a771-000f-5000-9000-1edaf29243f2',
        ], $result);
    }

    /**
     * Тест метода getOrderStatusWithData()
     */
    public function testGetOrderStatusWithData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $interface = $this->getMockBuilder(YooKassaInterface::class)
            ->setMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('payments/aaaa-bbbb-cccc-dddd', [], false);

        $result = $interface->getOrderStatusWithData($order, new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseOrderStatusResponse()
     */
    public function testParseOrderStatusResponse()
    {
        $interface = new YooKassaInterface();

        $result = $interface->parseOrderStatusResponse(json_decode('{
          "id": "22e12f66-000f-5000-8000-18db351245c7",
          "status": "waiting_for_capture",
          "paid": true,
          "amount": {
            "value": "2.00",
            "currency": "RUB"
          },
          "created_at": "2018-07-18T10:51:18.139Z",
          "description": "Заказ №72",
          "expires_at": "2018-07-25T10:52:00.233Z",
          "metadata": {},
          "payment_method": {
            "type": "bank_card",
            "id": "22e12f66-000f-5000-8000-18db351245c7",
            "saved": false,
            "card": {
              "first6": "555555",
              "last4": "4444",
              "expiry_month": "07",
              "expiry_year": "2022",
              "card_type": "MIR",
              "card_product": {
                "code": "MCP",
                "name": "MIR Privilege"
              },
              "issuer_country": "RU",
              "issuer_name": "Sberbank"
            },
            "title": "Bank card *4444"
          },
          "recipient": {
            "account_id": "100500",
            "gateway_id": "100700"
          },
          "refundable": false,
          "test": false
        }', true));

        $this->assertEquals([
            'errors' => [],
            'status' => 'waiting_for_capture',
        ], $result);
    }
}
