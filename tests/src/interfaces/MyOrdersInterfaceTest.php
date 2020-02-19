<?php
/**
 * Файл теста интерфейса сервиса "Мои заказы"
 */
namespace RAAS\CMS\Shop;

use RAAS\Controller_Frontend;
use RAAS\CMS\Block_PHP;
use RAAS\CMS\Page;
use RAAS\CMS\User;

/**
 * Класс теста интерфейса сервиса "Мои заказы"
 */
class MyOrdersInterfaceTest extends BaseDBTest
{
    /**
     * Тест получения пользователя
     */
    public function testGetUser()
    {
        Controller_Frontend::i()->user = new User(1);
        $interface = new MyOrdersInterface();

        $result = $interface->getUser();

        $this->assertEquals(Controller_Frontend::i()->user, $result);
        $this->assertEquals(1, $result->id);

        Controller_Frontend::i()->user = new User();
    }


    /**
     * Тест получения заказа
     */
    public function testGetOrder()
    {
        $order = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order->commit();
        $id = (int)$order->id;
        $user = new User(1);

        $interface = new MyOrdersInterface();

        $result = $interface->getOrder($id, $user);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($id, $result->id);

        Order::delete($order);
    }


    /**
     * Тест получения заказа
     * Случай с несовпадающим пользователем
     */
    public function testGetOrderWithInvalidUser()
    {
        $order = new Order([
            'uid' => 2,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order->commit();
        $id = (int)$order->id;
        $user = new User(1);

        $interface = new MyOrdersInterface();

        $result = $interface->getOrder($id, $user);

        $this->assertNull($result);

        Order::delete($order);
    }


    /**
     * Тест удаления заказа
     */
    public function testDeleteOrder()
    {
        $order = new Order([
            'uid' => 2,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order->commit();
        $id = (int)$order->id;
        $order = new Order($id);
        $interface = new MyOrdersInterface();

        $this->assertEquals($id, $order->id);

        $result = $interface->deleteOrder($order, '');

        $this->assertTrue($result);

        $order = new Order($id);
        $this->assertEmpty($order->id);
    }


    /**
     * Тест обработки страницы для отображения заказа
     */
    public function testProcessOrder()
    {
        $order = new Order([
            'uid' => 2,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order->commit();
        $id = (int)$order->id;
        $page = new Page(1);

        $interface = new MyOrdersInterface();

        $interface->processOrder($order, $page);

        $this->assertEquals('Главная', $page->oldName);
        $this->assertEquals($order, $page->Item);
        $this->assertContains(
            'Заказ #' . $id . ' от ' . date('d.m.Y H:i'),
            $page->name
        );

        Order::delete($order);
    }


    /**
     * Тест получения списка заказов
     * Случай с неавторизованным пользователем
     */
    public function testGetOrdersList()
    {
        $order1 = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order1->commit();
        $id1 = (int)$order1->id;
        $order2 = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order2->commit();
        $id2 = (int)$order2->id;
        $order3 = new Order([
            'uid' => 2,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order3->commit();
        $id3 = (int)$order3->id;

        $user = new User(1);
        $interface = new MyOrdersInterface();

        $result = $interface->getOrdersList($user);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Order::class, $result[0]);
        $this->assertInstanceOf(Order::class, $result[1]);
        $this->assertEquals($id2, $result[0]->id);
        $this->assertEquals($id1, $result[1]->id);

        Order::delete($order1);
        Order::delete($order2);
        Order::delete($order3);
    }


    /**
     * Тест получения списка заказов
     */
    public function testGetOrdersListWithoutUser()
    {
        $user = new User();
        $interface = new MyOrdersInterface();

        $result = $interface->getOrdersList($user);

        $this->assertEquals([], $result);
    }


    /**
     * Тест отработки интерфейса
     * Случай с удалением заказа
     */
    public function testProcessWithDelete()
    {
        Controller_Frontend::i()->user = new User(1);
        $order = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order->commit();
        $id = (int)$order->id;
        $interface = new MyOrdersInterface(
            new Block_PHP(),
            new Page(1),
            ['action' => 'delete', 'id' => $id],
            ['AJAX' => 1]
        );

        $this->assertEquals($id, $order->id);

        $result = $interface->process();

        $this->assertEquals(['success' => true], $result);

        $order = new Order($id);
        $this->assertEmpty($order->id);
        Controller_Frontend::i()->user = new User();
    }


    /**
     * Тест отработки интерфейса
     * Случай с проверкой редиректа "назад"
     */
    public function testProcessWithDeleteAndBackRedirect()
    {
        Controller_Frontend::i()->user = new User(1);
        $order = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $interface = $this->getMockBuilder(MyOrdersInterface::class)
            ->setMethods([
                'getOrder',
                'deleteOrder',
            ])
            ->setConstructorArgs([
                new Block_PHP(),
                new Page(1),
                ['action' => 'delete', 'id' => $id, 'back' => 1]
            ])
            ->getMock();

        $interface->method('getOrder')->willReturn($order);
        $interface->expects($this->once())->method('deleteOrder')->withConsecutive(
            [$order, 'history:back']
        );
        $result = $interface->process();

        Controller_Frontend::i()->user = new User();
    }


    /**
     * Тест отработки интерфейса
     * Случай с проверкой редиректа по URL
     */
    public function testProcessWithDeleteAndURLRedirect()
    {
        Controller_Frontend::i()->user = new User(1);
        $order = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $interface = $this->getMockBuilder(MyOrdersInterface::class)
            ->setMethods([
                'getOrder',
                'deleteOrder',
            ])
            ->setConstructorArgs([
                new Block_PHP(),
                new Page(1),
                ['action' => 'delete', 'id' => $id],
                [],
                [],
                [],
                ['REQUEST_URI' => '/my_orders/?action=delete&id=1']
            ])
            ->getMock();

        $interface->method('getOrder')->willReturn($order);
        $interface->expects($this->once())->method('deleteOrder')->withConsecutive(
            [$order, '/my_orders/?']
        );
        $result = $interface->process();

        Controller_Frontend::i()->user = new User();
    }


    /**
     * Тест отработки интерфейса
     * Случай с получением заказа
     */
    public function testProcessWithOneOrder()
    {
        Controller_Frontend::i()->user = new User(1);
        $order = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order->commit();
        $id = (int)$order->id;
        $page = new Page(1);
        $interface = new MyOrdersInterface(
            new Block_PHP(),
            $page,
            ['id' => $id]
        );

        $this->assertEquals($id, $order->id);

        $result = $interface->process();

        $this->assertEquals(['Item'], array_keys($result));
        $this->assertEquals($id, $result['Item']->id);
        $this->assertEquals('Главная', $page->oldName);
        $this->assertEquals($order, $page->Item);
        $this->assertContains(
            'Заказ #' . $id . ' от ' . date('d.m.Y H:i'),
            $page->name
        );

        Order::delete($order);
        Controller_Frontend::i()->user = new User();
    }


    /**
     * Тест отработки интерфейса
     * Случай с получением списка заказов
     */
    public function testProcessWithOrdersList()
    {
        Controller_Frontend::i()->user = new User(1);
        $order1 = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order1->commit();
        $id1 = (int)$order1->id;
        $order2 = new Order([
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order2->commit();
        $id2 = (int)$order2->id;
        $order3 = new Order([
            'uid' => 2,
            'pid' => 1,
            'page_id' => 25, // Корзина
            'post_date' => date('Y-m-d H:i:s'),
        ]);
        $order3->commit();
        $id3 = (int)$order3->id;

        $interface = new MyOrdersInterface(new Block_PHP(), new Page(1));

        $result = $interface->process();

        $this->assertEquals(['Set'], array_keys($result));
        $this->assertCount(2, $result['Set']);
        $this->assertInstanceOf(Order::class, $result['Set'][0]);
        $this->assertInstanceOf(Order::class, $result['Set'][1]);
        $this->assertEquals($id2, $result['Set'][0]->id);
        $this->assertEquals($id1, $result['Set'][1]->id);

        Order::delete($order1);
        Order::delete($order2);
        Order::delete($order3);
        Controller_Frontend::i()->user = new User();
    }
}
