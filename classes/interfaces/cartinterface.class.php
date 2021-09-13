<?php
/**
 * Файл стандартного интерфейса корзины
 */
namespace RAAS\CMS\Shop;

use Mustache_Engine;
use SOME\HTTP;
use RAAS\Application;
use RAAS\Controller_Frontend as RAASControllerFrontend;
use RAAS\Redirector;
use RAAS\View_Web as RAASViewWeb;
use RAAS\CMS\AbstractInterface;
use RAAS\CMS\FormInterface;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypesRecursiveCache;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\User;

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
     * @param Block_Cart|null $block Блок, для которого применяется
     *                               интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     * @param array $files Поля $_FILES параметров
     */
    public function __construct(
        Block_Cart $block = null,
        Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        array $files = []
    ) {
        AbstractInterface::__construct(
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
     * @return mixed
     */
    public function process($debug = false)
    {
        $result = [];
        $cartType = new Cart_Type((int)$this->block->cart_type);
        $user = RAASControllerFrontend::i()->user;
        $cart = new Cart($cartType, $user);
        $action = isset($this->get['action']) ? $this->get['action'] : '';

        switch ($action) {
            case 'set':
            case 'add':
            case 'reduce':
            case 'delete':
                $id = isset($this->get['id']) ? $this->get['id'] : '';
                $material = new Material((int)$id);
                $meta = isset($this->get['meta']) ? $this->get['meta'] : '';
                $amount = 0;
                if (($action != 'delete') && isset($this->get['amount'])) {
                    $amount = (int)$this->get['amount'];
                }
                if ($material->id) {
                    switch ($action) {
                        case 'set':
                        case 'delete':
                            $cart->set($material, $amount, $meta);
                            break;
                        case 'add':
                            $cart->add($material, $amount ?: 1, $meta);
                            break;
                        case 'reduce':
                            $cart->reduce($material, $amount ?: 1, $meta);
                            break;
                    }
                }
                break;
            case 'clear':
                $cart->clear();
                break;
            case 'success':
                $result = array_merge(
                    $result,
                    $this->success($this->block, $this->get)
                );
                break;
            case 'refresh':
                break;
            default:
                $form = $cartType->Form;
                if (isset($this->post['amount'])) {
                    foreach ((array)$this->post['amount'] as $key => $val) {
                        list($id, $meta) = explode('_', $key);
                        $material = new Material($id);
                        $cart->set($material, (int)$val, $meta);
                    }
                }
                if ($form->id && $cart->items) {
                    $localError = [];
                    if ($this->isFormProceed(
                        $this->block,
                        $form,
                        $this->server['REQUEST_METHOD'],
                        $this->post
                    )) {
                        // 2019-10-02, AVS: добавили для совместимости с виджетом, где даже
                        // в случае ошибок проверяется соответствие
                        // ($Item instanceof Order)
                        // 2019-11-14, AVS: перенес сюда, иначе при AJAX-запросе
                        // первая попавшаяся форма отключает
                        $result['Item'] = $this->getRawOrder($cart);

                        $Item = new Order();
                        $Item->pid = (int)$cartType->id;

                        // Проверка полей на корректность
                        $localError = $this->check(
                            $form,
                            $this->post,
                            $this->session,
                            $this->files
                        );

                        if (!$localError) {
                            $result = array_merge($result, $this->processOrderForm(
                                $cart,
                                $this->page,
                                $this->post,
                                $this->server,
                                $this->files
                            ));
                            if ($this->post['epay'] != 1) {
                                $cart->clear();
                                $result['success'][(int)$this->block->id] = true;
                            }
                            new Redirector(
                                '?action=success&id=' . (int)$result['Item']->id .
                                '&crc=' . Application::i()->md5It($result['Item']->id) .
                                ($this->post['epay'] ? ('&epay=' . $this->post['epay']) : '')
                            );
                        }
                        $result['DATA'] = $this->post;
                        $result['localError'] = $localError;
                    } else {
                        $result['DATA'] = [];
                        foreach ($form->fields as $fieldURN => $row) {
                            if ($row->defval) {
                                $result['DATA'][$fieldURN] = $row->defval;
                            } elseif ($userVal = $user->{$fieldURN}) {
                                $result['DATA'][$fieldURN] = $userVal;
                            }
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
                !$this->get['AJAX'] // 2020-12-25, AVS: добавлено, чтобы не редиректило в AJAX'е
            )
        ) {
            if ($this->get['back']) {
                $url = 'history:back';
            } else {
                $url = HTTP::queryString('action=&id=&meta=&amount=') ?: '?';
            }
            if ($debug) {
                return $url;
            } else {
                new Redirector($url);
            }
        }

        $result['Cart'] = $cart;
        $result['Cart_Type'] = $cartType;
        $result['convertMeta'] = [$this, 'convertMeta'];
        $result['interface'] = $this;
        if ($additional = $this->getAdditionals($cart, $this->post, $user)) {
            $result['additional'] = $additional;
        }
        if ($this->block->EPay_Interface->id) {
            $epayResult = $this->block->EPay_Interface->process(array_merge(
                [
                    'Block' => $this->block,
                    'Page' => $this->page,
                    'config' => $this->block->config,
                ],
                $result
            ));
            if ($epayResult['success'][$this->block->id]) {
                $cart->clear();
            }
            $result = array_merge($result, $epayResult);
        }
        return $result;
    }


    /**
     * Функция расчета дополнительных пунктов для корзины
     * @param Cart $cart Корзина
     * @param array $post POST-данные
     * @param User $user Пользователь
     * @return array <pre>[
     *     'items' => array<CartItem>,
     *     string[] => mixed Дополнительные данные
     * ]</pre>
     */
    public function getAdditionals(
        Cart $cart,
        array $post = [],
        User $user = null
    ) {
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
     */
    public function success(Block_Cart $block, array $get)
    {
        $result = [];
        $localError = [];
        if (!$get['id']) {
            $localError['id'] = View_Web::i()->_('ORDER_NOT_FOUND');
        } elseif ($get['crc'] != Application::i()->md5It($get['id'])) {
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
            if ($get['epay']) {
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
     * @param Page $page Текущая страница
     * @param array $post Данные $_POST-полей
     * @param array $server Данные $_SERVER-полей
     * @param array $files Данные $_FILES-полей
     * @return [
     *             'Item' =>? Order Заказ
     *             'Material' =>? Material Созданный материал
     *         ]
     */
    public function processOrderForm(
        Cart $cart,
        Page $page,
        array $post = [],
        array $server = [],
        array $files = []
    ) {
        $order = $this->getRawOrder($cart);
        // Для AJAX'а
        $this->processFeedbackReferer($order, $page, $server);
        $user = RAASControllerFrontend::i()->user;
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
        if ($additional['items']) {
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
        return ['Item' => $order, 'Material' => $material];
    }


    /**
     * Получает "сырой" заказ (без commit'а и заполненных полей)
     * @param Cart $cart Корзина
     * @return Order
     */
    public function getRawOrder(Cart $cart)
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
                $material = new Material($cartItem->id);
                $price = $cart->getPrice($material);
                $orderItems[] = [
                    'material_id' => $cartItem->id,
                    'name' => $cartItem->name,
                    'meta' => $this->convertMeta($cartItem->meta),
                    'realprice' => (float)$price,
                    'amount' => (int)$cartItem->amount,
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
     * @param Material $material Созданный материал
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
        Material $material = null,
        $forAdmin = false,
        $debug = false
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

        if ($emails = $addresses['emails']) {
            if ($debug) {
                $debugMessages['emails'] = [
                    'emails' => $emails,
                    'subject' => $subject,
                    'message' => $message,
                    'from' => $fromName,
                    'fromEmail' => $fromEmail,
                ];
            } else {
                Application::i()->sendmail(
                    $emails,
                    $subject,
                    $message,
                    $fromName,
                    $fromEmail
                );
            }
        }

        if ($smsEmails = $addresses['smsEmails']) {
            if ($debug) {
                $debugMessages['smsEmails'] = [
                    'emails' => $smsEmails,
                    'subject' => $subject,
                    'message' => $smsMessage,
                    'from' => $fromName,
                    'fromEmail' => $fromEmail,
                ];
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
        }

        if (Application::i()->prod && ($smsPhones = $addresses['smsPhones'])) {
            $urlTemplate = Package::i()->registryGet('sms_gate');
            $m = new Mustache_Engine();
            foreach ($smsPhones as $phone) {
                $url = $m->render($urlTemplate, [
                    'PHONE' => urlencode($phone),
                    'TEXT' => urlencode($smsMessage)
                ]);
                if ($debug) {
                    $debugMessages['smsPhones'][] = $url;
                } else {
                    $result = file_get_contents($url);
                }
            }
        }

        if ($debug) {
            return $debugMessages;
        }
    }


    /**
     * Получает заголовок e-mail сообщения
     * @param Order $order Заказ
     * @param bool $forAdmin Уведомление для администратора
     *                       (если нет, то для пользователя)
     * @return string
     */
    public function getEmailOrderSubject(Order $order, $forAdmin = false)
    {
        $host = $this->server['HTTP_HOST'];
        if (function_exists('idn_to_utf8')) {
            $host = idn_to_utf8($host);
        }
        $host = mb_strtoupper($host);

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
     */
    public function convertMeta($meta)
    {
        return $meta;
    }
}
