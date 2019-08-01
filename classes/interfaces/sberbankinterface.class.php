<?php
/**
 * Файл класса интерфейса электронной оплаты через Сбербанк России
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Application;
use RAAS\Exception;
use RAAS\Redirector;
use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс интерфейса электронной оплаты через Сбербанк России
 */
class SberbankInterface extends EPayInterface
{
    /**
     * Значение поля Status получении состояния заказа: предавторизованная сумма
     * захолдирована (для двухстадийных платежей)
     */
    const REQUEST_STATUS_HOLD = 1;

    /**
     * Значение поля Status получении состояния заказа: проведена полная
     * авторизация суммы заказа
     */
    const REQUEST_STATUS_PAID = 2;

    /**
     * Ставка НДС - без НДС
     */
    const TAX_TYPE_NO_VAT = 0;

    /**
     * Система налогообложения - упрощенная, доход
     */
    const TAX_SYSTEM_SIMPLE = 1;

    /**
     * Тип оплаты - полная предварительная оплата до момента передачи предмета расчёта
     */
    const PAYMENT_METHOD_PREPAY = 1;

    /**
     * Тип оплачиваемой позиции - товар
     */
    const PAYMENT_OBJECT_PRODUCT = 1;

    /**
     * Обработка интерфейса
     * @param Order $order Текущий заказ, для которого осуществляем обработку
     * @return [
     *             'epayWidget' ?=> Snippet Виджет оплаты
     *             'Item' ?=> Order Текущий заказ
     *             'success' ?=> array<int[] ID# блока => сообщение об успешной операции>,
     *             'localError' ?=> array<string[] URN поля ошибки => сообщение об ошибке>,
     *             'paymentURL' ?=> string Платежный URL,
     *         ]
     */
    public function process(Order $order = null)
    {
        $out = [];
        $addURN = $this->page->additionalURLArray[0];
        try {
            if (in_array($addURN, ['result', 'fail']) ||
                ($order->id && $this->post['epay'])
            ) {
                if (in_array($addURN, ['result', 'fail'])) {
                    if (!($order && $order->id) &&
                        $this->session['sberbankOrderNumber']
                    ) {
                        $order = new Order($this->session['sberbankOrderNumber']);
                    }
                }
                switch ($addURN) {
                    case 'result':
                        $out = $this->result(
                            $order,
                            $this->block,
                            $this->page,
                            $this->get,
                            $this->session
                        );
                        break;
                    case 'fail':
                        $out = $this->fail(
                            $order,
                            $this->block,
                            $this->page,
                            $this->get,
                            $this->session
                        );
                        break;
                    default:
                        $out = $this->init($order, $this->block, $this->page);
                        break;
                }
                $out['epayWidget'] = $this->getEPayWidget();
                if ($order->id) {
                    $out['Item'] = $order;
                }
            }
        } catch (Exception $exception) {
            $out['localError'] = [
                'order' => $exception->getMessage()
                        .  (
                                $exception->getCode() ?
                                ' #' . $exception->getCode() :
                                ''
                            )
            ];
        }
        return $out;
    }


    /**
     * Проверка заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param array $get Данные GET-запроса
     * @param array $session Данные сессии
     * @return [
     *             'success' ?=> array<
     *                 int[] ID# блока => сообщение об успешной операции
     *             >,
     *             'localError' ?=> array<
     *                 string[] URN поля ошибки => сообщение об ошибке
     *             >
     *         ]
     */
    public function result(
        Order $order,
        Block_Cart $block,
        Page $page,
        array $get = [],
        array $session = []
    ) {
        if ($session['sberbankOrderNumber'] &&
            $session['sberbankOrderId'] &&
            $order->id
        ) {
            if ($block->epay_test) {
                file_put_contents(
                    'sberbank.log',
                    date('Y-m-d H:i:s ') . 'result: ' .
                    $session['sberbankOrderNumber'] . ' / ' .
                    $session['sberbankOrderId'] . "\n",
                    FILE_APPEND
                );
            }
            $orderIsPaid = $this->getOrderIsPaid(
                $block,
                $page,
                $get,
                $session
            );
            if ($orderIsPaid) {
                $history = new Order_History();
                $history->uid = Application::i()->user->id;
                $history->order_id = (int)$order->id;
                $history->status_id = (int)$order->status_id;
                $history->paid = 1;
                $history->post_date = date('Y-m-d H:i:s');
                $history->description = 'Оплачено через Сбербанк'
                                      . ' (ID# заказа в системе банка: '
                                      . $session['sberbankOrderId'] . ')';
                $history->commit();

                $order->paid = 1;
                $order->commit();
                $out['success'][(int)$block->id] = sprintf(
                    ORDER_SUCCESSFULLY_PAID,
                    $order->id
                );
            } else {
                $out['localError'] = [
                    'order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $order->id)
                ];
            }
        } else {
            $out['localError'] = ['order' => INVALID_CRC];
        }
        return $out;
    }


    /**
     * Обработка неудачного завершения
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param array $get Данные GET-запроса
     * @param array $session Данные сессии
     * @return [
     *             'localError' ?=> array<
     *                 string[] URN поля ошибки => сообщение об ошибке
     *             >
     *         ]
     */
    public function fail(
        Order $order,
        Block_Cart $block,
        Page $page,
        array $get = [],
        array $session = []
    ) {
        if ($block->epay_test) {
            file_put_contents(
                'sberbank.log',
                date('Y-m-d H:i:s ') . 'fail: ' . $session['sberbankOrderId'] .
                ' / ' . $session['sberbankOrderNumber'] . "\n",
                FILE_APPEND
            );
            $this->getOrderIsPaid($block, $page, $get, $session);
        }
        return ['localError' => [
            'order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $order->id)
        ]];
    }


    /**
     * Инициализация заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return [
     *             'paymentURL' ?=> string Платежный URL,
     *         ]
     */
    public function init(Order $order, Block_Cart $block, Page $page)
    {
        $out = [];
        $_SESSION['orderId'] = $orderId = $order->id;
        $response = $this->registerOrder($order, $block, $page);

        $localError = [];
        if (!$response) {
            $localError[] = 'Не удалось получить результат запроса на оплату';
        } elseif ($response['errorCode']) {
            $localError[] = 'В процессе регистрации заказа возникла ошибка: #'
                          . $response['errorCode'] . ' '
                          . $response['errorMessage'];
        } elseif (!$response['orderId'] || !$response['formUrl']) {
            $localError[] = 'Не удалось получить адрес для оплаты';
        }

        if ($localError) {
            if ($block->epay_test) {
                file_put_contents(
                    'sberbank.log',
                    date('Y-m-d H:i:s ') . 'init: ' .
                    var_export($localError, true) . "\n",
                    FILE_APPEND
                );
            }
            return ['localError' => $localError];
        } else {
            $_SESSION['sberbankOrderNumber'] = (int)$order->id;
            $_SESSION['sberbankOrderId'] = $response['orderId'];
            if ($block->epay_test) {
                file_put_contents(
                    'sberbank.log',
                    date('Y-m-d H:i:s ') . 'init: ' . (int)$order->id . ' / ' .
                    $response['orderId'] . "\n",
                    FILE_APPEND
                );
            }
            $redirectURL = $response['formUrl'];
            new Redirector($redirectURL);
            exit;
        }
    }


    /**
     * Обращается к интерфейсу банка
     * @param string $method Метод для обращения
     * @param array $requestData Данные запроса
     * @param bool $isTest Тестовый режим
     * @return array Данные ответа
     */
    public function exec($method, array $requestData = [], $isTest = false)
    {
        $url = $this->getURL($isTest)
             . $method . '.do?' . http_build_query($requestData);
        $result = file_get_contents($url);
        $json = json_decode($result, true);
        return $json;
    }



    /**
     * Получает URL API банка
     * @param bool $isTest Тестовый режим
     * @return string
     */
    public function getURL($isTest = false)
    {
        if ($isTest) {
            $url = 'https://3dsec.sberbank.ru/payment/rest/';
        } else {
            $url = 'https://securepayments.sberbank.ru/payment/rest/';
        }
        return $url;
    }


    /**
     * Является ли статус успешным
     * @param string $status Внутреннее представление статуса
     * @return bool
     */
    public function isSuccessfulStatus($status)
    {
        return in_array(
            $status,
            [static::REQUEST_STATUS_PAID, static::REQUEST_STATUS_HOLD]
        );
    }


    /**
     * Получение данные для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param string $emailField URN поля E-mail
     * @param string $phoneField URN поля Телефон
     * @param string $deliveryAddressField URN поля Адрес доставки
     * @param string $deliveryCityField URN поля Город доставки
     * @param string $deliveryCountryField URN поля Страна доставки
     * @param string $taxTypeField URN поля Ставка НДС
     * @param string $taxSystemField URN поля Система налогообложения
     * @param string $paymentMethodField URN поля Тип оплаты
     * @param string $paymentObjectField URN поля Тип оплачиваемой позиции
     * @return array
     */
    public function getRegisterOrderData(
        Order $order,
        Block_Cart $block,
        Page $page,
        $emailField = 'email',
        $phoneField = 'phone',
        $deliveryAddressField = 'address',
        $deliveryCityField = 'city',
        $deliveryCountryField = 'country',
        $taxTypeField = 'tax_type',
        $taxSystemField = 'tax_system',
        $paymentMethodField = 'sberbank_payment_method',
        $paymentObjectField = 'sberbank_payment_object'
    ) {
        $jsonParams = [];
        foreach ($order->fields as $field) {
            if (!in_array($field->datatype, ['image', 'file', 'checkbox']) &&
                !in_array($field->urn, ['epay'])
            ) {
                $val = $field->getValues(true);
                $val = array_map(function ($x) use ($field) {
                    $y = $field->doRich($x);
                    if ($y instanceof Material) {
                        $y = $y->name;
                    }
                    return $y;
                }, $val);
                $val = implode(', ', $val);
                $jsonParams[$field->urn] = $val;
            }
        }
        $orderBundle = [];
        if (trim($order->$phoneField) !== '') {
            $beautifiedPhone = Text::beautifyPhone(trim($order->$phoneField));
            $beautifiedPhoneLength = mb_strlen($beautifiedPhone);
            $beautifiedPhone = mb_substr('700000', 0, 11 - $beautifiedPhoneLength) . $beautifiedPhone;
            $orderBundle['customerDetails']['phone'] = $beautifiedPhone;
        }
        if (trim($order->$emailField) !== '') {
            $orderBundle['customerDetails']['email'] = trim($order->$emailField);
        }
        if (trim($order->$deliveryAddressField) !== '') {
            $orderBundle['customerDetails']['deliveryInfo']['postAddress'] = trim($order->$deliveryAddressField);
            $orderBundle['customerDetails']['deliveryInfo']['city'] = trim($order->$deliveryCityField) ?: '-';
            $orderBundle['customerDetails']['deliveryInfo']['country'] = trim($order->$deliveryCountryField) ?: 'RU';
        }

        $orderBundle['cartItems'] = [];
        foreach ($order->items as $i => $item) {
            if ($item->realprice > 0) {
                $itemPrice = (float)$item->realprice;
                $itemPrice = round($itemPrice * 100);
                $itemData = [
                    'positionId' => ($i + 1),
                    'name' => $item->name,
                    'quantity' => [
                        'value' => (int)$item->amount,
                        'measure' => 'шт.'
                    ],
                    'itemPrice' => $itemPrice,
                    'itemAmount' => $itemPrice * $item->amount,
                    'itemCode' => (int)$item->id,
                    'tax' => [
                        'taxType' => (trim($order->$taxTypeField) !== '')
                                  ?  $order->$taxTypeField
                                  :  static::TAX_TYPE_NO_VAT
                    ],
                    // 'itemAttributes' => [
                    //     'paymentMethod' => (trim($order->paymentMethodField) !== '')
                    //                     ?  $order->paymentMethodField
                    //                     : static::PAYMENT_METHOD_PREPAY,
                    //     'paymentObject' => (trim($order->paymentObjectField) !== '')
                    //                     ?  $order->paymentObjectField
                    //                     : static::PAYMENT_OBJECT_PRODUCT,
                    // ],
                ];
                $orderBundle['cartItems']['items'][] = $itemData;
            }
        }
        $requestData = [
            'userName' => $block->epay_login,
            'password' => $block->epay_pass1,
            'orderNumber' => (int)$order->id,
            'amount' => ($order->sum * 100),
            'returnUrl' => $this->getCurrentHostURL() . $page->url . 'result/',
            'failUrl' => $this->getCurrentHostURL() . $page->url . 'result/',
            'description' => $this->getOrderDescription($order),
            'jsonParams' => json_encode($jsonParams),
            'taxSystem' => (trim($order->$taxSystem) !== '')
                        ?  $order->$taxSystem
                        :  static::TAX_SYSTEM_SIMPLE,
            'orderBundle' => json_encode($orderBundle),
        ];

        return $requestData;
    }


    /**
     * Регистрирует заказ
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array Ответ сервера
     */
    public function registerOrder(Order $order, Block_Cart $block, Page $page)
    {
        $requestData = $this->getRegisterOrderData($order, $block, $page);
        $response = $this->exec('register', $requestData, $block->epay_test);
        return $response;
    }


    /**
     * Получает описание заказа
     * @param Order $order Заказ
     * @return string
     */
    public function getOrderDescription(Order $order)
    {
        $text = sprintf(ORDER_NUM, (int)$order->id, $this->getCurrentHostName());
        return $text;
    }


    /**
     * Получает статус оплаты заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param array $get Данные GET-запроса
     * @param array $session Данные сессии
     * @return bool Статус оплаты заказа
     * @throws Exception Ошибка при выполнении
     */
    public function getOrderIsPaid(Block_Cart $block, Page $page, array $get = [], array $session = [])
    {
        $requestData = array(
            'userName' => $block->epay_login,
            'password' => $block->epay_pass1,
            'orderId' => $get['orderId'] ?: $session['sberbankOrderId'],
        );
        $json = $this->exec('getOrderStatus', $requestData, $block->epay_test);

        if (!$json) {
            $errorText = 'Не удалось получить результат запроса состояния заказа';
            throw new Exception($errorText);
        } elseif ($json['errorCode']) {
            $errorText = 'В процессе оплаты заказа'
                       . (
                            $json['OrderNumber'] ?
                            ' #' . (int)$json['OrderNumber'] :
                            ''
                        )
                       . ' возникла ошибка: (' . $json['errorCode'] . ') '
                       . $json['errorMessage'];
            if ($json['OrderNumber']) {
                $order = new Order((int)$json['OrderNumber']);
                $history = new Order_History();
                $history->uid = Application::i()->user->id;
                $history->order_id = (int)$order->id;
                $history->status_id = (int)$order->status_id;
                $history->paid = 0;
                $history->post_date = date('Y-m-d H:i:s');
                $history->description = $errorText;
                $history->commit();
            }
            if ($block->epay_test) {
                file_put_contents(
                    'sberbank.log',
                    date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' .
                    var_export($requestData, true) . "\n" .
                    var_export($json, true) . "\n" .
                    'Error: ' . $errorText,
                    FILE_APPEND
                );
            }
            throw new Exception($errorText);
        } elseif (!$json['OrderNumber'] || !$json['OrderStatus']) {
            if ($block->epay_test) {
                file_put_contents(
                    'sberbank.log',
                    date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' .
                    var_export($requestData, true) . "\n" .
                    var_export($json, true) . "\n" .
                    'Error: Не удалось получить адрес для оплаты',
                    FILE_APPEND
                );
            }
            throw new Exception('Не удалось получить адрес для оплаты');
        }

        if ($block->epay_test) {
            file_put_contents(
                'sberbank.log',
                date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' .
                var_export($requestData, true) . "\n",
                FILE_APPEND
            );
        }

        $_SESSION['sberbankOrderNumber'] = (int)$json['OrderNumber'];
        return $this->isSuccessfulStatus($json['OrderStatus']);
    }


    /**
     * Получает виджет электронной оплаты
     * @return Snippet
     */
    public function getEPayWidget()
    {
        return Snippet::importByURN('sberbank');
    }
}
