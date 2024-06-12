<?php
/**
 * Тест класса Order
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Application;
use RAAS\CMS\Form;
use RAAS\CMS\Form_Field;
use RAAS\CMS\Material;
use RAAS\CMS\Snippet;

/**
 * Тест класса Order
 * @covers RAAS\CMS\Shop\Order
 */
class OrderTest extends BaseTest
{
    public static $tables = [
        'cms_data',
        'cms_fields',
        'cms_forms',
        'cms_materials',
        'cms_shop_cart_types',
        'cms_shop_cart_types_material_types_assoc',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_orders_goods',
        'cms_shop_orders_history',
        'cms_shop_orders_statuses',
        'cms_shop_priceloaders',
        'cms_snippets',
        'registry',
    ];

    /**
     * Проверка получения свойства count
     */
    public function testGetCount()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = $order->count;

        $this->assertEquals(6, $result);

        Order::delete($order);
    }


    /**
     * Проверка получения свойства sum
     */
    public function testGetSum()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = $order->sum;

        $this->assertEquals(14000, $result);

        Order::delete($order);
    }


    /**
     * Проверка получения свойства weight
     */
    public function testGetWeight()
    {
        $cartType = new Cart_Type(1);
        $cartType->weight_callback = 'return 10;';
        $cartType->commit();
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = $order->weight;

        $this->assertEquals(10, $result);


        $cartType->weight_callback = '';
        $cartType->commit();
        Order::delete($order);
    }


    /**
     * Проверка получения свойства sizes
     */
    public function testGetSizes()
    {
        $cartType = new Cart_Type(1);
        $cartType->sizes_callback = 'return [10, 20, 30];';
        $cartType->commit();
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = $order->sizes;

        $this->assertEquals([10, 20, 30], $result);


        $cartType->sizes_callback = '';
        $cartType->commit();
        Order::delete($order);
    }


    /**
     * Проверка получения свойства fields
     */
    public function testGetFields()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = $order->fields;

        $this->assertIsArray($result);
        $this->assertEquals('last_name', $result['last_name']->urn);
        $this->assertNull($result['notexistingfield'] ?? null);

        Order::delete($order);
    }


    /**
     * Проверка получения свойства items
     */
    public function testGetItems()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Это товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Это товар 2', 'meta' => 'aaa', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Это товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = $order->items;

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(Material::class, $result[1]);
        $this->assertEquals('Это товар 2', $result[1]->name);
        $this->assertEquals('Товар 2', $result[1]->originalName);
        $this->assertEquals(2000, $result[1]->realprice);
        $this->assertEquals(2, $result[1]->amount);
        $this->assertEquals('aaa', $result[1]->meta);

        Order::delete($order);
    }


    /**
     * Проверка методов commit() и delete()
     */
    public function testCommitDelete()
    {
        $order = new Order([
            'pid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $orderId = $order->id;
        $sqlQuery = "SELECT * FROM cms_shop_orders_goods WHERE order_id = " . (int)$orderId;

        $result = Application::i()->SQL->get($sqlQuery);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        Order::delete($order);

        $result = Application::i()->SQL->get($sqlQuery);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }


    /**
     * Проверка метода getItemsTextArr()
     */
    public function testGetItemsTextArr()
    {
        $result = Order::getItemsTextArr([
            ['material_id' => 10, 'name' => 'Это товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
            ['material_id' => 11, 'name' => 'Это товар 2', 'meta' => 'aaa', 'realprice' => 2000, 'amount' => 2],
            ['material_id' => 12, 'name' => 'Это товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
        ]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('#10 Это товар 1: 1000 x 1 = 1000', $result[0]);
        $this->assertEquals('#11 Это товар 2 (aaa): 2000 x 2 = 4000', $result[1]);
    }


    /**
     * Проверка метода getItemsTextArr() - случай с материалами
     */
    public function testGetItemsTextArrWithMaterials()
    {
        $material = new Material(11);
        $material->meta = 'aaa';
        $material->realprice = 2000;
        $material->amount = 2;
        $result = Order::getItemsTextArr([
            $material
        ]);

        $this->assertEquals('#11 Товар 2 (aaa): 2000 x 2 = 4000', $result[0]);
    }


    /**
     * Проверка метода notifyStatus()
     */
    public function testNotifyStatus()
    {
        $oldServer = $_SERVER['HTTP_HOST'];
        $_SERVER['HTTP_HOST'] = 'test';

        // Создадим поле с doRich
        $field = new Form_Field([
            'classname' => Form::class,
            'pid' => 3, // Форма заказа
            'datatype' => 'select',
            'urn' => 'test',
            'source_type' => 'ini',
            'source' => '123 = "AAA"',
        ]);
        $field->commit();

        $status = new Order_Status(2); // Обработан
        $status->do_notify = true;
        $status->notification_title = 'Ваш заказ №{{ID}} обработан {{TEST}}';
        $status->commit();
        $order = new Order([
            'pid' => 1,
            'status_id' => 2,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $order->fields['email']->addValue('test@test.org');
        $order->fields['test']->addValue('123');

        $result = $order->notifyStatus(true);

        $this->assertIsArray($result['emails']['emails']);
        $this->assertCount(1, $result['emails']['emails']);
        $this->assertEquals('test@test.org', $result['emails']['emails'][0]);
        $this->assertStringContainsString('Ваш заказ №' . $order->id . ' обработан AAA', $result['emails']['subject']);
        $this->assertStringContainsString('Ваш заказ №' . $order->id . ' обработан', $result['emails']['message']);
        $this->assertStringContainsString('Администрация сайта', $result['emails']['message']);
        $this->assertIsArray($result['emails']['embedded']);
        $this->assertEmpty($result['emails']['embedded']);
        $this->assertStringContainsString('Администрация сайта test', $result['emails']['from']);
        $this->assertNull($result['emails']['fromEmail']);
        $this->assertIsArray($result['emails']['attachments']);
        $this->assertEmpty($result['emails']['attachments']);

        Order::delete($order);
        Form_Field::delete($field);
        $status->do_notify = false;
        $status->notification_title = 'Ваш заказ №{{ID}} обработан';
        $status->commit();
        $_SERVER['HTTP_HOST'] = $oldServer;
    }


    /**
     * Проверка метода importByPayment()
     */
    public function testImportByPayment()
    {
        $snippet = new Snippet(['urn' => 'testpaymentsnippet']);
        $snippet->commit();
        $order = new Order([
            'pid' => 1,
            'payment_id' => '12345',
            'payment_interface_id' => $snippet->id,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = Order::importByPayment('12345', $snippet);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);

        Order::delete($order);
        Snippet::delete($snippet);
    }


    /**
     * Проверка метода importByPayment() - случай с указанием класса платежного интерфейса
     */
    public function testImportByPaymentWithPaymentInterfaceClassname()
    {
        $order = new Order([
            'pid' => 1,
            'payment_id' => '12345',
            'payment_interface_classname' => SberbankInterface::class,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();

        $result = Order::importByPayment('12345', SberbankInterface::class);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);

        Order::delete($order);
    }


    /**
     * Проверка метода importByPayment() - случай с неуказанным payment_id
     */
    public function testImportByPaymentWithoutPaymentId()
    {
        $result = Order::importByPayment('');

        $this->assertNull($result);
    }


    /**
     * Проверка метода importByPayment() - случай с ненайденным заказом
     */
    public function testImportByPaymentWithNotFound()
    {
        $result = Order::importByPayment('notexisting');

        $this->assertNull($result);
    }
}
