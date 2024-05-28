<?php
/**
 * Тест класса SberbankCheckPaymentCommand
 */
namespace RAAS\CMS\Shop;

use Exception;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Cron;
use RAAS\CMS\Form;
use RAAS\CMS\Form_Field;
use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса SberbankCheckPaymentCommand
 * @covers RAAS\CMS\Shop\SberbankCheckPaymentCommand
 */
class SberbankCheckPaymentCommandTest extends BaseTest
{
    public static $tables = [
        'cms_access',
        'cms_access_blocks_cache',
        'cms_blocks',
        'cms_blocks_pages_assoc',
        'cms_data',
        'cms_fields',
        'cms_forms',
        'cms_pages',
        'cms_shop_blocks_cart',
        'cms_shop_cart_types',
        'cms_shop_orders',
        'cms_shop_orders_goods',
        'cms_shop_orders_history',
        'cms_shop_priceloaders',
        'cms_snippets',
    ];

    /**
     * Тест метода getInterface()
     */
    public function testGetInterface()
    {
        $block = new Block_Cart();
        $command = new SberbankCheckPaymentCommand();

        $result = $command->getInterface($block);

        $this->assertInstanceOf(SberbankInterface::class, $result);
        $this->assertEquals($block, $result->block);
    }


    /**
     * Тест метода process()
     */
    public function testProcess()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $block = new Block_Cart([
            'cart_type' => 1,
            'location' => 'content',
            'epay_interface_id' => $paymentInterfaceId,
            'cats' => [1],
        ]);
        $block->commit();
        $page = new Page(1);
        $order = new Order([
            'pid' => 1,
            'payment_interface_id' => $paymentInterfaceId,
            'payment_id' => 'aaaa-bbbb-cccc-dddd',
            'payment_url' => 'https://securecardpayment.ru/payment/merchants/sbersafe_sberid/payment_ru.html?mdOrder=aaaa-bbbb-cccc-dddd',
            'page_id' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block])
            ->setMethods(['getOrderIsPaid'])
            ->getMock();
        $interface->expects($this->once())->method('getOrderIsPaid')->with($order, $block, $page)->willReturn(true);

        $command = $this->getMockBuilder(SberbankCheckPaymentCommand::class)
            ->setConstructorArgs([Controller_Cron::i()])
            ->setMethods(['getInterface'])
            ->getMock();
        $command->expects($this->once())->method('getInterface')->willReturn($interface);

        ob_start();
        $command->process();
        $result = ob_get_clean();

        $this->assertStringContainsString('— ОПЛАЧЕН', $result);

        $order = new Order($orderId);
        $this->assertEquals(true, $order->paid);
        $this->assertCount(1, $order->history);
        $this->assertStringContainsString('Оплачено через Сбербанк', $order->history[0]->description);
        $this->assertEquals(
            'Оплачено через Сбербанк (проверка по времени) (ID# заказа в системе банка: aaaa-bbbb-cccc-dddd)',
            $order->history[0]->description
        );
        $this->assertEquals(true, $order->history[0]->paid);

        Block_Cart::delete($block);
        Order::delete($order);
        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода process() - случай с отсутствующим блоком
     */
    public function testProcessWithNoBlock()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $page = new Page(1);
        $order = new Order([
            'pid' => 1,
            'payment_interface_id' => $paymentInterfaceId,
            'payment_id' => 'aaaa-bbbb-cccc-dddd',
            'payment_url' => 'https://securecardpayment.ru/payment/merchants/sbersafe_sberid/payment_ru.html?mdOrder=aaaa-bbbb-cccc-dddd',
            'page_id' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $command = new SberbankCheckPaymentCommand(Controller_Cron::i());

        ob_start();
        $command->process();
        $result = ob_get_clean();

        $this->assertStringContainsString('Невозможно найти блок корзины для заказа #' . $orderId, $result);

        Order::delete($order);
        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода process() - случай с отсутствующей страницей
     */
    public function testProcessWithNoPage()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $block = new Block_Cart([
            'cart_type' => 1,
            'location' => 'content',
            'epay_interface_id' => $paymentInterfaceId,
            'cats' => [1],
        ]);
        $block->commit();
        $page = new Page(1);
        $order = new Order([ // Намеренно убрали page_id
            'pid' => 1,
            'payment_interface_id' => $paymentInterfaceId,
            'payment_id' => 'aaaa-bbbb-cccc-dddd',
            'payment_url' => 'https://securecardpayment.ru/payment/merchants/sbersafe_sberid/payment_ru.html?mdOrder=aaaa-bbbb-cccc-dddd',
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $command = new SberbankCheckPaymentCommand(Controller_Cron::i());

        ob_start();
        $command->process();
        $result = ob_get_clean();

        $this->assertStringContainsString('Невозможно найти страницу корзины для заказа #' . $orderId, $result);

        Order::delete($order);
        Block_Cart::delete($block);
        Snippet::delete($paymentInterface);
    }


    /**
     * Тест метода process() - случай с выбрасываемым исключением
     */
    public function testProcessWithException()
    {
        $paymentInterface = new Snippet(['urn' => 'testpaymentinterface']);
        $paymentInterface->commit();
        $paymentInterfaceId = $paymentInterface->id;
        $block = new Block_Cart([
            'cart_type' => 1,
            'location' => 'content',
            'epay_interface_id' => $paymentInterfaceId,
            'cats' => [1],
        ]);
        $block->commit();
        $page = new Page(1);
        $order = new Order([
            'pid' => 1,
            'payment_interface_id' => $paymentInterfaceId,
            'payment_id' => 'aaaa-bbbb-cccc-dddd',
            'payment_url' => 'https://securecardpayment.ru/payment/merchants/sbersafe_sberid/payment_ru.html?mdOrder=aaaa-bbbb-cccc-dddd',
            'page_id' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $interface = $this->getMockBuilder(SberbankInterface::class)
            ->setConstructorArgs([$block])
            ->setMethods(['getOrderIsPaid'])
            ->getMock();
        $interface->expects($this->once())
            ->method('getOrderIsPaid')
            ->with($order, $block, $page)
            ->willThrowException(new Exception('Тестовая ошибка'));

        $command = $this->getMockBuilder(SberbankCheckPaymentCommand::class)
            ->setConstructorArgs([Controller_Cron::i()])
            ->setMethods(['getInterface'])
            ->getMock();
        $command->expects($this->once())->method('getInterface')->willReturn($interface);

        ob_start();
        $command->process();
        $result = ob_get_clean();

        $this->assertStringContainsString('Тестовая ошибка для заказа #' . $orderId, $result);

        Order::delete($order);
        Block_Cart::delete($block);
        Snippet::delete($paymentInterface);
    }
}
