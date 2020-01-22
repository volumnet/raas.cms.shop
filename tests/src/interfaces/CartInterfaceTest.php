<?php
/**
 * Файл теста стандартного интерфейса корзины
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс теста стандартного интерфейса корзины
 */
class CartInterfaceTest extends BaseDBTest
{
    /**
     * Проверка конвертации мета-данных для сохранения в заказ
     */
    public function testConvertMeta()
    {
        $interface = new CartInterface();

        $result = $interface->convertMeta('test');

        $this->assertEquals('test', $result);
    }


    /**
     * Тест получения "сырого" заказа (без commit'а и заполненных полей)
     */
    public function testGetRawOrder()
    {
        $interface = new CartInterface();
        $cart = new Cart(new Cart_Type(1));

        $result = $interface->getRawOrder($cart);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEmpty($result->id);
        $this->assertEquals(1, $result->pid);
    }


    /**
     * Тест добавления товаров в заказ из корзины
     */
    public function testProcessOrderItems()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $cart = new Cart(new Cart_Type(1));
        $interface = new CartInterface();
        $order = new Order(['pid' => 1]);

        $result = $interface->processOrderItems($order, $cart);

        $this->assertCount(3, $order->meta_items);
        $this->assertEquals(10, $order->meta_items[0]['material_id']);
        $this->assertEquals(11, $order->meta_items[1]['material_id']);
        $this->assertEquals(12, $order->meta_items[2]['material_id']);
        $this->assertEquals('Товар 1', $order->meta_items[0]['name']);
        $this->assertEquals(83620, $order->meta_items[0]['realprice']);
        $this->assertEquals(2, $order->meta_items[1]['amount']);
        $this->assertEquals('aaa', $order->meta_items[1]['meta']);
    }


    /**
     * Тест получения заголовка e-mail сообщения
     * (случай с заголовком для администратора)
     */
    public function testGetEmailOrderSubjectWithForAdmin()
    {
        $interface = new CartInterface(
            null,
            null,
            [],
            [],
            [],
            [],
            ['HTTP_HOST' => 'xn--d1acufc.xn--p1ai']
        );
        $order = new Order(['pid' => 1, 'id' => 1, 'page_id' => 25]);

        $result = $interface->getEmailOrderSubject($order, true);

        $this->assertContains(date('d.m.Y H:i'), $result);
        $this->assertContains('Новый заказ корзины «Корзина» на странице «Корзина»', $result);
    }


    /**
     * Тест получения заголовка e-mail сообщения
     * (случай с заголовком для пользователя)
     */
    public function testGetEmailOrderSubjectWithForUser()
    {
        $interface = new CartInterface(
            null,
            null,
            [],
            [],
            [],
            [],
            ['HTTP_HOST' => 'xn--d1acufc.xn--p1ai']
        );
        $order = new Order(['pid' => 1, 'id' => 1]);

        $result = $interface->getEmailOrderSubject($order, false);

        $this->assertContains(date('d.m.Y H:i'), $result);
        $this->assertContains('Новый заказ #1 на сайте ДОМЕН.РФ', $result);
    }


    /**
     * Тест уведомления о заказе
     * (случай отправки для администратора)
     */
    public function testNotifyOrderWithForAdmin()
    {
        $form = new Form(3);
        $form->email = 'test@test.org, [79990000000@sms.test.org], [+79990000000]';
        $form->commit();
        $order = new Order([
            'id' => 1,
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25,
            'post_date' => date('Y-m-d H:i:s'),
            'vis' => 0,
            'ip' => '127.0.0.1',
            'user_agent' => 'User Agent',
            'status_id' => 0,
            'paid' => 0,
            'meta_items' => [
                [
                    'material_id' => 10,
                    'name' => 'Товар 1',
                    'meta' => '',
                    'realprice' => 83620,
                    'amount' => 1
                ],
                [
                    'material_id' => 11,
                    'name' => 'Товар 2',
                    'meta' => '',
                    'realprice' => 67175,
                    'amount' => 2
                ],
                [
                    'material_id' => 12,
                    'name' => 'Товар 3',
                    'meta' => '',
                    'realprice' => 71013,
                    'amount' => 3
                ]
            ],
        ]);
        $order->commit();
        $order->fields['full_name']->addValue('Тестовый пользователь');
        $order->fields['phone']->addValue('+7 999 000-00-00');
        $order->fields['email']->addValue('user@test.org');
        $order->fields['agree']->addValue('1');
        $material = new Material(7);


        $interface = new CartInterface();
        Package::i()->registrySet(
            'sms_gate',
            'http://smsgate/{{PHONE}}/{{TEXT}}/'
        );
        Controller_Frontend::i()->exportLang(Application::i(), $page->lang);
        Controller_Frontend::i()->exportLang(Package::i(), $page->lang);
        Controller_Frontend::i()->exportLang(Module::i(), $page->lang);

        $result = $interface->notifyOrder($order, $material, true, true);

        $this->assertEquals(['test@test.org'], $result['emails']['emails']);
        $this->assertContains(
            'Новый заказ корзины «Корзина» на странице «Корзина»',
            $result['emails']['subject']
        );
        $this->assertContains('<div>', $result['emails']['message']);
        $this->assertContains(
            'Телефон: +7 999 000-00-00',
            $result['emails']['message']
        );
        $this->assertContains('/admin/', $result['emails']['message']);
        $this->assertContains('<table', $result['emails']['message']);
        $this->assertContains('edit_material', $result['emails']['message']);
        $this->assertContains('Администрация сайта', $result['emails']['from']);
        $this->assertContains('info@', $result['emails']['fromEmail']);
        $this->assertEquals(
            ['79990000000@sms.test.org'],
            $result['smsEmails']['emails']
        );
        $this->assertContains(
            'Новый заказ корзины «Корзина» на странице «Корзина»',
            $result['smsEmails']['subject']
        );
        $this->assertNotContains('<div>', $result['smsEmails']['message']);
        $this->assertContains(
            'Администрация сайта',
            $result['smsEmails']['from']
        );
        $this->assertContains('info@', $result['smsEmails']['fromEmail']);
        $this->assertContains(
            'Телефон: +7 999 000-00-00',
            $result['smsEmails']['message']
        );
        $this->assertContains(
            'smsgate/%2B79990000000/',
            $result['smsPhones'][0]
        );
        $this->assertContains(
            urlencode('Телефон: +7 999 000-00-00'),
            $result['smsPhones'][0]
        );

        $form->email = '';
        $form->commit();
        Package::i()->registrySet('sms_gate', '');
        Order::delete($order);
    }


    /**
     * Тест уведомления о заказе
     * (случай отправки для пользователя)
     */
    public function testNotifyOrderWithForUser()
    {
        $order = new Order([
            'id' => 1,
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25,
            'post_date' => date('Y-m-d H:i:s'),
            'vis' => 0,
            'ip' => '127.0.0.1',
            'user_agent' => 'User Agent',
            'status_id' => 0,
            'paid' => 0,
            'meta_items' => [
                [
                    'material_id' => 10,
                    'name' => 'Товар 1',
                    'meta' => '',
                    'realprice' => 83620,
                    'amount' => 1
                ],
                [
                    'material_id' => 11,
                    'name' => 'Товар 2',
                    'meta' => '',
                    'realprice' => 67175,
                    'amount' => 2
                ],
                [
                    'material_id' => 12,
                    'name' => 'Товар 3',
                    'meta' => '',
                    'realprice' => 71013,
                    'amount' => 3
                ]
            ],
        ]);
        $order->commit();
        $order->fields['full_name']->addValue('Тестовый пользователь');
        $order->fields['phone']->addValue('+7 999 000-00-00');
        $order->fields['email']->addValue('user@test.org');
        $order->fields['agree']->addValue('1');
        $material = new Material(7);


        $interface = new CartInterface(
            null,
            null,
            [],
            [],
            [],
            [],
            ['HTTP_HOST' => 'xn--d1acufc.xn--p1ai']
        );
        Package::i()->registrySet(
            'sms_gate',
            'http://smsgate/{{PHONE}}/{{TEXT}}/'
        );
        Controller_Frontend::i()->exportLang(Application::i(), $page->lang);
        Controller_Frontend::i()->exportLang(Package::i(), $page->lang);
        Controller_Frontend::i()->exportLang(Module::i(), $page->lang);

        $result = $interface->notifyOrder($order, $material, false, true);

        $this->assertEquals(['user@test.org'], $result['emails']['emails']);
        $this->assertContains(
            'Новый заказ #1 на сайте ДОМЕН.РФ',
            $result['emails']['subject']
        );
        $this->assertContains('<div>', $result['emails']['message']);
        $this->assertContains(
            'Телефон: +7 999 000-00-00',
            $result['emails']['message']
        );
        $this->assertNotContains('/admin/', $result['emails']['message']);
        $this->assertContains('/catalog/', $result['emails']['message']);
        $this->assertContains('<table', $result['emails']['message']);
        $this->assertNotContains('edit_material', $result['emails']['message']);
        $this->assertContains('Администрация сайта', $result['emails']['from']);
        $this->assertContains('info@', $result['emails']['fromEmail']);
        $this->assertEmpty($result['smsEmails']);
        $this->assertEmpty($result['smsPhones']); // Пока по SMS ничего не отправляем пользователю

        Package::i()->registrySet('sms_gate', '');
        Order::delete($order);
    }


    /**
     * Тест уведомления о заказе
     * (случай с формой без интерфейса)
     */
    public function testNotifyOrderWithNoFormInterface()
    {
        $form = new Form(3);
        $form->interface_id = 0;
        $form->commit();
        $order = new Order([
            'id' => 1,
            'uid' => 1,
            'pid' => 1,
            'page_id' => 25,
            'post_date' => date('Y-m-d H:i:s'),
            'vis' => 0,
            'ip' => '127.0.0.1',
            'user_agent' => 'User Agent',
            'status_id' => 0,
            'paid' => 0,
            'meta_items' => [
                [
                    'material_id' => 10,
                    'name' => 'Товар 1',
                    'meta' => '',
                    'realprice' => 83620,
                    'amount' => 1
                ],
                [
                    'material_id' => 11,
                    'name' => 'Товар 2',
                    'meta' => '',
                    'realprice' => 67175,
                    'amount' => 2
                ],
                [
                    'material_id' => 12,
                    'name' => 'Товар 3',
                    'meta' => '',
                    'realprice' => 71013,
                    'amount' => 3
                ]
            ],
        ]);
        $order->commit();
        $order->fields['full_name']->addValue('Тестовый пользователь');
        $order->fields['phone']->addValue('+7 999 000-00-00');
        $order->fields['email']->addValue('user@test.org');
        $order->fields['agree']->addValue('1');
        $interface = new CartInterface();

        $result = $interface->notifyOrder($order, null, false, true);

        $this->assertNull($result);

        Order::delete($order);
        $form->interface_id = 25;
        $form->commit();
    }


    /**
     * Тест обработки формы заказа
     */
    public function testProcessOrderForm()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $cart = new Cart(new Cart_Type(1));
        $interface = new CartInterface();
        $page = new Page(25);
        $post = [
            'full_name' => 'Test User',
            'phone' => '+7 999 000-00-00',
            'email' => 'test@test.org',
            '_description_' => 'Test order',
            'agree' => 1
        ];

        $result = $interface->processOrderForm($cart, $page, $post);

        $this->assertEmpty($result['Material']);
        $this->assertInstanceOf(Order::class, $result['Item']);
        $this->assertEquals(1, $result['Item']->pid);
        $this->assertEquals('Test User', $result['Item']->full_name);
        $this->assertEquals('Test order', $result['Item']->_description_);
        $this->assertCount(3, $result['Item']->items);
        $this->assertInstanceOf(Material::class, $result['Item']->items[2]);
        $this->assertEquals(11, $result['Item']->items[1]->id);
        $this->assertEquals('Товар 2', $result['Item']->items[1]->name);
        $this->assertEquals('aaa', $result['Item']->items[1]->meta);
        $this->assertEquals(67175, $result['Item']->items[1]->realprice);
        $this->assertEquals(2, $result['Item']->items[1]->amount);
        $this->assertEquals(0, $result['Item']->status_id);
        $this->assertEquals(0, $result['Item']->paid);

        Order::delete($result['Item']);
    }


    /**
     * Тест обработки формы заказа
     * (случай с удалением собственно заказа)
     */
    public function testProcessOrderFormWithOrderDeletion()
    {
        $form = new Form(3);
        $form->create_feedback = 0;
        $form->material_type = 7;
        $form->commit();
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $cart = new Cart(new Cart_Type(1));
        $interface = new CartInterface();
        $page = new Page(25);
        $post = [
            'full_name' => 'Test User',
            'phone' => '+7 999 000-00-00',
            'email' => 'test@test.org',
            '_description_' => 'Test order',
            'agree' => 1
        ];

        $result = $interface->processOrderForm($cart, $page, $post);

        $this->assertEmpty($result['Item']);
        $this->assertInstanceOf(Material::class, $result['Material']);
        $this->assertEquals(7, $result['Material']->pid);

        $form->create_feedback = 1;
        $form->material_type = 0;
        $form->commit();
        Material::delete($result['Material']);
    }


    /**
     * Тест отработки интерфейса
     * (случай установки количества товара)
     */
    public function testProcessWithSet()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $interface = new CartInterface(
            new Block_Cart(38),
            new Page(25),
            [
                'action' => 'set',
                'id' => 11,
                'meta' => 'aaa',
                'amount' => 20,
                'back' => 1,
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertEquals('history:back', $result);
        $this->assertEquals(1, $cartData[10]['']);
        $this->assertEquals(20, $cartData[11]['aaa']);
    }


    /**
     * Тест отработки интерфейса
     * (случай добавления количества товара)
     */
    public function testProcessWithAdd()
    {
        $_SERVER['REQUEST_URI'] = '/cart/?action=add&id=11&meta=aaa&amount=3';
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $interface = new CartInterface(
            new Block_Cart(38),
            new Page(25),
            [
                'action' => 'add',
                'id' => 11,
                'meta' => 'aaa',
                'amount' => 3,
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertEquals('?', $result);
        $this->assertEquals(1, $cartData[10]['']);
        $this->assertEquals(5, $cartData[11]['aaa']);
    }


    /**
     * Тест отработки интерфейса
     * (случай уменьшения количества товара)
     */
    public function testProcessWithReduce()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $interface = new CartInterface(
            new Block_Cart(38),
            new Page(25),
            [
                'action' => 'reduce',
                'id' => 11,
                'meta' => 'aaa',
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertEquals(1, $cartData[10]['']);
        $this->assertEquals(1, $cartData[11]['aaa']);
    }


    /**
     * Тест отработки интерфейса
     * (случай удаления товара)
     */
    public function testProcessWithDelete()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $interface = new CartInterface(
            new Block_Cart(38),
            new Page(25),
            [
                'action' => 'delete',
                'id' => 11,
                'meta' => 'aaa',
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertEquals(1, $cartData[10]['']);
        $this->assertEmpty($cartData[11]);
    }


    /**
     * Тест отработки интерфейса
     * (случай очистки корзины)
     */
    public function testProcessWithClear()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $interface = new CartInterface(
            new Block_Cart(38),
            new Page(25),
            [
                'action' => 'clear',
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertEquals([], $cartData);
    }


    /**
     * Тест отработки интерфейса
     * (случай отправки формы)
     */
    public function testProcess()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $epayInterface = new Snippet([
            'urn' => 'epay_test',
            'description' => '<' . '?php return ["aaa" => "bbb", "epayData" => $data];'
        ]);
        $epayInterface->commit();
        $block = new Block_Cart(38);
        $block->epay_interface_id = $epayInterface->id;

        $interface = new CartInterface(
            $block,
            new Page(25),
            [],
            [
                'full_name' => 'Test User',
                'phone' => '+7 999 000-00-00',
                'email' => 'test@test.org',
                '_description_' => 'Test order',
                'agree' => 1,
                'amount' => [
                    '10_' => 10,
                    '11_aaa' => 20,
                    '12_' => 30
                ],
                'form_signature' => md5('form338')
            ]
        );

        $preResult = $epayInterface->process(['ccc' => 'ddd']);
        $this->assertEquals(
            ['aaa' => 'bbb', 'epayData' => ['ccc' => 'ddd']],
            $preResult
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);

        $this->assertEmpty($result['Material']);
        $this->assertInstanceOf(Order::class, $result['Item']);
        $this->assertEquals(1, $result['Item']->pid);
        $this->assertEquals('Test User', $result['Item']->full_name);
        $this->assertEquals('Test order', $result['Item']->_description_);
        $this->assertCount(3, $result['Item']->items);
        $this->assertInstanceOf(Material::class, $result['Item']->items[2]);
        $this->assertEquals(11, $result['Item']->items[1]->id);
        $this->assertEquals('Товар 2', $result['Item']->items[1]->name);
        $this->assertEquals('aaa', $result['Item']->items[1]->meta);
        $this->assertEquals(67175, $result['Item']->items[1]->realprice);
        $this->assertEquals(10, $result['Item']->items[0]->amount);
        $this->assertEquals(20, $result['Item']->items[1]->amount);
        $this->assertEquals(30, $result['Item']->items[2]->amount);
        $this->assertEquals(0, $result['Item']->status_id);
        $this->assertEquals(0, $result['Item']->paid);
        $this->assertEquals([], json_decode($_COOKIE['cart_1'], true));
        $this->assertEquals('bbb', $result['aaa']);
        $this->assertEquals($result['Item'], $result['epayData']['Item']);
        $this->assertEquals($block->config, $result['epayData']['config']);
        $this->assertEquals($block, $result['epayData']['Block']);
        $this->assertEquals(25, $result['epayData']['Page']->id);
        $this->assertEquals('Test User', $result['DATA']['full_name']);
        $this->assertEquals([], $result['localError']);
        $this->assertEquals(3, $result['Form']->id);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));

        $block->epay_interface_id = 0;
        $block->commit();
        Order::delete($result['Item']);
        Snippet::delete($epayInterface);
    }


    /**
     * Тест отработки интерфейса
     * (случай без отправки формы)
     */
    public function testProcessWithoutPost()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $block = new Block_Cart(38);
        $form = $block->Cart_Type->Form;
        $fullNameField = $form->fields['full_name'];
        $fullNameField->defval = 'Test User';
        $fullNameField->commit();
        $interface = new CartInterface($block, new Page(25), [], []);

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);

        $this->assertEmpty($result['Material']);
        $this->assertEmpty($result['Item']);
        $this->assertEquals(['full_name' => 'Test User'], $result['DATA']);
        $this->assertEquals([], $result['localError']);
        $this->assertEquals(3, $result['Form']->id);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));

        $fullNameField->defval = '';
        $fullNameField->commit();
    }
}
