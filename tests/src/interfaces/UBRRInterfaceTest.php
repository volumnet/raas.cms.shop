<?php
/**
 * Файл теста интерфейса Уральского банка реконструкции и развития
 */
namespace RAAS\CMS\Shop;

use Exception;
use SimpleXMLElement;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\Form_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест интерфейса Уральского банка реконструкции и развития
 * @covers RAAS\CMS\Shop\UBRRInterface
 */
class UBRRInterfaceTest extends BaseTest
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
     * @return array <pre><code>array<[bool Тестовый режим, string Ожидаемое значение]></code></pre>
     */
    public function getURLDataProvider()
    {
        return [
            [true, 'https://91.208.121.69:7443/Exec'],
            [false, 'https://twpg.ubrr.ru:8443/Exec'],
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
        $interface = new UBRRInterface();

        $result = $interface->getURL($test);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода getBankCertificate()
     */
    public function testGetBankCertificate()
    {
        $filename = Application::i()->baseDir . '/../bank.crt';
        touch($filename);
        $interface = new UBRRInterface();

        $result = $interface->getBankCertificate();

        $this->assertEquals(realpath($filename), $result);
        unlink($filename);
    }


    /**
     * Тест метода getClientCertificate()
     */
    public function testGetClientCertificate()
    {
        $filename = Application::i()->baseDir . '/../user.crt';
        touch($filename);
        $interface = new UBRRInterface();

        $result = $interface->getClientCertificate();

        $this->assertEquals(realpath($filename), $result);
        unlink($filename);
    }


    /**
     * Тест метода getClientKey()
     */
    public function testGetClientKey()
    {
        $filename = Application::i()->baseDir . '/../user.key';
        touch($filename);
        $interface = new UBRRInterface();

        $result = $interface->getClientKey();

        $this->assertEquals(realpath($filename), $result);
        unlink($filename);
    }


    /**
     * Провайдер данных для метода testGetCurrency
     * @return array <pre><code>array<[string Входная валюта, int Ожидаемое значение]></code></pre>
     */
    public function getCurrencyDataProvider()
    {
        return [
            ['USD', 840],
            ['RUB', 643],
        ];
    }


    /**
     * Тест метода getCurrency()
     * @param string $currency Входная валюта
     * @param int $expected Ожидаемое значение
     * @dataProvider getCurrencyDataProvider()
     */
    public function testGetCurrency(string $currency, int $expected)
    {
        $interface = new UBRRInterface();

        $result = $interface->getCurrency($currency);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода sxeToArray()
     */
    public function testSxeToArray()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . '<aaa><bbb>1</bbb><ccc><ddd>2</ddd></ccc></aaa>';
        $sxe = new SimpleXMLElement($xml);
        $interface = new UBRRInterface();

        $result = $interface->sxeToArray($sxe);

        $this->assertEquals([
            'bbb' => '1',
            'ccc' => [
                'ddd' => '2',
            ],
        ], $result);
    }


    /**
     * Тест метода arrayToXML()
     */
    public function testArrayToXML()
    {
        $data = [
            'aaa' => [
                'bbb' => '1',
                'ccc' => [
                    'ddd' => '2',
                ],
            ],
        ];
        $interface = new UBRRInterface();

        $result = $interface->arrayToXML($data);

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . '<aaa><bbb>1</bbb><ccc><ddd>2</ddd></ccc></aaa>',
            $result
        );
    }


    /**
     * Тест метода exec
     */
    public function testExec()
    {
        $interface = $this->getMockBuilder(UBRRInterface::class)
            ->setConstructorArgs([new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass'])])
            ->setMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');

        $result = $interface->exec('CreateOrder', ['aaa' => 'bbb'], true, 'pass');

        $this->assertEquals(7, $result['errorCode']);
        $this->assertStringContainsString('cURL error: Failed to connect', $result['errorMessage']);
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
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' =>
            'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
            'epay_currency' => 'USD',
        ]);
        $page = new Page(25); // Корзина
        $interface = new UBRRInterface(
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

        $this->assertEquals('RU', $result['Language']);
        $this->assertEquals('user', $result['Order']['Merchant']);
        $this->assertEquals(1260000, $result['Order']['Amount']);
        $this->assertEquals(840, $result['Order']['Currency']); // Доллары
        $this->assertEquals('Заказ #' . $orderId . ' на сайте test', $result['Order']['Description']);
        $this->assertEquals('http://test/cart/result/', $result['Order']['ApproveURL']);
        $this->assertEquals('http://test/cart/result/', $result['Order']['CancelURL']);
        $this->assertEquals('http://test/cart/result/', $result['Order']['DeclineURL']);
        $this->assertEquals('test@test.org', $result['Order']['email']);
        $this->assertEquals('79990000000', $result['Order']['phone']);
        $this->assertEquals('Email=test@test.org; Phone=79990000000', $result['Order']['AddParams']['FA-DATA']);

        Order::delete($order);
        Form_Field::delete($materialField);
    }


    /**
     * Тест метода registerOrderWithData()
     */
    public function testRegisterOrderWithData()
    {
        $interface = $this->getMockBuilder(UBRRInterface::class)->setMethods(['exec'])->getMock();
        $interface->expects($this->once())->method('exec')->with('CreateOrder', ['aaa'], false);

        $result = $interface->registerOrderWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseResponseCommonErrors()
     */
    public function testParseResponseCommonErrors()
    {
        $interface = new UBRRInterface();

        $result = $interface->parseResponseCommonErrors([
            'errorCode' => 123,
            'errorMessage' => 'Test error',
        ]);

        $this->assertEquals([
            ['code' => 123, 'message' => 'Test error']
        ], $result);
    }


    /**
     * Тест метода parseInitResponse()
     */
    public function testParseInitResponse()
    {
        $interface = new UBRRInterface();

        $result = $interface->parseInitResponse([
            'Order' => [
                'OrderID' => 'aaaa-bbbb-cccc-dddd',
                'SessionID' => 'session_id123',
                'URL' => 'https://test.org/aaa/bbb',
            ],
        ]);

        $this->assertEquals([
            'errors' => [],
            'paymentId' => 'aaaa-bbbb-cccc-dddd',
            'sessionId' => 'session_id123',
            'paymentURL' => 'https://test.org/aaa/bbb?ORDERID=aaaa-bbbb-cccc-dddd&SESSIONID=session_id123',
        ], $result);

        $this->assertEquals('session_id123', $_SESSION['ubrrSession']);
        $this->assertEquals('session_id123', $interface->session['ubrrSession']);
        unset($_SESSION['ubrrSession']);
    }


    /**
     * Тест метода getOrderStatusData()
     */
    public function testGetOrderStatusData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(['lang' => 'en']);
        $interface = new UBRRInterface(null, null, [], [], [], ['ubrrSession' => 'session_id123']);

        $result = $interface->getOrderStatusData($order, $block, $page);



        $this->assertEquals('EN', $result['Language']);
        $this->assertEquals('user', $result['Order']['Merchant']);
        $this->assertEquals('aaaa-bbbb-cccc-dddd', $result['Order']['OrderID']);
        $this->assertEquals('session_id123', $result['SessionID']);
    }


    /**
     * Тест метода getOrderStatusWithData()
     */
    public function testGetOrderStatusWithData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_pass1' => 'pass']);
        $interface = $this->getMockBuilder(UBRRInterface::class)
            ->setMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('GetOrderStatus', ['aaa'], false, 'pass');

        $result = $interface->getOrderStatusWithData($order, $block, new Page(), ['aaa']);
    }


    /**
     * Тест метода parseOrderStatusResponse()
     */
    public function testParseOrderStatusResponse()
    {
        $interface = new UBRRInterface();

        $result = $interface->parseOrderStatusResponse(['Order' => ['OrderStatus' => 'APPROVED']]);

        $this->assertEquals([
            'errors' => [],
            'status' => 'APPROVED',
        ], $result);
    }


    /**
     * Тест метода result()
     */
    public function testResult()
    {
        $order = new Order(['id' => 123]);
        $block = new Block_Cart(['id' => 111, 'epay_test' => '1']);
        $page = new Page();
        $interface = $this->getMockBuilder(UBRRInterface::class)
            ->setConstructorArgs([null, null, [], [], [], ['ubrrSession' => 'session_id123']])
            ->setMethods(['doLog', 'getOrderIsPaid', 'applyPaidStatus'])
            ->getMock();
        $interface->expects($this->once())->method('doLog')->with('UBRR Session ID: session_id123');
        $interface->method('getOrderIsPaid')->willReturn(true);
        $interface->expects($this->once())->method('applyPaidStatus')->with($order);

        $result = $interface->result($order, $block, $page);

        $this->assertEquals(true, $result['success'][111]);

        Order::delete($order);
    }


    /**
     * Тест метода result()
     */
    public function testResultWithoutSession()
    {
        $order = new Order(['id' => 123]);
        $block = new Block_Cart(['id' => 111]);
        $page = new Page();
        $interface = new UBRRInterface();

        $result = $interface->result($order, $block, $page);

        $this->assertStringContainsStringIgnoringCase('некорректная подпись', $result['localError']['order']);

        Order::delete($order);
    }
}
