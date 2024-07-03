<?php
/**
 * Файл теста стандартного интерфейса корзины
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\Block;
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;
use RAAS\CMS\User;
use RAAS\CMS\Users\Module as UsersModule;

/**
 * Класс теста стандартного интерфейса корзины
 * @covers RAAS\CMS\Shop\CartInterface
 */
class CartInterfaceTest extends BaseTest
{
    public static $tables = [
        'cms_access',
        'cms_access_blocks_cache',
        'cms_access_materials_cache',
        'cms_access_pages_cache',
        'cms_blocks',
        'cms_blocks_material',
        'cms_blocks_pages_assoc',
        'cms_data',
        'cms_fields',
        'cms_forms',
        'cms_material_types',
        'cms_material_types_affected_pages_for_materials_cache',
        'cms_material_types_affected_pages_for_self_cache',
        'cms_materials',
        'cms_materials_affected_pages_cache',
        'cms_materials_pages_assoc',
        'cms_pages',
        'cms_shop_blocks_cart',
        'cms_shop_cart_types',
        'cms_shop_cart_types_material_types_assoc',
        'cms_shop_carts',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_orders_goods',
        'cms_shop_orders_history',
        'cms_shop_priceloaders',
        'cms_snippets',
        'cms_users', // Только для одиночного теста
        'cms_users_blocks_register',
        'cms_users_groups_assoc',
        'cms_users_social',
        'registry',
    ];


    public static function setUpBeforeClass(): void
    {
        ControllerFrontend::i()->exportLang(Application::i(), 'ru');
        ControllerFrontend::i()->exportLang(Package::i(), 'ru');
        ControllerFrontend::i()->exportLang(Module::i(), 'ru');
        ControllerFrontend::i()->exportLang(UsersModule::i(), 'ru');
        parent::setUpBeforeClass();
    }

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
        $additionalItems = [new CartItem([
            'name' => 'Доставка',
            'realprice' => 1000,
            'amount' => 1,
        ])];

        $result = $interface->processOrderItems($order, $cart, $additionalItems);

        $this->assertCount(4, $order->meta_items); // Товар без количества не учитывается
        $this->assertEquals(10, $order->meta_items[0]['material_id']);
        $this->assertEquals(11, $order->meta_items[1]['material_id']);
        $this->assertEquals(12, $order->meta_items[2]['material_id']);
        $this->assertEquals('Товар 1', $order->meta_items[0]['name']);
        $this->assertEquals(83620, $order->meta_items[0]['realprice']);
        $this->assertEquals(2, $order->meta_items[1]['amount']);
        $this->assertEquals('aaa', $order->meta_items[1]['meta']);
        $this->assertEquals('Доставка', $order->meta_items[3]['name']);
        $this->assertEquals(1000, $order->meta_items[3]['realprice']);

        $_COOKIE = [];
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

        $this->assertStringContainsString(date('d.m.Y H:i'), $result);
        $this->assertStringContainsString('Новый заказ корзины «Корзина» на странице «Корзина»', $result);
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

        $this->assertStringContainsString(date('d.m.Y H:i'), $result);
        $this->assertStringContainsString('Новый заказ #1 на сайте ДОМЕН.РФ', $result);
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
        $page = new Page(25);
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
        $order->fields['last_name']->addValue('Тестовый');
        $order->fields['first_name']->addValue('Пользователь');
        $order->fields['phone']->addValue('+7 999 000-00-00');
        $order->fields['email']->addValue('user@test.org');
        $order->fields['agree']->addValue('1');
        $material = new Material(7);

        $interface = new CartInterface();
        Package::i()->registrySet(
            'sms_gate',
            'http://smsgate/{{PHONE}}/{{TEXT}}/'
        );

        $result = $interface->notifyOrder($order, $material, true, true);

        $this->assertEquals(['test@test.org'], $result['emails']['emails']);
        $this->assertStringContainsString(
            'Новый заказ корзины «Корзина» на странице «Корзина»',
            $result['emails']['subject']
        );
        $this->assertStringContainsString('<div>', $result['emails']['message']);
        $this->assertStringContainsString('Телефон: +7 999 000-00-00', $result['emails']['message']);
        $this->assertStringContainsString('/admin/', $result['emails']['message']);
        $this->assertStringContainsString('<table', $result['emails']['message']);
        $this->assertStringContainsString('edit_material', $result['emails']['message']);
        $this->assertStringContainsString('Администрация сайта', $result['emails']['from']);
        $this->assertStringContainsString('info@', $result['emails']['fromEmail']);
        $this->assertEquals(
            ['79990000000@sms.test.org'],
            $result['smsEmails']['emails']
        );
        $this->assertStringContainsString(
            'Новый заказ корзины «Корзина» на странице «Корзина»',
            $result['smsEmails']['subject']
        );
        $this->assertStringNotContainsString('<div>', $result['smsEmails']['message']);
        $this->assertStringContainsString('Администрация сайта', $result['smsEmails']['from']);
        $this->assertStringContainsString('info@', $result['smsEmails']['fromEmail']);
        $this->assertStringContainsString('Телефон: +7 999 000-00-00', $result['smsEmails']['message']);
        $this->assertStringContainsString('smsgate/%2B79990000000/', $result['smsPhones'][0]);
        $this->assertStringContainsString(
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
        $page = new Page(25);
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
        $order->fields['last_name']->addValue('Тестовый');
        $order->fields['first_name']->addValue('Пользователь');
        $order->fields['phone']->addValue('+7 999 000-00-00');
        $order->fields['email']->addValue('user@test.org');
        $order->fields['agree']->addValue('1');
        $material = new Material(7);

        $interface = new CartInterface(null, null, [], [], [], [], ['HTTP_HOST' => 'xn--d1acufc.xn--p1ai']);
        Package::i()->registrySet('sms_gate', 'http://smsgate/{{PHONE}}/{{TEXT}}/');

        $result = $interface->notifyOrder($order, $material, false, true);

        $this->assertEquals(['user@test.org'], $result['emails']['emails']);
        $this->assertStringContainsString(
            'Новый заказ #1 на сайте ДОМЕН.РФ',
            $result['emails']['subject']
        );
        $this->assertStringContainsString('<div>', $result['emails']['message']);
        $this->assertStringContainsString('Телефон: +7 999 000-00-00', $result['emails']['message']);
        $this->assertStringNotContainsString('/admin/', $result['emails']['message']);
        $this->assertStringContainsString('/catalog/', $result['emails']['message']);
        $this->assertStringContainsString('<table', $result['emails']['message']);
        $this->assertStringNotContainsString('edit_material', $result['emails']['message']);
        $this->assertStringContainsString('Администрация сайта', $result['emails']['from']);
        $this->assertStringContainsString('info@', $result['emails']['fromEmail']);
        $this->assertEmpty($result['smsEmails'] ?? null);
        $this->assertEmpty($result['smsPhones'] ?? null); // Пока по SMS ничего не отправляем пользователю

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
        $order->fields['last_name']->addValue('Тестовый');
        $order->fields['first_name']->addValue('Пользователь');
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
     * Тест метода success()
     */
    public function testSuccess()
    {
        $interface = new CartInterface();
        $block = new Block_Cart(38);
        $order = new Order();
        $order->commit();

        $result = $interface->success($block, ['id' => $order->id, 'crc' => Application::i()->md5It($order->id)]);

        $this->assertTrue($result['success'][$block->id]);

        Order::delete($order);
    }


    /**
     * Тест метода success() - случай с неуказанным ID# заказа
     */
    public function testSuccessWithNoId()
    {
        $interface = new CartInterface();
        $block = new Block_Cart(38);
        $get = [];

        $result = $interface->success($block, []);

        $this->assertStringContainsString('не найден', $result['localError']['id']);
    }


    /**
     * Тест метода success() - случай с некорректной подписью
     */
    public function testSuccessWithInvalidCRC()
    {
        $interface = new CartInterface();
        $block = new Block_Cart(38);
        $get = [];

        $result = $interface->success($block, ['id' => 1]);

        $this->assertStringContainsStringIgnoringCase('контрольная сумма', $result['localError']['id']);
    }


    /**
     * Тест метода success() - случай с некорректным ID# заказа
     */
    public function testSuccessWithInvalidId()
    {
        $interface = new CartInterface();
        $block = new Block_Cart(38);

        $result = $interface->success($block, ['id' => 1, 'crc' => Application::i()->md5It(1)]);

        $this->assertStringContainsString('не найден', $result['localError']['id']);
    }


    /**
     * Тест метода success() - случай с электронной оплатой
     */
    public function testSuccessWithEPay()
    {
        $_POST = [];
        $interface = new CartInterface();
        $block = new Block_Cart(38);
        $order = new Order();
        $order->commit();
        $get = ['id' => $order->id, 'crc' => Application::i()->md5It($order->id), 'epay' => 1];

        $result = $interface->success($block, $get);

        $this->assertEquals(['Item' => $order], $result);
        $this->assertEquals(1, $_POST['epay']);
        $this->assertEquals(1, $interface->post['epay']);

        Order::delete($order);
        $_POST = [];
    }


    /**
     * Провайдер данных для метода testFindUser()
     * @return array <pre><code>[
     *     array POST-данные
     *     string[] Поля для нахождения пользователя
     *     int|null Ожидаемый ID# пользователя
     * ]</code></pre>
     */
    public function findUserDataProvider()
    {
        return [
            [['email' => 'test@test.org', 'login' => 'test2'], ['email', 'login'], 1],
            [['email' => 'test@test.org', 'login' => 'test2'], ['login', 'email'], 2],
            [['phone' => '+7 999 000-00-00'], ['phone'], 1], // Найдется ID#1, поскольку сортируются по ID#
            [['second_name' => 2], ['second_name'], 2],
            [['second_name' => 'aaa'], ['second_name'], null],
        ];
    }


    /**
     * Тест метода findUser()
     * @param array $post POST-данные
     * @param string[] $findBy Поля для нахождения пользователя
     * @param int|null $expected Ожидаемый ID# пользователя
     * @dataProvider findUserDataProvider
     */
    public function testFindUser(array $post, array $findBy, int $expected = null)
    {
        $interface = new CartInterface();

        $result = $interface->findUser($post, $findBy);

        if ($expected) {
            $this->assertEquals($expected, $result->id);
        } else {
            $this->assertNull($result);
        }
    }


    /**
     * Тест метода createUser()
     */
    public function testCreateUser()
    {
        $interface = new CartInterface();
        $block = Block::spawn(45); // Регистрация
        $block->email_as_login = 1; // Установим в e-mail качестве логина
        $page = new Page(30); // Регистрация
        $post = [
            'email' => '0945@test.org',
            'phone' => '+7 (999) 000-09-45',
            'last_name' => 'Тестовый',
            'first_name' => 'Пользователь',
            'second_name' => '0945',
        ];
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'User agent',
        ];
        $files = [];

        $result = $interface->createUser($block, $page, $post, $server, $files);

        $user = new User($result->id);

        $this->assertNotEmpty($user->id);
        $this->assertEquals('0945@test.org', $user->email);
        $this->assertEquals('0945@test.org', $user->login);
        $this->assertEquals('+7 (999) 000-09-45', $user->phone);
        $this->assertEquals('Тестовый', $user->last_name);
        $this->assertEquals('Пользователь', $user->first_name);
        $this->assertEquals('0945', $user->second_name);
        $this->assertTrue($result->new);

        User::delete($user);
    }


    /**
     * Тест метода createUser() - случай без e-mail
     */
    public function testCreateUserWithNoEmail()
    {
        $interface = new CartInterface();
        $block = Block::spawn(45); // Регистрация
        $page = new Page(30); // Регистрация
        $post = [
            'phone' => '+7 (999) 000-09-45',
            'last_name' => 'Тестовый',
            'first_name' => 'Пользователь',
            'second_name' => '0945',
        ];
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'User agent',
        ];
        $files = [];

        $result = $interface->createUser($block, $page, $post, $server, $files);

        $this->assertNull($result);
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
        $block = Block::spawn(38);
        $block->params = 'bindUserBy[]=phone&createUserBlockId=45';
        $interface = new CartInterface($block);
        $interface->additionalsCallback = function (Cart $cart, array $post = [], User $user = null) {
            return [
                'items' => [new CartItem([
                    'name' => 'Доставка',
                    'realprice' => 1000,
                    'amount' => 1,
                ])],
            ];
        };
        $page = new Page(25);
        $post = [
            'last_name' => 'Тестовый',
            'first_name' => 'Пользователь',
            'second_name' => '1030',
            'phone' => '+7 (999) 000-10-30',
            'email' => '1030@test.org',
            '_description_' => 'Test order',
            'agree' => 1,
        ];

        $result = $interface->processOrderForm($cart, $page, $post);

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertInstanceOf(Order::class, $result['Item']);
        $this->assertEquals(1, $result['Item']->pid);
        $this->assertEquals('Тестовый', $result['Item']->last_name);
        $this->assertEquals('Пользователь', $result['Item']->first_name);
        $this->assertEquals('1030', $result['Item']->second_name);
        $this->assertEquals('Test order', $result['Item']->_description_);
        $this->assertCount(4, $result['Item']->items); // Товар без количества не учитывается
        $this->assertInstanceOf(Material::class, $result['Item']->items[2]);
        $this->assertEquals(11, $result['Item']->items[1]->id);
        $this->assertEquals('Товар 2', $result['Item']->items[1]->name);
        $this->assertEquals('aaa', $result['Item']->items[1]->meta);
        $this->assertEquals(67175, $result['Item']->items[1]->realprice);
        $this->assertEquals(2, $result['Item']->items[1]->amount);
        $this->assertEquals('Доставка', $result['Item']->items[3]->name);
        $this->assertEquals(1000, $result['Item']->items[3]->realprice);
        $this->assertEquals(0, $result['Item']->status_id);
        $this->assertEquals(0, $result['Item']->paid);

        $this->assertNotEmpty($result['Item']->uid);
        $this->assertNotEmpty($result['user']->id);
        $this->assertEquals($result['user']->id, $result['Item']->uid);
        $this->assertEquals('1030@test.org', $result['user']->email);
        $this->assertEquals('1030@test.org', $result['user']->login);
        $this->assertEquals('+7 (999) 000-10-30', $result['user']->phone);
        $this->assertEquals('Тестовый', $result['user']->last_name);
        $this->assertEquals('Пользователь', $result['user']->first_name);
        $this->assertEquals('1030', $result['user']->second_name);

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
            'last_name' => 'Тестовый',
            'first_name' => 'Пользователь',
            'phone' => '+7 999 000-00-00',
            'email' => 'test@test.org',
            '_description_' => 'Test order',
            'agree' => 1
        ];

        $result = $interface->processOrderForm($cart, $page, $post);

        $this->assertEmpty($result['Item'] ?? null);
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

        $this->assertEquals('history:back', $result['redirectUrl']);
        $this->assertEquals(1, $cartData[10]['']);
        $this->assertEquals(20, $cartData[11]['aaa']);

        $_COOKIE['cart_1'] = '';
    }


    /**
     * Тест отработки интерфейса - случай установки количества товара массивом с очисткой
     */
    public function testProcessWithSetArrayAndClear()
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
                'id' => ['11_aaa' => 11],
                'clear' => 1,
                'back' => 1,
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertNull($cartData[10][''] ?? null);
        $this->assertEquals(11, $cartData[11]['aaa']);
        $this->assertNull($cartData[12][''] ?? null);
        $this->assertNull($cartData[13][''] ?? null);

        $_COOKIE['cart_1'] = '';
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

        $this->assertEquals('?', $result['redirectUrl']);
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
        $this->assertEmpty($cartData[11] ?? null);

        $_COOKIE['cart_1'] = '';
    }


    /**
     * Тест отработки интерфейса - случай удаления товара массивом
     */
    public function testProcessWithDeleteArray()
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
                'id' => ['11_aaa' => 11],
                'back' => 1,
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);
        $cartData = json_decode($_COOKIE['cart_1'], true);

        $this->assertEquals(1, $cartData[10]['']);
        $this->assertNull($cartData[11]['aaa'] ?? null);

        $_COOKIE['cart_1'] = '';
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
     * Тест метода process() - случай success с платежом
     */
    public function testProcessWithEPaySuccess()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $snippet = new Snippet([
            'urn' => 'epay_test',
            'description' => '<' . "?php return ['success' => [38 => true]]; "
        ]);
        $snippet->commit();
        $block = new Block_Cart(38);
        $block->epay_interface_id = $snippet->id;
        $interface = new CartInterface(
            $block,
            new Page(25),
            [
                'action' => 'success',
            ]
        );

        $result = $interface->process(true);

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertEmpty($result['Item'] ?? null);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));
        $this->assertEquals('$cart->clear();', $result['@debug.action']);
        $this->assertEquals(true, $result['success'][38]);

        $_COOKIE['cart_1'] = '';

        Snippet::delete($snippet);
    }


    /**
     * Тест метода process() - случай success с платежом и указанным классом платежного интерфейса
     */
    public function testProcessWithEPaySuccessAndEPayInterfaceClassname()
    {
        $_COOKIE['cart_1'] = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $block = new Block_Cart(38);
        $block->epay_interface_classname = MockEPayInterface::class;
        $interface = new CartInterface(
            $block,
            new Page(25),
            [
                'action' => 'success',
            ]
        );

        $result = $interface->process(true);

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertEmpty($result['Item'] ?? null);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));
        $this->assertEquals(true, $result['success'][38]);
        $this->assertEquals('$cart->clear();', $result['@debug.action']);

        $_COOKIE['cart_1'] = '';
    }


    /**
     * Тест метода process() - случай success с установкой данных из электронной оплаты без успеха электронной оплаты
     */
    public function testProcessWithSuccessAndNotEPaySuccess()
    {
        $cartData = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $_COOKIE['cart_1'] = $cartData;
        $snippet = new Snippet(['urn' => 'epay_test', 'description' => '<' . "?php return ['aaa' => 'bbb']; "]);
        $snippet->commit();
        $block = new Block_Cart(38);
        $block->epay_interface_id = $snippet->id;
        $interface = new CartInterface(
            $block,
            new Page(25),
            [
                'action' => 'success',
            ]
        );

        $result = $interface->process();

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertEmpty($result['Item'] ?? null);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));
        $this->assertEquals('bbb', $result['aaa']);
        $this->assertEquals($cartData, $_COOKIE['cart_1']);

        $_COOKIE['cart_1'] = '';
    }


    /**
     * Тест метода process() - случай refresh и установленными additionalsCallback
     */
    public function testProcessWithRefreshAndAdditionals()
    {
        $cartData = json_encode([
            '10' => ['' => 1],
            '11' => ['aaa' => 2],
            '12' => ['' => 3],
            '13' => ['' => 0],
        ]);
        $_COOKIE['cart_1'] = $cartData;
        $block = new Block_Cart(38);
        $interface = new CartInterface(
            $block,
            new Page(25),
            [
                'action' => 'refresh',
            ]
        );
        $interface->additionalsCallback = function (Cart $cart, array $post = [], User $user = null) {
            return ['aaa' => ['bbb' => 'ccc']];
        };

        $result = $interface->process();

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertEmpty($result['Item'] ?? null);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));
        $this->assertEquals($cartData, $_COOKIE['cart_1']);
        $this->assertEquals('ccc', $result['additional']['aaa']['bbb']);

        $_COOKIE['cart_1'] = '';
    }


    /**
     * Тест метода getAdditionals()
     */
    public function testGetAdditionals()
    {
        $interface = new CartInterface();
        $interface->additionalsCallback = function (Cart $cart, array $post = [], User $user = null) {
            return ['aaa' => ['bbb' => 'ccc']];
        };
        $cart = new Cart(new Cart_Type(1));
        $result = $interface->getAdditionals($cart, [], null);

        $this->assertEquals(['aaa' => ['bbb' => 'ccc']], $result);
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
                'last_name' => 'Тестовый',
                'first_name' => 'Пользователь',
                'phone' => '+7 999 000-00-00',
                'email' => 'test@test.org',
                '_description_' => 'Test order',
                'agree' => 1,
                'amount' => [
                    '10_' => 10,
                    '11_aaa' => 20,
                    '12_' => 30
                ],
                'form_signature' => md5('form338'),
                'AJAX' => 1, // Для предотвращения редиректа
            ]
        );

        $preResult = $epayInterface->process(['ccc' => 'ddd']);
        $this->assertEquals(
            ['aaa' => 'bbb', 'epayData' => ['ccc' => 'ddd']],
            $preResult
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertInstanceOf(Order::class, $result['Item']);
        $this->assertEquals(1, $result['Item']->pid);
        $this->assertEquals('Тестовый', $result['Item']->last_name);
        $this->assertEquals('Пользователь', $result['Item']->first_name);
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
        $this->assertEquals('Тестовый', $result['DATA']['last_name']);
        $this->assertEquals('Пользователь', $result['DATA']['first_name']);
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
     * Тест отработки интерфейса - случай отправки формы без AJAX-редиректа
     */
    public function testProcessWithoutAJAXRedirect()
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
                'last_name' => 'Тестовый',
                'first_name' => 'Пользователь',
                'phone' => '+7 999 000-00-00',
                'email' => 'test@test.org',
                '_description_' => 'Test order',
                'epay' => 1,
                'agree' => 1,
                'amount' => [
                    '10_' => 10,
                    '11_aaa' => 20,
                    '12_' => 30
                ],
                'form_signature' => md5('form338'),
            ]
        );

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);

        $this->assertStringContainsString('?action=success&id=', $result['redirectUrl']);
        $this->assertStringContainsString('&crc=', $result['redirectUrl']);
        $this->assertStringContainsString('&epay=1', $result['redirectUrl']);
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
        $lastNameField = $form->fields['last_name'];
        $lastNameField->defval = 'Тестовый';
        $lastNameField->commit();
        $interface = new CartInterface($block, new Page(25), [], []);

        // Комментируем ошибки из-за setcookie после вывода текста
        $result = @$interface->process(true);

        $this->assertEmpty($result['Material'] ?? null);
        $this->assertEmpty($result['Item'] ?? null);
        $this->assertEquals(['last_name' => 'Тестовый'], $result['DATA']);
        $this->assertEquals([], $result['localError']);
        $this->assertEquals(3, $result['Form']->id);
        $this->assertEquals(1, $result['Cart']->cartType->id);
        $this->assertEquals(1, $result['Cart_Type']->id);
        $convertMeta = $result['convertMeta'];
        $this->assertEquals('aaa', $convertMeta('aaa'));

        $lastNameField->defval = '';
        $lastNameField->commit();
    }


    /**
     * Тест метода process - случай с повтором заказа
     */
    public function testProcessWithOrderRepeat()
    {
        $order = new Order([
            'pid' => 1,
            'uid' => 1,
            'meta_items' => [
                ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
                ['material_id' => 11, 'name' => 'Товар 2', 'meta' => 'aaa', 'realprice' => 2000, 'amount' => 2],
                ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
            ],
        ]);
        $order->commit();
        $user = new User(1);
        ControllerFrontend::i()->user = $user;

        $block = new Block_Cart(38);
        $interface = new CartInterface($block, new Page(25), ['repeat_order' => $order->id]);
        $result = $interface->process(true);

        $this->assertEquals('test@test.org', $result['DATA']['email']);
        $this->assertEquals('Тестовый', $result['DATA']['last_name']);
        $this->assertInstanceOf(Cart::class, $result['Cart']);
        $this->assertCount(3, $result['Cart']->items);
        $this->assertEquals(11, $result['Cart']->items[1]->id);
        $this->assertEquals('aaa', $result['Cart']->items[1]->meta);
        $this->assertEquals(2, $result['Cart']->items[1]->amount);

        Order::delete($order);
        ControllerFrontend::i()->user = new User();
    }
}
