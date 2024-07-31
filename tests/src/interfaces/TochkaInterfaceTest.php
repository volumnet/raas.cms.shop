<?php
/**
 * Файл теста интерфейса банка Точка
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
 * Тест интерфейса банка Точка
 * @covers RAAS\CMS\Shop\TochkaInterface
 */
class TochkaInterfaceTest extends BaseTest
{
    public static $tables = [
        'cms_data',
        'cms_fields',
        'cms_forms',
        'cms_materials',
        'cms_pages',
        'cms_shop_blocks_cart',
        'cms_shop_cart_types',
        'cms_shop_orders',
        'cms_shop_orders_goods',
        'cms_shop_orders_history',
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
     * @return array <pre><code>array<[
     *     bool Тестовый режим,
     *     string Ожидаемое значение
     * ]></code></pre>
     */
    public function getURLDataProvider()
    {
        return [
            [true, 'https://enter.tochka.com/sandbox/v2/acquiring/1.0/'],
            [false, 'https://enter.tochka.com/uapi/acquiring/1.0/'],
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
        $block = new Block_Cart();
        $interface = new TochkaInterface($block);

        $result = $interface->getURL($test);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода checkWebhook - положительный результат
     */
    public function testCheckWebhook()
    {
        $interface = new TochkaInterface(
            new Block_Cart(),
            new Page(),
            [],
            [
                '@raw' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJjdXN0b21lckNvZGUiOiAiMzAwMTIzMTIzIiwgImFtb3VudCI6ICIwLjMzIiwgInBheW1lbnRUeXBlIjogImNhcmQiLCAib3BlcmF0aW9uSWQiOiAiYmVlYWM4YTQtNjA0Ny0zZjM4LTg5MjItYTY2NGU2YjVjNDNiIiwgInB1cnBvc2UiOiAiXHUwNDFlXHUwNDNmXHUwNDNiXHUwNDMwXHUwNDQyXHUwNDMwIFx1MDQzZlx1MDQzZSBcdTA0NDFcdTA0NDdcdTA0MzVcdTA0NDJcdTA0NDMgXHUyMTE2IDEgXHUwNDNlXHUwNDQyIDAxLjAxLjIwMjEuIFx1MDQxMVx1MDQzNVx1MDQzNyBcdTA0MWRcdTA0MTRcdTA0MjEiLCAid2ViaG9va1R5cGUiOiAiYWNxdWlyaW5nSW50ZXJuZXRQYXltZW50In0.FJfaan8N1OWxLRipfsMuxmYyE69mA7yhp3uP2ycImzmT3UpSXtgdedGKP8RoVDq-r4nOiiXLMCYO7bsH0L8660wZvnCMuqZzmE_K3vbczTBdFiWhp7ExFTNX-rALuYemmdjIk4iSc7nDU4DwWvTaQGh8_yJlm9MOqa9RSFXnHpfKElNRea0rNonk02KqGdPz_zRVF7MXPjr970tEATibR52hrZCFWYZxA6FiggFsrqOykGAPX6uZyR7OD_TP0oZM5v3KxNFcnSsIxb_G8UJpdGk2GvDWDx9Px7tjkROu_ja47-N8StlY54DxDmzpaqfl35mYnLv8awGmfaZXOWYZySADRG2MDAi-iii4TPKdUtPeZga-mo0T9Vv_Jqeg9O-glFufLjCvm4dEPl36ccdpBTcvpfLthQEwa60Eb_fiyrYhIVBmjucxJZOgiATuEiXbMXPe9Z7wXYlS6tilEzBPpjy8glUcH_WDMCkK5Lylu7SCERr1Uc0PFF8M93TCTnJB',
            ],
            [],
            [],
            ['REQUEST_METHOD' => 'POST']
        );

        $result = $interface->checkWebhook();

        $this->assertEquals(['paymentId' => 'beeac8a4-6047-3f38-8922-a664e6b5c43b'], $result);
    }


    /**
     * Тест метода checkWebhook - отрицательный результат
     */
    public function testCheckWebhookWithNegative()
    {
        $interface = new TochkaInterface(new Block_Cart(), new Page(), [], ['@raw' => 'aaa']);

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
        $interface = new TochkaInterface(
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
        $interface = new TochkaInterface(new Block_Cart(), new Page(), [], [], [], ['orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }



    /**
     * Тест метода exec
     */
    public function testExec()
    {
        $interface = $this->getMockBuilder(TochkaInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass'])])
            ->setMethods(['doLog'])
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
        $interface = $this->getMockBuilder(TochkaInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'login', 'epay_pass1' => 'pass'])])
            ->setMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog'); // Один на получение токена, один - на собственно запрос
        $result = $interface->exec('payments', ['aaa' => 'bbb'], true);

        $this->assertEquals([], $result);
    }



    /**
     * Тест метода exec - случай с PUT
     */
    public function testExecWithPut()
    {
        $interface = $this->getMockBuilder(TochkaInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'login', 'epay_pass1' => 'pass'])])
            ->setMethods(['doLog'])
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
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new TochkaInterface(
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

        $this->assertEquals('user', $result['Data']['customerCode']);
        $this->assertEquals(12600, $result['Data']['amount']);
        $this->assertEquals('Заказ #' . $orderId . ' на сайте test', $result['Data']['purpose']);
        $this->assertEquals('http://test/cart/result/?orderId=' . $orderId, $result['Data']['redirectUrl']);
        $this->assertEquals('http://test/cart/result/?orderId=' . $orderId, $result['Data']['failRedirectUrl']);
        $this->assertEquals(['sbp', 'card'], $result['Data']['paymentMode']);
        $this->assertEquals('Тестовый Пользователь', $result['Data']['Client']['name']);
        $this->assertEquals('test@test.org', $result['Data']['Client']['email']);
        $this->assertEquals('79990000000', $result['Data']['Client']['phone']);

        $this->assertCount(3, $result['Data']['Items']);
        $this->assertEquals('Товар 2', $result['Data']['Items'][1]['name']);
        $this->assertEquals(1800, $result['Data']['Items'][1]['amount']);
        $this->assertEquals(2, $result['Data']['Items'][1]['quantity']);

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
        $interface = new TochkaInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getRegisterOrderData($order, $block, $page);
        $this->assertEquals('Тестовый пользователь 123', $result['Data']['Client']['name']);

        Order::delete($order);
    }


    /**
     * Тест метода registerOrderWithData()
     */
    public function testRegisterOrderWithData()
    {
        $interface = $this->getMockBuilder(TochkaInterface::class)->setMethods(['exec'])->getMock();
        $interface->expects($this->once())->method('exec')->with('payments_with_receipt', ['aaa'], false);

        $result = $interface->registerOrderWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseResponseCommonErrors()
     */
    public function testParseResponseCommonErrors()
    {
        $interface = new TochkaInterface();

        $result = $interface->parseResponseCommonErrors(json_decode('{
            "code": "400",
            "id": "c397b21a-d998-4c4d-9471-e60eaf816b87",
            "message": "Что-то пошло не так",
            "Errors": [
                {
                    "errorCode": "HTTPBadRequest",
                    "message": "Something going wrong",
                    "url": "\"http://enter.tochka.com/open-banking/docs\""
                }
            ]
        }', true));

        $this->assertEquals([
            ['code' => 'HTTPBadRequest', 'message' => 'Something going wrong'],
        ], $result);
    }


    /**
     * Тест метода parseInitResponse()
     */
    public function testParseInitResponse()
    {
        $interface = new TochkaInterface();

        $result = $interface->parseInitResponse(json_decode('{
          "Data": {
            "purpose": "Перевод за оказанные услуги",
            "status": "CREATED",
            "amount": "1234.00",
            "operationId": "48232c9a-ce82-1593-3cb6-5c85a1ffef8f",
            "paymentLink": "https://merch.bank24.int/order/?uuid=16ea4c54-bf1d-4e6a-a1ef-53ad55666e43",
            "consumerId": "fedac807-078d-45ac-a43b-5c01c57edbf8",
            "customerCode": "300000092",
            "redirectUrl": "https://example.com",
            "failRedirectUrl": "https://example.com/fail",
            "paymentMode": [
              "sbp",
              "card"
            ],
            "merchantId": "200000000001056",
            "taxSystemCode": "osn",
            "Client": {
              "name": "Иванов Иван Иванович",
              "email": "ivanov@mail.com",
              "phone": "+7999999999"
            },
            "Items": [
              {
                "vatType": "none",
                "name": "string",
                "amount": "1234.00",
                "quantity": 0,
                "paymentMethod": "full_payment",
                "paymentObject": "service",
                "measure": "шт."
              }
            ]
          }
        }', true));

        $this->assertEquals([
            'errors' => [],
            'paymentId' => '48232c9a-ce82-1593-3cb6-5c85a1ffef8f',
            'paymentURL' => 'https://merch.bank24.int/order/?uuid=16ea4c54-bf1d-4e6a-a1ef-53ad55666e43',
        ], $result);
    }

    /**
     * Тест метода getOrderStatusWithData()
     */
    public function testGetOrderStatusWithData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $interface = $this->getMockBuilder(TochkaInterface::class)
            ->setMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with(
            'payments/aaaa-bbbb-cccc-dddd',
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
        $interface = new TochkaInterface();

        $result = $interface->parseOrderStatusResponse(json_decode('{
          "Data": {
            "Operation": [
              {
                "customerCode": "300000092",
                "taxSystemCode": "osn",
                "paymentType": "card",
                "paymentId": "A22031016256670100000533E625FCB3",
                "transactionId": "48232c9a-ce82-1593-3cb6-5c85a1ffef8f",
                "createdAt": "2022-10-18T08:28:59+00:00",
                "paymentMode": [
                  "sbp",
                  "card"
                ],
                "redirectUrl": "https://example.com",
                "failRedirectUrl": "https://example.com/fail",
                "Client": {
                  "name": "Иванов Иван Иванович",
                  "email": "ivanov@mail.com",
                  "phone": "+7999999999"
                },
                "Items": [
                  {
                    "vatType": "none",
                    "name": "string",
                    "amount": "1234.00",
                    "quantity": 0,
                    "paymentMethod": "full_payment",
                    "paymentObject": "service",
                    "measure": "шт."
                  }
                ],
                "purpose": "Перевод за оказанные услуги",
                "amount": "1234.00",
                "status": "APPROVED",
                "operationId": "48232c9a-ce82-1593-3cb6-5c85a1ffef8f",
                "paymentLink": "https://merch.bank24.int/order/?uuid=16ea4c54-bf1d-4e6a-a1ef-53ad55666e43",
                "merchantId": "200000000001056",
                "consumerId": "fedac807-078d-45ac-a43b-5c01c57edbf8"
              }
            ]
          },
          "Links": {
            "self": "http://example.com"
          },
          "Meta": {
            "totalPages": 0
          }
        }', true));

        $this->assertEquals([
            'errors' => [],
            'status' => 'APPROVED',
        ], $result);
    }
}
