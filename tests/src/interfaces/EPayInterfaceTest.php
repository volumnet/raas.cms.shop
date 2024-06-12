<?php
/**
 * Файл теста интерфейса оплаты
 */
namespace RAAS\CMS\Shop;

use Exception;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест интерфейса оплаты
 * @covers RAAS\CMS\Shop\EPayInterface
 */
class EPayInterfaceTest extends BaseTest
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
     * Тест метода getName()
     */
    public function testGetBankName()
    {
        $interface = new SberbankInterface();

        $result = $interface->getBankName();

        $this->assertEquals('Сбербанк', $result);
    }


    /**
     * Тест метода getPaymentInterface()
     */
    public function testGetPaymentInterface()
    {
        $block = new Block_Cart(['cats' => [1], 'epay_interface_classname' => SberbankInterface::class]);
        $interface = new SberbankInterface();

        $result = $interface->getPaymentInterface($block);

        $this->assertEquals(SberbankInterface::class, $result);
    }


    /**
     * Тест метода getPaymentInterface() - случай со сниппетом
     */
    public function testGetPaymentInterfaceWithSnippet()
    {
        $paymentInterface = new Snippet(['urn' => 'testpayment']);
        $paymentInterface->commit();
        $block = new Block_Cart(['cats' => [1], 'epay_interface_id' => $paymentInterface->id]);
        $interface = new SberbankInterface();

        $result = $interface->getPaymentInterface($block);

        $this->assertInstanceOf(Snippet::class, $result);
        $this->assertEquals($paymentInterface->id, $result->id);

        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода getPaymentInterface() - случай без указания платежного интерфейса
     */
    public function testGetPaymentInterfaceWithNull()
    {
        $block = new Block_Cart(['cats' => [1]]);
        $interface = new SberbankInterface();

        $result = $interface->getPaymentInterface($block);

        $this->assertNull($result);
    }


    /**
     * Тест метода getPaymentInterface() - случай без указания блока
     */
    public function testGetPaymentInterfaceWithNoBlock()
    {
        $interface = new SberbankInterface();

        $result = $interface->getPaymentInterface();

        $this->assertNull($result);
    }


    /**
     * Тест метода getPositiveItems()
     */
    public function testGetPositiveItems()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['name' => 'Скидка', 'realprice' => -1000, 'amount' => 1],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $interface = new SberbankInterface();

        $result = $interface->getPositiveItems($order);
        // 1-й товар со скидкой в коп. 92857,142857142857142857142857143
        // 2-й товар со скидкой в коп. 185714,28571428571428571428571429 * 2
        // 3-й товар со скидкой в коп. 278571,42857142857142857142857143 * 3
        // Сумма округленных 92857 + (185714 * 2) + (278571 * 3) = 92857 + 371428 + 835713 = 1299998
        // Должно получиться 1300000
        // После распределения получаем
        // 1-й товар со скидкой в коп. 92857
        // 2-й товар со скидкой в коп. 185715 (добавилась 1 коп.)
        // 3-й товар со скидкой в коп. 278571
        // Сумма 92857 + (185715 * 2) + (278571 * 3) = 92857 + 371430 + 835713 = 1300000

        $this->assertEquals(1300000, $result['sum']);
        $this->assertCount(3, $result['items']);
        $this->assertEquals(10, $result['items'][0]->id);
        $this->assertEquals('Товар 1', $result['items'][0]->name);
        $this->assertEquals(1, $result['items'][0]->amount);
        $this->assertEquals(92857, $result['items'][0]->epayPriceKop);
        $this->assertEquals(11, $result['items'][1]->id);
        $this->assertEquals('Товар 2', $result['items'][1]->name);
        $this->assertEquals(2, $result['items'][1]->amount);
        $this->assertEquals(185715, $result['items'][1]->epayPriceKop);
        $this->assertEquals(12, $result['items'][2]->id);
        $this->assertEquals('Товар 3', $result['items'][2]->name);
        $this->assertEquals(3, $result['items'][2]->amount);
        $this->assertEquals(278571, $result['items'][2]->epayPriceKop);

        Order::delete($order);
    }


    /**
     * Тест метода findOrder()
     */
    public function testFindOrder()
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
     * Тест метода findOrder() - случай с вебхуком, когда возвращаются paymentId и orderId
     */
    public function testFindOrderWithWebhookPaymentIdAndOrderId()
    {
        $paymentInterface = new Snippet(['urn' => 'testpayment']);
        $paymentInterface->commit();

        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd', 'payment_interface_id' => $paymentInterface->id]);
        $order->commit();
        $orderId = $order->id;

        $block = new Block_Cart(['cats' => [1], 'epay_interface_id' => $paymentInterface->id]); //

        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['checkWebhook'])
            ->setConstructorArgs([$block])
            ->getMock();
        $interface->expects($this->once())
            ->method('checkWebhook')
            ->willReturn(['paymentId' => 'aaaa-bbbb-cccc-dddd', 'orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода findOrder() - случай с вебхуком, когда возвращаются paymentId и orderId,
     * при указании класса платежного интерфейса
     */
    public function testFindOrderWithWebhookPaymentIdAndOrderIdAndInterfaceClassname()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd', 'payment_interface_classname' => MockEPayInterface::class]);
        $order->commit();
        $orderId = $order->id;


        $block = new Block_Cart(['cats' => [1], 'epay_interface_classname' => MockEPayInterface::class]); //

        $interface = $this->getMockBuilder(MockEPayInterface::class)
            ->setMethods(['checkWebhook'])
            ->setConstructorArgs([$block])
            ->getMock();
        $interface->expects($this->once())
            ->method('checkWebhook')
            ->willReturn(['paymentId' => 'aaaa-bbbb-cccc-dddd', 'orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }


    /**
     * Тест метода findOrder() - случай с вебхуком, когда возвращается только orderId
     */
    public function testFindOrderWithWebhookOrderIdOnly()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;

        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['checkWebhook'])
            ->getMock();
        $interface->expects($this->once())
            ->method('checkWebhook')
            ->willReturn(['orderId' => $orderId]);

        $result = $interface->findOrder();

        $this->assertEquals($orderId, $result->id);

        Order::delete($order);
    }


    /**
     * Тест метода findOrder() - случай пустого результата
     */
    public function testFindOrderWithEmpty()
    {
        $interface = new SberbankInterface(new Block_Cart(), new Page(), [], [], [], ['orderId' => 'notexistingorder']);

        $result = $interface->findOrder();

        $this->assertNull($result);
    }


    /**
     * Тест метода getLogFile()
     */
    public function testGetLogFile()
    {
        $interface = new SberbankInterface();

        $result = $interface->getLogFile();

        $this->assertEquals('sberbank.log', $result);
    }


    /**
     * Проверка метода doLog()
     */
    public function testDoLog()
    {
        $interface = new SberbankInterface();
        $filename = Application::i()->baseDir . '/logs/sberbank.log';
        if (is_file($filename)) {
            unlink($filename);
        }

        $interface->doLog('Тестовое сообщение');

        $this->assertFileExists($filename);

        $text = file_get_contents($filename);

        $this->assertStringContainsString('testDoLog:', $text);
        $this->assertStringContainsString("\nТестовое сообщение\n\n", $text);
    }


    /**
     * Проверка метода doLogRequest()
     */
    public function testDoLogRequest()
    {
        $interface = new SberbankInterface();
        $filename = Application::i()->baseDir . '/logs/sberbank.log';
        if (is_file($filename)) {
            unlink($filename);
        }

        $interface->doLogRequest('http://test', 'REQUEST', 'RESPONSE');

        $this->assertFileExists($filename);

        $text = file_get_contents($filename);

        $this->assertStringContainsString('testDoLogRequest:', $text);
        $this->assertStringContainsString("\nhttp://test\nЗАПРОС:\nREQUEST\nОТВЕТ:\nRESPONSE\n\n", $text);
    }


    /**
     * Проверка метода logHistory
     */
    public function testLogHistory()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $interface = new SberbankInterface();

        $interface->logHistory($order, 'Тестовое сообщение');

        $order = new Order($orderId);

        $this->assertCount(1, $order->history);
        $this->assertEquals('Тестовое сообщение', $order->history[0]->description);
        $this->assertEquals(false, $order->history[0]->paid);

        Order::delete($order);
    }


    /**
     * Проверка метода logHistory - случай со статусом оплаты
     */
    public function testLogHistoryWithPaymentStatus()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $interface = new SberbankInterface();

        $interface->logHistory($order, 'Тестовое сообщение', true);

        $order = new Order($orderId);

        $this->assertCount(1, $order->history);
        $this->assertEquals('Тестовое сообщение', $order->history[0]->description);
        $this->assertEquals(true, $order->history[0]->paid);

        Order::delete($order);
    }


    /**
     * Тест метода getEPayWidget
     */
    public function testGetEPayWidget()
    {
        $snippet = new Snippet(['urn' => 'sberbank']);
        $snippet->commit();
        $snippetId = $snippet->id;

        $interface = new SberbankInterface();
        $result = $interface->getEPayWidget();

        $this->assertEquals($snippetId, $result->id);

        Snippet::delete($snippet);
    }


    /**
     * Тест метода getEPayWidget - случай с fallback'ом в "epay"
     */
    public function testGetEPayWidgetWithFallback()
    {
        $snippet = new Snippet(['urn' => 'epay']);
        $snippet->commit();
        $snippetId = $snippet->id;

        $interface = new SberbankInterface();
        $result = $interface->getEPayWidget();

        $this->assertEquals($snippetId, $result->id);

        Snippet::delete($snippet);
    }


    /**
     * Провайдер данных для метода testIsSuccessfulStatus
     * @return array <pre><code>array<[int Статус, bool Ожидаемое значение]></code></pre>
     */
    public function isSuccessfulStatusDataProvider()
    {
        return [
            [SberbankInterface::REQUEST_STATUS_HOLD, true],
            [SberbankInterface::REQUEST_STATUS_PAID, true],
            [1234, false],
            [4321, false],
        ];
    }


    /**
     * Тест метода isSuccessfulStatus
     * @param int $status Статус
     * @param bool $expected Ожидаемое значение
     * @dataProvider isSuccessfulStatusDataProvider
     */
    public function testIsSuccessfulStatus(int $status, bool $expected)
    {
        $interface = new SberbankInterface();

        $result = $interface->isSuccessfulStatus($status);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода getOrderDescription
     */
    public function testGetOrderDescription()
    {
        $order = new Order(['id' => 123, 'payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $interface = new SberbankInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'test']);

        $result = $interface->getOrderDescription($order);

        $this->assertEquals('Заказ #123 на сайте test', $result);
    }


    /**
     * Тест метода processInitialPaymentData()
     */
    public function testProcessInitialPaymentData()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_interface_id' => $paymentInterfaceId,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())->method('logHistory')->with(
            $order,
            'Зарегистрировано в системе Сбербанк (ID# заказа в системе банка: aaaa-bbbb-cccc-dddd, платежный URL: http://test)',
        );
        $interface->expects($this->once())->method('doLog')->with($orderId . ' / aaaa-bbbb-cccc-dddd');

        $result = $interface->processInitialPaymentData($order, $block, 'aaaa-bbbb-cccc-dddd', 'http://test');

        $this->assertEquals('aaaa-bbbb-cccc-dddd', $order->payment_id);
        $this->assertEquals($paymentInterfaceId, $order->payment_interface_id);
        $this->assertEquals('http://test', $order->payment_url);

        Order::delete($order);
        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода processInitialPaymentData() - случай с указанием класса платежного интерфейса
     */
    public function testProcessInitialPaymentDataWithInterfaceClassname()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_interface_classname' => MockEPayInterface::class,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $interface = new MockEPayInterface($block);

        $result = $interface->processInitialPaymentData($order, $block, 'aaaa-bbbb-cccc-dddd', 'http://test');

        $this->assertEquals('aaaa-bbbb-cccc-dddd', $order->payment_id);
        $this->assertEquals(MockEPayInterface::class, $order->payment_interface_classname);
        $this->assertEquals('http://test', $order->payment_url);

        Order::delete($order);
    }


    /**
     * Тест метода applyPaidStatus()
     */
    public function testApplyPaidStatus()
    {
        $order = new Order(['id' => 123, 'payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['logHistory'])
            ->getMock();
        $interface->expects($this->once())->method('logHistory')->with(
            $order,
            'Оплачено через Сбербанк (ID# заказа в системе банка: aaaa-bbbb-cccc-dddd)',
            true
        );

        $result = $interface->applyPaidStatus($order);

        $this->assertEquals(true, $order->paid);

        Order::delete($order);
    }


    /**
     * Тест метода checkWebhook()
     */
    public function testCheckWebhook()
    {
        $order = new Order();
        $block = new Block_Cart();
        $page = new Page();
        $interface = new class extends EPayInterface {
            public function getURL(bool $isTest = false): string
            {
                return '';
            }

            public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array
            {
                return [];
            }

            public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
            {
                return [];
            }

            public function parseResponseCommonErrors(array $response): array
            {
                return [];
            }

            public function parseInitResponse(array $response): array
            {
                return [];
            }

            public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
            {
                return [];
            }

            public function parseOrderStatusResponse(array $response): array
            {
                return [];
            }

            public function exec(string $method, array $requestData = [], bool $isTest = false): array
            {
                return [];
            }
        };

        $result = $interface->checkWebhook();

        $this->assertNull($result);
    }


    /**
     * Тест метода getOrderStatusData()
     */
    public function testGetOrderStatusData()
    {
        $order = new Order();
        $block = new Block_Cart();
        $page = new Page();
        $interface = new class extends EPayInterface {
            public function getURL(bool $isTest = false): string
            {
                return '';
            }

            public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array
            {
                return [];
            }

            public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
            {
                return [];
            }

            public function parseResponseCommonErrors(array $response): array
            {
                return [];
            }

            public function parseInitResponse(array $response): array
            {
                return [];
            }

            public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
            {
                return [];
            }

            public function parseOrderStatusResponse(array $response): array
            {
                return [];
            }

            public function exec(string $method, array $requestData = [], bool $isTest = false): array
            {
                return [];
            }
        };

        $result = $interface->getOrderStatusData($order, $block, $page);

        $this->assertEquals([], $result);
    }


    /**
     * Тест метода getOrderIsPaid
     */
    public function testGetOrderIsPaid()
    {
        $order = new Order(['id' => 123, 'payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods([
                'getOrderStatusData',
                'getOrderStatusWithData',
                'parseOrderStatusResponse',
                'isSuccessfulStatus',
            ])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderStatusData')
            ->with($order, $block, $page)
            ->willReturn(['orderStatusData' => 1]);
        $interface->expects($this->once())
            ->method('getOrderStatusWithData')
            ->with($order, $block, $page, ['orderStatusData' => 1])
            ->willReturn(['orderStatus' => 1]);
        $interface->expects($this->once())
            ->method('parseOrderStatusResponse')
            ->with(['orderStatus' => 1])
            ->willReturn(['status' => 123]);
        $interface->expects($this->once())
            ->method('isSuccessfulStatus')
            ->with(123)
            ->willReturn(true);

        $result = $interface->getOrderIsPaid($order, $block, $page);

        $this->assertTrue($result);
    }


    /**
     * Тест метода getOrderIsPaid - случай, когда не указан платежный ID# заказа
     */
    public function testGetOrderIsPaidWithNoOrderPaymentId()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Заказ не отправлен на оплату');

        $interface = new SberbankInterface();
        $order = new Order(['id' => 123]);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();

        $result = $interface->getOrderIsPaid($order, $block, $page);
    }


    /**
     * Тест метода getOrderIsPaid - случай с пустым ответом
     */
    public function testGetOrderIsPaidWithNoResponse()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Не удалось получить результат запроса состояния заказа');

        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)->setMethods(['getOrderStatusWithData'])->getMock();
        $interface->expects($this->once())->method('getOrderStatusWithData')->willReturn([]);

        $result = $interface->getOrderIsPaid($order, $block, $page);
    }


    /**
     * Тест метода getOrderIsPaid - случай с возвращенной ошибкой
     */
    public function testGetOrderIsPaidWithError()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('В процессе оплаты заказа');
        $this->expectExceptionMessage('ошибка: #123 Тестовая ошибка');
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['parseOrderStatusResponse', 'doLog', 'getOrderStatusWithData'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderStatusWithData')
            ->willReturn(['orderStatus' => 1]);
        $interface->expects($this->once())
            ->method('parseOrderStatusResponse')
            ->willReturn(['errors' => [['code' => 123, 'message' => 'Тестовая ошибка']]]);
        $interface->expects($this->once())->method('doLog');

        $result = $interface->getOrderIsPaid($order, $block, $page);
    }


    /**
     * Тест метода getOrderIsPaid - случай с возвращенной ошибкой - проверка истории
     */
    public function testGetOrderIsPaidWithErrorCheckHistory()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['parseOrderStatusResponse', 'doLog', 'getOrderStatusWithData'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderStatusWithData')
            ->willReturn(['orderStatus' => 1]);
        $interface->expects($this->once())
            ->method('parseOrderStatusResponse')
            ->willReturn(['errors' => [['code' => 123, 'message' => 'Тестовая ошибка']]]);
        $interface->expects($this->once())->method('doLog');

        try {
            $result = $interface->getOrderIsPaid($order, $block, $page);
        } catch (Exception $e) {
        }
        $order = new Order($orderId);

        $this->assertCount(1, $order->history);
        $this->assertStringContainsString('В процессе оплаты заказа', $order->history[0]->description);
        $this->assertStringContainsString('ошибка: #123 Тестовая ошибка', $order->history[0]->description);

        Order::delete($order);
    }


    /**
     * Тест метода getOrderIsPaid - случай с невозвращенным статусом заказа
     */
    public function testGetOrderIsPaidWithNoStatus()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Не удалось получить статус состояния заказа');
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['parseOrderStatusResponse', 'doLog', 'getOrderStatusWithData'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderStatusWithData')
            ->willReturn(['orderStatus' => 1]);
        $interface->expects($this->once())
            ->method('parseOrderStatusResponse')
            ->willReturn([]);
        $interface->expects($this->once())->method('doLog');

        $result = $interface->getOrderIsPaid($order, $block, $page);
    }


    /**
     * Тест метода result
     */
    public function testResult()
    {
        $order = new Order(['id' => 123, 'payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['getOrderIsPaid', 'doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderIsPaid')
            ->with($order, $block, $page)
            ->willReturn(true);
        $interface->expects($this->once())->method('logHistory')->with(
            $order,
            'Оплачено через Сбербанк (ID# заказа в системе банка: aaaa-bbbb-cccc-dddd)',
            true
        );

        $result = $interface->result($order, $block, $page);

        $this->assertEquals(true, $result['success'][111]);
        $this->assertEquals(true, $order->paid);

        Order::delete($order);
    }


    /**
     * Тест метода result - случай без оплаты
     */
    public function testResultWithNoPayment()
    {
        $order = new Order(['id' => 123, 'payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['getOrderIsPaid', 'doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderIsPaid')
            ->with($order, $block, $page)
            ->willReturn(false);

        $result = $interface->result($order, $block, $page);

        $this->assertStringContainsString('не был оплачен', $result['localError']['order']);
    }


    /**
     * Тест метода result - случай без ID# заказа
     */
    public function testResultWithNoOrderId()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $interface = new SberbankInterface();

        $result = $interface->result($order, $block, $page);

        $this->assertStringContainsStringIgnoringCase('некорректная подпись', $result['localError']['order']);
    }


    /**
     * Тест метода registerOrder
     */
    public function testRegisterOrder()
    {
        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd']);
        $block = new Block_Cart(['id' => 111, 'epay_login' => 'user', 'epay_pass1' => 'pass', 'epay_test' => 1]);
        $page = new Page();
        $mockRequestData = ['request' => 'requestData'];
        $mockResponseData = ['request' => 'requestData'];
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setMethods(['getRegisterOrderData', 'doLog', 'registerOrderWithData'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getRegisterOrderData')
            ->with($order, $block, $page)
            ->willReturn($mockRequestData);
        $interface->expects($this->once())->method('registerOrderWithData')
            ->with($order, $block, $page, $mockRequestData)
            ->willReturn($mockResponseData);

        $result = $interface->registerOrder($order, $block, $page);

        $this->assertEquals($mockResponseData, $result);
    }


    /**
     * Тест метода init
     */
    public function testInit()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_interface_id' => $paymentInterfaceId,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page();
        $mockResponseData = ['ok' => true];
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, [], ['AJAX' => 1]])
            ->setMethods(['registerOrder', 'parseInitResponse', 'doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())
            ->method('registerOrder')
            ->with($order, $block, $page)
            ->willReturn($mockResponseData);
        $interface->expects($this->once())
            ->method('parseInitResponse')
            ->with($mockResponseData)
            ->willReturn(['paymentId' => 'aaaa-bbbb-cccc-dddd', 'paymentURL' => 'http://test']);
        $interface->expects($this->once())->method('logHistory')->with(
            $order,
            'Зарегистрировано в системе Сбербанк (ID# заказа в системе банка: aaaa-bbbb-cccc-dddd, платежный URL: http://test)',
        );
        $interface->expects($this->once())->method('doLog')->with($orderId . ' / aaaa-bbbb-cccc-dddd');

        $result = $interface->init($order, $block, $page);

        $this->assertEquals($orderId, $_SESSION['orderId']);
        $this->assertEquals('aaaa-bbbb-cccc-dddd', $order->payment_id);
        $this->assertEquals($paymentInterfaceId, $order->payment_interface_id);
        $this->assertEquals('http://test', $order->payment_url);
        $this->assertEquals(['redirectUrl' => 'http://test'], $result);

        Order::delete($order);
        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода init - случай без ответа
     */
    public function testInitWithoutResponse()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, [], ['AJAX' => 1]])
            ->setMethods(['registerOrder', 'doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())
            ->method('registerOrder')
            ->with($order, $block, $page)
            ->willReturn([]);
        $interface->expects($this->once())
            ->method('doLog')
            ->with(var_export(['Не удалось получить результат запроса на оплату'], true));

        $result = $interface->init($order, $block, $page);

        $this->assertEquals(['localError' => ['Не удалось получить результат запроса на оплату']], $result);

        Order::delete($order);
    }


    /**
     * Тест метода init - случай с ошибкой
     */
    public function testInitWithError()
    {
        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, [], ['AJAX' => 1]])
            ->setMethods(['registerOrder', 'parseInitResponse', 'doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())
            ->method('registerOrder')
            ->with($order, $block, $page)
            ->willReturn(['error' => 123]);
        $interface->expects($this->once())
            ->method('parseInitResponse')
            ->with(['error' => 123])
            ->willReturn(['errors' => [['code' => 123, 'message' => 'Тестовая ошибка']]]);
        $interface->expects($this->once())
            ->method('doLog')
            ->with(var_export(['В процессе регистрации заказа возникла ошибка: #123 Тестовая ошибка'], true));

        $result = $interface->init($order, $block, $page);

        $this->assertEquals(['localError' => ['В процессе регистрации заказа возникла ошибка: #123 Тестовая ошибка']], $result);

        Order::delete($order);
    }


    /**
     * Тест метода init - случай без orderId
     */
    public function testInitWithoutOrderId()
    {
        $order = new Order();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page();
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, [], ['AJAX' => 1]])
            ->setMethods(['registerOrder', 'doLog', 'logHistory'])
            ->getMock();
        $interface->expects($this->once())
            ->method('registerOrder')
            ->with($order, $block, $page)
            ->willReturn(['aaa' => 'bbb']);
        $interface->expects($this->once())
            ->method('doLog')
            ->with(var_export(['Не удалось получить адрес для оплаты'], true));

        $result = $interface->init($order, $block, $page);

        $this->assertEquals(['localError' => ['Не удалось получить адрес для оплаты']], $result);

        Order::delete($order);
    }


    /**
     * Тест метода process
     */
    public function testProcess()
    {
        $snippet = new Snippet(['urn' => 'sberbank']);
        $snippet->commit();
        $snippetId = $snippet->id;

        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page(25); // Корзина

        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, [], ['epay' => 1]])
            ->setMethods(['init'])
            ->getMock();
        $interface->expects($this->once())
            ->method('init')
            ->with($order, $block, $page)
            ->willReturn(['redirectUrl' => 'http://test']);

        $result = $interface->process($order);

        $this->assertEquals('http://test', $result['redirectUrl']);
        $this->assertEquals($snippetId, $result['epayWidget']->id);
        $this->assertEquals($order, $result['Item']);

        Snippet::delete($snippet);
        Order::delete($order);
    }


    /**
     * Тест метода process - случай получения результата
     */
    public function testProcessWithResult()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $snippet = new Snippet(['urn' => 'sberbank']);
        $snippet->commit();
        $snippetId = $snippet->id;

        $order = new Order(['payment_id' => 'aaaa-bbbb-cccc-dddd', 'payment_interface_id' => $paymentInterfaceId]);
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_interface_id' => $paymentInterfaceId,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page(25); // Корзина
        $page->initialURL = '/cart/result/';

        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, ['orderId' => 'aaaa-bbbb-cccc-dddd'], ['epay' => 1]])
            ->setMethods(['result'])
            ->getMock();
        $interface->expects($this->once())
            ->method('result')
            ->with(
                $this->callback(function ($arg) use ($orderId) {
                    return ($arg instanceof Order) && ($arg->id == $orderId);
                }),
                $block,
                $page
            )
            ->willReturn(['success' => ['111' => true]]);

        $result = $interface->process();

        $this->assertEquals(['111' => true], $result['success'] ?? null);
        $this->assertEquals($snippetId, $result['epayWidget']->id);
        $this->assertEquals($orderId, $result['Item']->id);

        Snippet::delete($paymentInterface);
        Snippet::delete($snippet);
        Order::delete($order);
    }


    /**
     * Тест метода process - случай без указания заказа
     */
    public function testProcessWithNoOrder()
    {
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page(25); // Корзина
        $page->initialURL = '/cart/fail/';

        $interface = new SberbankInterface($block, $page, [], ['epay' => 1]);

        $result = $interface->process();

        $this->assertEquals(['Ошибка: Заказ не найден'], $result['localError']);
        $this->assertNull($result['epayWidget'] ?? null);
        $this->assertNull($result['Item'] ?? null);
    }


    /**
     * Тест метода process - случай неуспешной оплаты
     */
    public function testProcessWithException()
    {
        $snippet = new Snippet(['urn' => 'sberbank']);
        $snippet->commit();
        $snippetId = $snippet->id;

        $order = new Order();
        $order->commit();
        $orderId = $order->id;
        $block = new Block_Cart([
            'id' => 111,
            'epay_login' => 'user',
            'epay_pass1' => 'pass',
            'epay_test' => 1,
        ]);
        $page = new Page(25); // Корзина
        $page->initialURL = '/cart/fail/';

        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block, $page, [], ['epay' => 1]])
            ->setMethods(['result'])
            ->getMock();
        $interface->expects($this->once())
            ->method('result')
            ->with($order, $block, $page)
            ->willThrowException(new Exception('Тестовая ошибка', 123));

        $result = $interface->process($order);

        $this->assertEquals(['Ошибка: #123 Тестовая ошибка'], $result['localError']);
        $this->assertNull($result['epayWidget'] ?? null);
        $this->assertNull($result['Item'] ?? null);

        Snippet::delete($snippet);
        Order::delete($order);
    }
}
