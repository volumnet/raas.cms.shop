<?php
/**
 * Файл стандартного интерфейса корзины
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\HTTP;
use SOME\Text;
use RAAS\Application;
use RAAS\Controller_Frontend as RAASControllerFrontend;
use RAAS\Redirector;
use RAAS\View_Web as RAASViewWeb;
use RAAS\CMS\BlockInterface;
use RAAS\CMS\Block;
use RAAS\CMS\DiagTimer;
use RAAS\CMS\FormInterface;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypesRecursiveCache;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\User;
use RAAS\CMS\User_Field;

/**
 * Класс стандартного интерфейса корзины
 */
class CartInterface extends FormInterface
{
    /**
     * Функция расчета дополнительных пунктов для корзины
     * @var callable|null <pre>function (
     *     Cart $cart Корзина,
     *     array $post POST-данные,
     *     User $user Пользователь
     * ): [
     *     'items' => array<CartItem>,
     *     string[] => mixed Дополнительные данные
     * ]</pre>
     */
    public $additionalsCallback = null;

    /**
     * Конструктор класса
     * @param ?Block_Cart $block Блок, для которого применяется
     *                               интерфейс
     * @param ?Page $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     * @param array $files Поля $_FILES параметров
     */
    public function __construct(
        ?Block_Cart $block = null,
        ?Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        array $files = []
    ) {
        BlockInterface::__construct(
            $block,
            $page,
            $get,
            $post,
            $cookie,
            $session,
            $server,
            $files
        );
    }


    /**
     * Отрабатывает интерфейс
     * @param bool $debug Режим отладки
     * @return array
     */
    public function process(bool $debug = false): array
    {
        $result = [];
        $cartType = new Cart_Type((int)$this->block->cart_type);
        $user = RAASControllerFrontend::i()->user;
        $cart =@ new Cart($cartType, $user); // Для подавления ошибки "...headers already sent"
        $action = $this->get['action'] ?? '';

        switch ($action) {
            case 'set':
            case 'add':
            case 'reduce':
            case 'delete':
                if (isset($this->get['id'])) {
                    $items = [];
                    if (is_array($this->get['id'])) {
                        foreach ($this->get['id'] as $idMeta => $amount) {
                            list($id, $meta) = explode('_', (string)$idMeta);
                            $id = (int)$id;
                            $material = new Material((int)$id);
                            $amount = (int)$amount;
                            if ($action == 'delete') {
                                $amount = 0;
                            }
                            $items[] = ['material' => $material, 'amount' => $amount, 'meta' => $meta];
                        }
                    } else {
                        $id = isset($this->get['id']) ? (int)$this->get['id'] : 0;
                        $material = new Material((int)$id);
                        $meta = isset($this->get['meta']) ? $this->get['meta'] : '';
                        $amount = 0;
                        if (($action != 'delete') && isset($this->get['amount'])) {
                            $amount = (int)$this->get['amount'];
                        }
                        $items[] = ['material' => $material, 'amount' => $amount, 'meta' => $meta];
                    }
                    if (($action == 'set') && (($this->get['clear'] ?? false) || ($this->get['init'] ?? false))) {
                        $cart->clear();
                    }
                    foreach ($items as $item) {
                        if ($item['material']->id) {
                            switch ($action) {
                                case 'set':
                                case 'delete':
                                    $cart->set($item['material'], $item['amount'], $item['meta']);
                                    break;
                                case 'add':
                                    $cart->add($item['material'], $item['amount'] ?: 1, $item['meta']);
                                    break;
                                case 'reduce':
                                    $cart->reduce($item['material'], $item['amount'] ?: 1, $item['meta']);
                                    break;
                            }
                        }
                    }
                }
                break;
            case 'clear':
                $cart->clear();
                break;
            case 'success':
                $result = array_merge($result, $this->success($this->block, $this->get));
                break;
            case 'refresh':
                break;
            default:
                $orderToRepeat = null;
                if ($user->id && ($this->get['repeat_order'] ?? 0)) {
                    $temp = new Order($this->get['repeat_order']);
                    if ($temp->uid == $user->id) {
                        $orderToRepeat = $temp;
                    }
                }
                $form = $cartType->Form;
                if (isset($this->post['amount'])) {
                    foreach ((array)$this->post['amount'] as $key => $val) {
                        list($id, $meta) = explode('_', $key);
                        $material = new Material($id);
                        $cart->set($material, (int)$val, $meta);
                    }
                } elseif ($orderToRepeat) {
                    $orderToRepeat->items;
                    foreach ($orderToRepeat->items as $itemToRepeat) {
                        $cart->set($itemToRepeat, (int)$itemToRepeat->amount, $itemToRepeat->meta, false);
                    }
                    @$cart->save(); // Для подавления ошибки "...headers already sent"
                }
                if ($form->id && $cart->items) {
                    $localError = [];
                    if ($this->isFormProceed($this->block, $form, $this->server['REQUEST_METHOD'] ?? '', $this->post)) {
                        // 2019-10-02, AVS: добавили для совместимости с виджетом, где даже
                        // в случае ошибок проверяется соответствие
                        // ($Item instanceof Order)
                        // 2019-11-14, AVS: перенес сюда, иначе при AJAX-запросе
                        // первая попавшаяся форма отключает
                        $result['Item'] = $this->getRawOrder($cart);

                        // Проверка полей на корректность
                        $localError = $this->check($form, $this->post, $this->session, $this->files);

                        if (!$localError) {
                            $result = array_merge(
                                $result,
                                $this->processOrderForm($cart, $this->page, $this->post, $this->server, $this->files)
                            );
                            if (($this->post['epay'] ?? null) != 1) {
                                $cart->clear();
                                $result['success'][(int)$this->block->id] = true;
                                // 2022-07-12, AVS: сделал проверку, чтобы по AJAX'у не редиректил
                            }
                            // 2024-05-01, AVS: редирект доступен только без электронной оплаты (там свой редирект)
                            // 2024-05-01, AVS: ОТМЕНА: при success() устанавливается $Item и $_POST['epay'],
                            //     а дальше вызывается редирект на платежный шлюз
                            if (!(($this->post['AJAX'] ?? null) || ($this->page->mime == 'application/json'))) {
                                $url = '?action=success&id=' . (int)$result['Item']->id
                                    . '&crc=' . Application::i()->md5It($result['Item']->id);
                                if ($this->post['epay'] ?? null) {
                                    $url .= '&epay=' . $this->post['epay'];
                                }
                                if ($debug) {
                                    return ['redirectUrl' => $url];
                                // @codeCoverageIgnoreStart
                                // Не можем проверить редирект
                                } else {
                                    new Redirector($url);
                                }
                                // @codeCoverageIgnoreEnd
                            }
                        }
                        $result['DATA'] = $this->post;
                        $result['localError'] = $localError;
                    } else {
                        $result['DATA'] = [];
                        foreach ($form->visFields as $fieldURN => $row) {
                            // 2023-04-18, AVS: убрано, сомнительно по версии клиента
                            // if ($orderToRepeat && trim($orderVal = (string)$orderToRepeat->{$fieldURN})) {
                            //     $result['DATA'][$fieldURN] = $orderVal;
                            // } else {
                            $userVal = $user->{$fieldURN};
                            if ((is_scalar($userVal) && (trim((string)$userVal) !== '')) ||
                                (!is_scalar($userVal) && $userVal)
                            ) {
                                $result['DATA'][$fieldURN] = $userVal;
                            } elseif ($row->defval) {
                                $result['DATA'][$fieldURN] = $row->defval;
                            }
                            // }
                            // 2022-10-13, AVS: поменял порядок следования (чтобы заполнялось),
                            // также сделал строгую проверку на непустую строку
                        }
                        $result['localError'] = [];
                    }
                }
                $result['Form'] = $form;
                break;
        }
        if ((isset($this->get['back']) && $this->get['back']) ||
            (
                in_array($action, ['set', 'add', 'reduce', 'delete', 'clear']) &&
                !($this->get['AJAX'] ?? false) // 2020-12-25, AVS: добавлено, чтобы не редиректило в AJAX'е
            )
        ) {
            if ($this->get['back'] ?? false) {
                $url = 'history:back';
            } else {
                $url = HTTP::queryString('action=&id=&meta=&amount=&clear=') ?: '?';
            }
            if ($debug) {
                return ['redirectUrl' => $url];
            // @codeCoverageIgnoreStart
            // Не можем проверить редирект
            } else {
                new Redirector($url);
            }
            // @codeCoverageIgnoreEnd
        }

        $result['Cart'] = $cart;
        $result['Cart_Type'] = $cartType;
        $result['convertMeta'] = [$this, 'convertMeta'];
        $result['interface'] = $this;
        if ($additional = $this->getAdditionals($cart, $this->post, $user)) {
            $result['additional'] = $additional;
        }
        if ($this->block->epay_interface_classname || $this->block->EPay_Interface->id) {
            if ($epayInterfaceClassname = $this->block->epay_interface_classname) {
                $epayInterface = new $epayInterfaceClassname(
                    $this->block,
                    $this->page,
                    $this->get,
                    $this->post,
                    $this->cookie,
                    $this->session,
                    $this->server
                );
                $epayResult = $epayInterface->process($result['Item'] ?? null);
            } else {
                $epayResult = $this->block->EPay_Interface->process(array_merge(
                    [
                        'Block' => $this->block,
                        'Page' => $this->page,
                        'config' => $this->block->config,
                    ],
                    $result
                ));
            }
            if ($epayResult['success'][$this->block->id] ?? null) {
                if ($debug) {
                    $result['@debug.action'] = '$cart->clear();';
                // @codeCoverageIgnoreStart
                // Не можем проверить установку корзины - Cannot modify header information - headers already sent
                } else {
                    $cart->clear();
                }
                // @codeCoverageIgnoreEnd
            }
            $result = array_merge($result, $epayResult);
        }
        return $result;
    }


    /**
     * Функция расчета дополнительных пунктов для корзины
     * @param Cart $cart Корзина
     * @param array $post POST-данные
     * @param ?User $user Пользователь
     * @return array <pre>[
     *     'items' => array<CartItem>,
     *     string[] => mixed Дополнительные данные
     * ]</pre>
     */
    public function getAdditionals(Cart $cart, array $post = [], ?User $user = null): array
    {
        if ($additionalsCallback = $this->additionalsCallback) {
            $result = $additionalsCallback($cart, $this->post, $user);
            return $result;
        }
        return [];
    }


    /**
     * Обрабатывает страницу успешной отправки заказа
     * (для предотвращения повторной отправки)
     * @param Block_Cart $block Текущий блок
     * @param array $get Данные $_GET-полей
     * @return array
     */
    public function success(Block_Cart $block, array $get): array
    {
        $result = [];
        $localError = [];
        if (!($get['id'] ?? null)) {
            $localError['id'] = View_Web::i()->_('ORDER_NOT_FOUND');
        } elseif (($get['crc'] ?? null) != Application::i()->md5It($get['id'])) {
            $localError['id'] = View_Web::i()->_('ORDER_CRC_IS_INVALID');
        } else {
            $order = new Order($get['id']);
            if ($order->id != $get['id']) {
                $localError['id'] = View_Web::i()->_('ORDER_NOT_FOUND');
            }
        }
        if ($localError) {
            $result['localError'] = $localError;
        } else {
            $result['Item'] = $order;
            if ($get['epay'] ?? null) {
                $_POST['epay'] = $this->post['epay'] = $get['epay'];
            } else {
                $result['success'][(int)$block->id] = true;
            }
        }
        return $result;
    }


    /**
     * Обрабатывает форму заказа
     * @param Cart $cart Корзина
     * @param ?Page $page Текущая страница
     * @param array $post Данные $_POST-полей
     * @param array $server Данные $_SERVER-полей
     * @param array $files Данные $_FILES-полей
     * @return array <pre><code>[
     *     'Item' =>? Order Заказ
     *     'Material' =>? Material Созданный материал
     * ]</code></pre>
     */
    public function processOrderForm(
        Cart $cart,
        ?Page $page = null,
        array $post = [],
        array $server = [],
        array $files = []
    ): array {
        $order = $this->getRawOrder($cart);
        // Для AJAX'а
        $this->processFeedbackReferer($order, $page, $server);
        $user = RAASControllerFrontend::i()->user;
        $additionalParams = [];
        if ($this->block && $this->block->additionalParams) {
            $additionalParams = (array)$this->block->additionalParams;
        }
        if (!$user->id && ($additionalParams['bindUserBy'] ?? null)) {
            $user = $this->findUser($post, (array)$additionalParams['bindUserBy']);
            if (!($user && $user->id) && ($additionalParams['createUserBlockId'] ?? null)) {
                $registerBlock = Block::spawn($additionalParams['createUserBlockId']);
                $user = $this->createUser($registerBlock, $page, $post, $server, $files);
            }
        }
        $order->uid = ($user instanceof User) ? (int)$user->id : 0;
        $this->processUserData($order, $server);

        $objects = [$order];
        $form = $order->parent->Form;
        if ($material = $this->getRawMaterial($form)) {
            if (!$form->Material_Type->global_type) {
                $material->cats = [(int)$order->page_id];
            }
            $objects[] = $material;
        }

        $additional = $this->getAdditionals($cart, $post, $user);
        $additionalItems = [];
        if ($additional['items'] ?? null) {
            $additionalItems = (array)$additional['items'];
        }
        $this->processOrderItems($order, $cart, $additionalItems);

        foreach ($objects as $object) {
            $this->processObject($object, $form, $post, $server, $files);
        }

        $this->notifyOrder($order, $material);
        $this->notifyOrder($order, $material, true);

        if (!$form->create_feedback) {
            Order::delete($order);
            $order = null;
        }
        $result = ['Item' => $order, 'Material' => $material];
        if ($user ?? null) {
            $result['user'] = $user;
        }
        return $result;
    }


    /**
     * Пытается найти пользователя по данным
     * @param array $post POST-данные
     * @param string $findBy Массив URN полей пользователя в порядке поиска
     * @return User|null
     */
    public function findUser(array $post, array $findBy)
    {
        $userFields = null;
        foreach ($findBy as $fieldURN) {
            $localVal = trim((string)$post[$fieldURN]);
            if ($localVal) {
                if (in_array($fieldURN, ['login', 'email'])) {
                    $sqlQuery = "SELECT *
                                   FROM " . User::_tablename()
                              . " WHERE " . $fieldURN . " = ?";
                    $sqlBind = [$localVal];
                    $sqlResult = User::_SQL()->getline([$sqlQuery, $sqlBind]);
                    if ($sqlResult) {
                        $result = new User($sqlResult);
                        return $result;
                    }
                } else {
                    if ($userFields === null) {
                        $userFieldsTmp = User_Field::getSet();
                        $userFields = [];
                        foreach ($userFieldsTmp as $userField) {
                            $userFields[trim((string)$userField->urn)] = $userField;
                        }
                    }
                    if ($userField = $userFields[$fieldURN]) {
                        if (($userField->datatype == 'tel') ||
                            ($fieldURN == 'phone')
                        ) {
                            $sqlQuery = "SELECT tU.*, tD.value AS tmpval
                                           FROM " . User::_tablename() . " AS tU
                                           JOIN cms_data AS tD ON tD.pid = tU.id
                                          WHERE tD.fid = ?
                                            AND tD.value LIKE ?
                                          ORDER BY id";
                            $localVal = Text::beautifyPhone($localVal);
                            $sqlBind = [
                                (int)$userField->id,
                                '%' . implode('%', str_split($localVal)) . '%'
                            ];
                            $sqlResult = User::_SQL()->get([$sqlQuery, $sqlBind]);
                            foreach ($sqlResult as $sqlRow) {
                                $remoteVal = Text::beautifyPhone($sqlRow['tmpval']);
                                if ($remoteVal == $localVal) {
                                    unset($sqlRow['tmpval']);
                                    $result = new User($sqlRow);
                                    return $result;
                                }
                            }
                        } else {
                            $sqlQuery = "SELECT tU.*, tD.value AS tmpval
                                           FROM " . User::_tablename() . " AS tU
                                           JOIN cms_data AS tD ON tD.pid = tU.id
                                          WHERE tD.fid = ?
                                            AND tD.value = ?
                                          ORDER BY id";
                            $sqlBind = [(int)$userField->id, $localVal];
                            $sqlResult = User::_SQL()->getline([$sqlQuery, $sqlBind]);
                            if ($sqlResult) {
                                $result = new User($sqlResult);
                                return $result;
                            }
                        }
                    }
                }
            }
        }
        return null;
    }


    /**
     * Создает нового пользователя на основании POST-данных
     * @param Block $block Блок регистрации
     * @param Page $page Текущая страница
     * @param array $post Данные $_POST-полей
     * @param array $server Данные $_SERVER-полей
     * @param array $files Данные $_FILES-полей
     * @return User|null
     */
    public function createUser(
        Block $registerBlock,
        Page $page,
        array $post = [],
        array $server = [],
        array $files = []
    ) {
        $classname = 'RAAS\\CMS\\Users\\RegisterInterface';
        // @codeCoverageIgnoreStart
        // Не сможем в рамках тестового окружения проверить отсутствие
        if (!class_exists($classname)) {
            return null;
        }
        // @codeCoverageIgnoreEnd
        if (!($post['email'] ?? null)) {
            return null; // Не можем создать пользователя если нет e-mail
        }
        $registerInterface = new $classname($registerBlock, $page, [], $post, [], [], $server, $files);
        $result = new User();
        $result = $registerInterface->processRegisterForm(
            new User(),
            $registerBlock,
            $registerBlock->Register_Form,
            $page,
            $post,
            [],
            $server,
            $files
        );
        $user = $result['User'];
        $user->new = true;
        // 2024-04-04, AVS: убрал if ($user->id) и return null, т.к. в processRegisterForm пользователь коммитится явно
        return $user;
    }


    /**
     * Получает "сырой" заказ (без commit'а и заполненных полей)
     * @param Cart $cart Корзина
     * @return Order
     */
    public function getRawOrder(Cart $cart): Order
    {
        return new Order(['pid' => (int)$cart->cartType->id]);
    }


    /**
     * Добавляет товары в заказ из корзины
     * @param Order $order Заказ
     * @param Cart $cart Корзина
     * @param CartItem[] $additionalItems Дополнительные элементы для добавления
     *     в корзину
     */
    public function processOrderItems(Order $order, Cart $cart, array $additionalItems = [])
    {
        $orderItems = [];
        $user = Controller_Frontend::i()->user;
        foreach ($cart->items as $cartItem) {
            if ($cartItem->amount) {
                $material = new Material($cartItem->id); // 2024-12-26, AVS: Сделано для совместимости со старыми корзинами, где товары это stdObject
                $price = $cart->getPrice($material, (float)$cartItem->amount, $cartItem->metaJSON);
                $orderItems[] = [
                    'material_id' => $cartItem->id,
                    'name' => $cartItem->name,
                    'meta' => $this->convertMeta($cartItem->meta),
                    'realprice' => (float)$price,
                    'amount' => (float)$cartItem->amount,
                ];
            }
        }
        if ($additionalItems) {
            foreach ((array)$additionalItems as $cartItem) {
                $orderItems[] = [
                    'material_id' => $cartItem->id,
                    'name' => $cartItem->name,
                    'meta' => $this->convertMeta($cartItem->meta),
                    'realprice' => (float)$cartItem->realprice,
                    'amount' => (int)$cartItem->amount,
                ];
            }
        }
        $order->meta_items = $orderItems;
    }


    /**
     * Уведомление о заказе
     * @param Order $order Заказ
     * @param ?Material $material Созданный материал
     * @param bool $forAdmin Уведомление для администратора
     *                       (если нет, то для пользователя)
     * @param bool $debug Режим отладки
     * @return array<
     *             ('emails'|'smsEmails')[] => [
     *                 'emails' => array<string> e-mail адреса,
     *                 'subject' => string Тема письма,
     *                 'message' => string Тело письма,
     *                 'from' => string Поле "от",
     *                 'fromEmail' => string Обратный адрес
     *             ],
     *             'smsPhones' => array<string URL SMS-шлюза>
     *         >|null Набор отправляемых писем либо URL SMS-шлюза
     *                            (только в режиме отладки)
     */
    public function notifyOrder(
        Order $order,
        ?Material $material = null,
        bool $forAdmin = false,
        bool $debug = false
    ) {
        $form = $order->parent->Form;
        if (!$form->Interface->id) {
            return;
        }
        if ($forAdmin) {
            $addresses = $this->parseFormAddresses($form);
        } else {
            $addresses = $this->parseUserAddresses($order);
            // Пока не отправляем пользователям уведомления по SMS
            // @todo Сделать флажок у формы
            // "отправлять уведомления пользователям по SMS"
            unset($addresses['smsPhones']);
        }
        $template = $form->Interface;

        $notificationData = [
            'Item' => $order,
            'Material' => $material,
            'Form' => $form,
            'ADMIN' => $forAdmin,
            'forUser' => !$forAdmin,
            'cartInterface' => $this,
        ];

        $subject = $this->getEmailOrderSubject($order, $forAdmin);
        $message = $this->getMessageBody(
            $template,
            array_merge($notificationData, ['SMS' => false])
        );
        $smsMessage = $this->getMessageBody(
            $template,
            array_merge($notificationData, ['SMS' => true])
        );
        $fromName = $this->getFromName();
        $fromEmail = $this->getFromEmail();
        $debugMessages = [];
        $attachments = $this->getAttachments($order, $material, $forAdmin);

        $processEmbedded = $this->processEmbedded($message);
        $message = Text::inlineCSS($processEmbedded['message']);
        $embedded = (array)$processEmbedded['embedded'];

        if ($emails = ($addresses['emails'] ?? null)) {
            if ($debug) {
                $debugMessages['emails'] = [
                    'emails' => $emails,
                    'subject' => $subject,
                    'message' => $message,
                    'from' => $fromName,
                    'fromEmail' => $fromEmail,
                    'attachments' => $attachments,
                    'embedded' => $embedded,
                ];
            // @codeCoverageIgnoreStart
            // Не можем проверить отправку
            } else {
                Application::i()->sendmail(
                    $emails,
                    $subject,
                    $message,
                    $fromName,
                    $fromEmail,
                    true,
                    $attachments,
                    $embedded
                );
            }
            // @codeCoverageIgnoreEnd
        }

        if ($smsEmails = ($addresses['smsEmails'] ?? null)) {
            if ($debug) {
                $debugMessages['smsEmails'] = [
                    'emails' => $smsEmails,
                    'subject' => $subject,
                    'message' => $smsMessage,
                    'from' => $fromName,
                    'fromEmail' => $fromEmail,
                ];
            // @codeCoverageIgnoreStart
            // Не можем проверить отправку письма
            } else {
                Application::i()->sendmail(
                    $smsEmails,
                    $subject,
                    $smsMessage,
                    $fromName,
                    $fromEmail,
                    false
                );
            }
            // @codeCoverageIgnoreEnd
        }

        if ($smsPhones = ($addresses['smsPhones'] ?? null)) {
            $urlTemplate = Package::i()->registryGet('sms_gate');
            foreach ($smsPhones as $phone) {
                $url = Text::renderTemplate($urlTemplate, [
                    'PHONE' => urlencode($phone),
                    'TEXT' => urlencode($smsMessage)
                ]);
                if ($debug || !Application::i()->prod) {
                    $debugMessages['smsPhones'][] = $url;
                // Не можем проверить отправку SMS
                // @codeCoverageIgnoreStart
                } else {
                    $result = file_get_contents($url);
                }
                // @codeCoverageIgnoreEnd
            }
        }

        if ($debug) {
            return $debugMessages;
        }
    }


    /**
     * Получает заголовок e-mail сообщения
     * @param Order $order Заказ
     * @param bool $forAdmin Уведомление для администратора (если нет, то для пользователя)
     * @return string
     */
    public function getEmailOrderSubject(Order $order, $forAdmin = false): string
    {
        $host = $this->getCurrentHostName();
        if (function_exists('idn_to_utf8')) {
            $host = idn_to_utf8($host);
        }
        $host = mb_strtoupper((string)$host);

        $subject = date(RAASViewWeb::i()->_('DATETIMEFORMAT')) . ' ';
        if ($forAdmin) {
            $subject .= sprintf(
                View_Web::i()->_('ORDER_STANDARD_HEADER'),
                $order->parent->name,
                $order->page->name
            );
        } else {
            $subject .= sprintf(
                View_Web::i()->_('ORDER_STANDARD_HEADER_USER'),
                $order->id,
                $host
            );
        }
        return $subject;
    }


    /**
     * Конвертирует мета-данные для сохранения в заказ
     * @param mixed $meta Мета-данные для конвертации
     * @return mixed
     */
    public function convertMeta($meta)
    {
        return $meta;
    }
}
