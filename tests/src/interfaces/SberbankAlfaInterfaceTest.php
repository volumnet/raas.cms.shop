<?php
/**
 * Файл теста объединенного интерфейса Сбербанка и Альфа-Банка
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
 * Тест объединенного интерфейса Сбербанка и Альфа-Банка
 */
#[CoversClass(SberbankAlfaInterface::class)]
class SberbankInterfaceTest extends BaseTest
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
     * Тест метода checkWebhook - положительный результат
     */
    public function testCheckWebhook()
    {
        $interface = new SberbankInterface(
            new Block_Cart(),
            new Page(),
            ['mdOrder' => 'aaaa-bbbb-cccc-dddd', 'orderNumber' => 123, 'operation' => 'test']
        );

        $result = $interface->checkWebhook();

        $this->assertEquals(['orderId' => 123, 'paymentId' => 'aaaa-bbbb-cccc-dddd'], $result);
    }


    /**
     * Тест метода checkWebhook - отрицательный результат
     */
    public function testCheckWebhookWithNegative()
    {
        $interface = new SberbankInterface(new Block_Cart(), new Page(), ['orderId' => 123]);

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
        $interface = new SberbankInterface(
            new Block_Cart(['epay_interface_id' => $paymentInterfaceId]),
            null,
            ['orderId' => 'aaaa-bbbb-cccc-dddd']
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
        $interface = new SberbankInterface(new Block_Cart(), new Page(), [], [], [], ['orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }


    /**
     * Тест метода exec
     */
    public function testExec()
    {
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->onlyMethods(['doLog'])
            ->getMock();
        $interface->expects($this->once())->method('doLog');
        $result = $interface->exec('getOrderStatusExtended', [], true);

        $this->assertEquals(5, $result['errorCode']);
        $this->assertNotEmpty($result['errorMessage']);
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
        $order->tax_type = 'taxTypeExample';
        $order->tax_system = 'taxSystemExample';
        $order->sberbank_payment_method = 'paymentMethodExample';
        $order->sberbank_payment_object = 'paymentObjectExample';
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new SberbankInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getRegisterOrderData($order, $block, $page);

        $this->assertEquals('user', $result['userName']);
        $this->assertEquals('pass', $result['password']);
        $this->assertEquals($orderId, $result['orderNumber']);
        $this->assertEquals(1260000, $result['amount']);
        $this->assertEquals('http://test/cart/result/', $result['returnUrl']);
        $this->assertEquals('http://test/cart/result/', $result['failUrl']);
        $this->assertEquals('http://test/cart/result/', $result['dynamicCallbackUrl']);
        $this->assertEquals('Заказ #' . $orderId . ' на сайте test', $result['description']);
        $this->assertEquals('taxSystemExample', $result['taxSystem']);

        $jsonParams = json_decode($result['jsonParams'], true);

        $this->assertEquals('+7 (999) 000-00-00', $jsonParams['phone']);
        $this->assertEquals('test@test.org', $jsonParams['email']);
        $this->assertEquals('Товар 1', $jsonParams['associtem']);

        $orderBundle = json_decode($result['orderBundle'], true);

        $this->assertEquals('79990000000', $orderBundle['customerDetails']['phone']);
        $this->assertEquals('test@test.org', $orderBundle['customerDetails']['email']);
        $this->assertEquals('Тестовый адрес', $orderBundle['customerDetails']['deliveryInfo']['postAddress']);
        $this->assertEquals('Город', $orderBundle['customerDetails']['deliveryInfo']['city']);
        $this->assertEquals('EU', $orderBundle['customerDetails']['deliveryInfo']['country']);

        $this->assertCount(3, $orderBundle['cartItems']['items']);
        $this->assertEquals(2, $orderBundle['cartItems']['items'][1]['positionId']);
        $this->assertEquals('Товар 2', $orderBundle['cartItems']['items'][1]['name']);
        $this->assertEquals(['value' => 2, 'measure' => 'шт.'], $orderBundle['cartItems']['items'][1]['quantity']);
        $this->assertEquals(180000, $orderBundle['cartItems']['items'][1]['itemPrice']); // С учетом скидки
        $this->assertEquals(360000, $orderBundle['cartItems']['items'][1]['itemAmount']); // С учетом скидки
        $this->assertEquals(11, $orderBundle['cartItems']['items'][1]['itemCode']);
        $this->assertEquals('taxTypeExample', $orderBundle['cartItems']['items'][1]['tax']['taxType']);
        $this->assertEquals('paymentMethod', $orderBundle['cartItems']['items'][1]['itemAttributes']['attributes'][0]['name']);
        $this->assertEquals('paymentMethodExample', $orderBundle['cartItems']['items'][1]['itemAttributes']['attributes'][0]['value']);
        $this->assertEquals('paymentObject', $orderBundle['cartItems']['items'][1]['itemAttributes']['attributes'][1]['name']);
        $this->assertEquals('paymentObjectExample', $orderBundle['cartItems']['items'][1]['itemAttributes']['attributes'][1]['value']);

        Order::delete($order);
        Form_Field::delete($materialField);
    }


    /**
     * Тест метода getRegisterOrderData - случай с дефолтными данными
     */
    public function testGetRegisterOrderDataWithDefaults()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $order->address = 'Тестовый адрес';
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page(25); // Корзина
        $interface = new SberbankInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getRegisterOrderData($order, $block, $page);

        $this->assertEquals(SberbankInterface::TAX_SYSTEM_SIMPLE, $result['taxSystem']);

        $orderBundle = json_decode($result['orderBundle'], true);

        $this->assertEquals('-', $orderBundle['customerDetails']['deliveryInfo']['city']);
        $this->assertEquals('RU', $orderBundle['customerDetails']['deliveryInfo']['country']);
        $this->assertEquals(SberbankInterface::TAX_TYPE_NO_VAT, $orderBundle['cartItems']['items'][0]['tax']['taxType']);
        $this->assertEquals('paymentMethod', $orderBundle['cartItems']['items'][0]['itemAttributes']['attributes'][0]['name']);
        $this->assertEquals(SberbankInterface::PAYMENT_METHOD_PREPAY, $orderBundle['cartItems']['items'][0]['itemAttributes']['attributes'][0]['value']);
        $this->assertEquals('paymentObject', $orderBundle['cartItems']['items'][0]['itemAttributes']['attributes'][1]['name']);
        $this->assertEquals(SberbankInterface::PAYMENT_OBJECT_PRODUCT, $orderBundle['cartItems']['items'][0]['itemAttributes']['attributes'][1]['value']);

        Order::delete($order);
    }


    /**
     * Тест метода registerOrderWithData()
     */
    public function testRegisterOrderWithData()
    {
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->onlyMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('register', ['aaa'], false);

        $result = $interface->registerOrderWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseResponseCommonErrors()
     */
    public function testParseResponseCommonErrors()
    {
        $interface = new SberbankInterface();

        $result = $interface->parseResponseCommonErrors([
            'errorCode' => '5',
            'errorMessage' => '[userName] or [password] or [token] is empty',
        ]);

        $this->assertEquals([['code' => '5', 'message' => '[userName] or [password] or [token] is empty']], $result);
    }


    /**
     * Тест метода parseInitResponse()
     */
    public function testParseInitResponse()
    {
        $interface = new SberbankInterface();

        $result = $interface->parseInitResponse([
            'errorCode' => '5',
            'errorMessage' => '[userName] or [password] or [token] is empty',
            'orderId' => '70906e55-7114-41d6-8332-4609dc6590f4',
            'formUrl' => 'https://3dsec.sberbank.ru/payment/merchants/test/payment_ru.html?mdOrder=70906e55-7114-41d6-8332-4609dc6590f4'
        ]);

        $this->assertEquals([
            'errors' => [['code' => '5', 'message' => '[userName] or [password] or [token] is empty']],
            'paymentId' => '70906e55-7114-41d6-8332-4609dc6590f4',
            'paymentURL' => 'https://3dsec.sberbank.ru/payment/merchants/test/payment_ru.html?mdOrder=70906e55-7114-41d6-8332-4609dc6590f4',
        ], $result);
    }


    /**
     * Тест метода getOrderStatusData()
     */
    public function testGetOrderStatusData()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = new SberbankInterface();

        $result = $interface->getOrderStatusData($order, $block, $page);

        $this->assertEquals('user', $result['userName']);
        $this->assertEquals('pass', $result['password']);
        $this->assertEquals('aaaa-bbbb-cccc-dddd', $result['orderId']);
    }


    /**
     * Тест метода getOrderStatusWithData()
     */
    public function testGetOrderStatusWithData()
    {
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->onlyMethods(['exec'])
            ->getMock();
        $interface->expects($this->once())->method('exec')->with('getOrderStatusExtended', ['aaa'], false);

        $result = $interface->getOrderStatusWithData(new Order(), new Block_Cart(), new Page(), ['aaa']);
    }


    /**
     * Тест метода parseOrderStatusResponse()
     */
    public function testParseOrderStatusResponse()
    {
        $interface = new SberbankInterface();

        $result = $interface->parseOrderStatusResponse([
            'errorCode' => '0',
            'errorMessage' => 'Успешно',
            'orderNumber' => '0784sse49d0s134567890',
            'orderStatus' => 6,
            'actionCode' => -2007,
            'actionCodeDescription' => 'Время сессии истекло',
            'amount' => 33000,
            'currency' => '643',
            'date' => 1383819429914,
            'orderDescription' => ' ',
            'merchantOrderParams' => [['name' => 'email', 'value' => 'yap']],
            'attributes' => [['name' => 'mdOrder', 'value' => 'b9054496-c65a-4975-9418-1051d101f1b9']],
            'cardAuthInfo' => [
                'expiration' => '201912',
                'cardholderName' => 'Ivan',
                'secureAuthInfo' => [
                    'eci' => 6,
                    'threeDSInfo' => ['xid' => 'MDAwMDAwMDEzODM4MTk0MzAzMjM=']
                ],
                'pan' => '411111**1111'
            ],
            'terminalId' => '333333',
        ]);

        $this->assertEquals([
            'errors' => [],
            'status' => 6,
        ], $result);
    }
}
