<?php
/**
 * Файл класса интерфейса электронной оплаты через PayKeeper
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
 * Класс интерфейса электронной оплаты через PayKeeper
 */
class PayKeeperInterface extends EPayInterface
{
    /**
     * Значение поля status получении состояния заказа: проведена полная
     * авторизация суммы заказа
     */
    const REQUEST_STATUS_PAID = 'paid';

    /**
     * Ставка НДС - без НДС
     */
    const TAX_TYPE_NO_VAT = 'vat0';

    /**
     * Система налогообложения - упрощенная, доход
     */
    const TAX_SYSTEM_SIMPLE = 'none';

    /**
     * Токен безопасности
     * @var string
     */
    protected $token = '';

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
            if (in_array($addURN, ['result', 'success', 'fail']) ||
                ($order->id && $this->post['epay'])
            ) {
                if (in_array($addURN, ['result'])) {
                    if (!($order && $order->id) && $this->post['orderid']) {
                        $order = new Order($this->post['orderid']);
                    }
                } elseif (in_array($addURN, ['success', 'fail'])) {
                    if ((!$order || !$order->id) &&
                        $this->session['orderId']
                    ) {
                        $order = new Order($this->session['orderId']);
                    }
                }
                switch ($addURN) {
                    case 'result':
                        if (!$order) {
                            $order = new Order();
                        }
                        $out = $this->result($order, $this->block, $this->page);
                        break;
                    case 'success':
                        $out['success'][(int)$this->block->id] = sprintf(
                            ORDER_SUCCESSFULLY_PAID,
                            $order->id
                        );
                        break;
                    case 'fail':
                        $out = $this->fail(
                            $order,
                            $this->block,
                            $this->page,
                            $this->get
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
     * @return [
     *             'success' ?=> array<
     *                 int[] ID# блока => сообщение об успешной операции
     *             >,
     *             'localError' ?=> array<
     *                 string[] URN поля ошибки => сообщение об ошибке
     *             >
     *         ]
     */
    public function result(Order $order, Block_Cart $block, Page $page)
    {
        if ($order->id) {
            if ($block->epay_test) {
                file_put_contents(
                    'paykeeper.log',
                    date('Y-m-d H:i:s ') . 'result: ' .
                    $order->id . ' / ' .
                    $order->payment_id . "\n\n",
                    FILE_APPEND
                );
            }
            $orderIsPaid = $this->getOrderIsPaid($order, $block, $page);
            if ($orderIsPaid) {
                if (!$order->paid) {
                    $history = new Order_History([
                        'uid' => (int)Application::i()->user->id,
                        'order_id' => (int)$order->id,
                        'status_id' => (int)$order->status_id,
                        'paid' => 1,
                        'post_date' => date('Y-m-d H:i:s'),
                        'description' => 'Оплачено через PayKeeper'
                                      .  ' (ID# заказа в системе: '
                                      .  $order->payment_id . ')'
                    ]);
                    $history->commit();

                    $order->paid = 1;
                    $order->commit();
                }
                if ($block->epay_pass2 && $this->post['id']) {
                    $checkKey = md5(
                        $this->post['id'] .
                        $this->post['sum'] .
                        $this->post['clientid'] .
                        $this->post['orderid'] .
                        $block->epay_pass2
                    );
                    if ($block->epay_test) {
                        file_put_contents(
                            'paykeeper.log',
                            date('Y-m-d H:i:s ') . 'result: checkKey ' .
                            $this->post['key'] . ' / ' .
                            $checkKey . "\n\n",
                            FILE_APPEND
                        );
                    }
                    if ($block->epay_pass2 && ($this->post['key'] == $checkKey)) {
                        $returnHash = md5($this->post['id'] . $block->epay_pass2);
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        if ($block->epay_test) {
                            file_put_contents(
                                'paykeeper.log',
                                date('Y-m-d H:i:s ') . 'result: returnHash ' .
                                'OK ' . $returnHash . "\n\n",
                                FILE_APPEND
                            );
                        }
                        echo 'OK ' . $returnHash;
                        exit;
                    }
                }
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
        array $get = []
    ) {
        if ($block->epay_test) {
            $logMessage = date('Y-m-d H:i:s ') . 'fail: ';
            if ($order->id) {
                $logMessage .= $order->id . ' / ' . $order->payment_id . "\n\n";
            } else {
                $logMessage .= "order not found\n\n";
            }
            file_put_contents('paykeeper.log', $logMessage, FILE_APPEND);
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
     *             'localError' ?=> string[] Список ошибок,
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
        } elseif ($response['result'] == 'fail') {
            $localError[] = 'В процессе регистрации заказа возникла ошибка: #'
                          . $response['msg'];
        } elseif (!$response['invoice_id'] || !$response['invoice_url']) {
            $localError[] = 'Не удалось получить адрес для оплаты';
        }

        if ($localError) {
            if ($block->epay_test) {
                file_put_contents(
                    'paykeeper.log',
                    date('Y-m-d H:i:s ') . 'init: ' .
                    var_export($localError, true) . "\n\n",
                    FILE_APPEND
                );
            }
            return ['localError' => $localError];
        } else {
            $order->payment_id = $response['invoice_id'];
            $order->payment_interface_id = (int)$block->EPay_Interface->id;
            $order->payment_url = $response['invoice_url'];
            $order->commit();
            $history = new Order_History([
                'uid' => (int)Application::i()->user->id,
                'order_id' => (int)$order->id,
                'status_id' => (int)$order->status_id,
                'paid' => (int)$order->paid,
                'post_date' => date('Y-m-d H:i:s'),
                'description' => 'Зарегистрировано в системе PayKeeper'
                              .  ' (ID# заказа в системе: '
                              .  $order->payment_id . ', платежный URL: '
                              .  $order->payment_url . ')'
            ]);
            $history->commit();
            if ($block->epay_test) {
                file_put_contents(
                    'paykeeper.log',
                    date('Y-m-d H:i:s ') . 'init: ' . (int)$order->id . ' / ' .
                    $response['orderId'] . "\n\n",
                    FILE_APPEND
                );
            }
            // 2022-07-12, AVS: сделал поддержку AJAX'а
            if ($this->post['AJAX'] ||
                ($page->mime == 'application/json')
            ) {
                return ['redirectUrl' => $order->payment_url];
            } else {
                new Redirector($order->payment_url);
                exit;
            }
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
        if (!$this->token && ($method != '/info/settings/token/')) {
            $tokenJSON = $this->exec('/info/settings/token/', [], $isTest);
            if ($tokenJSON['token']) {
                $this->token = $tokenJSON['token'];
            }
        }
        // if ($method != '/info/settings/token/') {
        //     var_dump($requestData); exit;
        // }
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        if ($this->block->epay_login && $this->block->epay_pass1) {
            $headers[] = 'Authorization: Basic '
                . base64_encode($this->block->epay_login . ':' . $this->block->epay_pass1);
        }
        $url = $this->getURL($isTest) . $method;
        // var_dump($url, $headers); exit;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($requestData) {
            if ($this->token) {
                $requestData['token'] = $this->token;
            }
            $request = http_build_query($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        $result = curl_exec($ch);
        $json = @json_decode($result, true);
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
            $url = 'https://demo.paykeeper.ru';
        } else {
            $serverHost = $this->block->additionalParams['paykeeperHost'];
            if ($serverHost) {
                if (!stristr($serverHost, '://')) {
                    $serverHost = 'https://' . $serverHost;
                }
                $url = trim($serverHost, '/');
            } else {
                $currentHostArr = explode('.', $this->getCurrentHostName());
                $url = 'https://' . $currentHostArr[0] . '.server.paykeeper.ru';
            }
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
        return in_array($status, [static::REQUEST_STATUS_PAID]);
    }


    /**
     * Получение данные для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array
     */
    public function getRegisterOrderData(
        Order $order,
        Block_Cart $block,
        Page $page
    ) {
        if ($order->full_name) {
            $clientId = $order->full_name;
        } else {
            $clientIdArr = [
                $order->last_name,
                $order->first_name,
                $order->second_name,
            ];
            $clientIdArr = array_values(array_filter($clientIdArr, 'trim'));
            $clientId = implode(' ', $clientIdArr);
        }

        $cartData = [];
        $sumWithoutDiscount = 0;
        $discountSum = 0;
        foreach ($order->items as $i => $item) {
            $itemSum = (float)($item->realprice * $item->amount);
            if ($item->realprice < 0) {
                $discountSum += abs($itemSum);
            } else {
                $sumWithoutDiscount += $itemSum;
            }
        }
        $discount = $discountSum / (float)$sumWithoutDiscount;
        foreach ($order->items as $i => $item) {
            if ($item->realprice > 0) {
                $itemPrice = (float)$item->realprice;
                $itemPrice = round($itemPrice * (1 - $discount) * 100) / 100;
                $itemData = [
                    'name' => $item->name,
                    'price' => $itemPrice,
                    'quantity' => (int)$item->amount,
                    'sum' => $itemPrice * $item->amount,
                    'tax' => $order->taxType ?: static::TAX_TYPE_NO_VAT,
                ];
                $cartData[] = $itemData;
            }
        }

        $fz54Data = [
            'service_name' => $this->getOrderDescription($order),
            'receipt_properties' => [
                'client' => [
                    'identity' => $clientId,
                ],
            ],
            'cart' => $cartData,
        ];

        $requestData = [
            'pay_amount' => (float)$order->sum,
            'clientid' => $clientId,
            'orderid' => (int)$order->id,
            'service_name' => json_encode($fz54Data),
        ];
        if (trim($order->email) !== '') {
            $requestData['client_email'] = trim($order->email);
        }
        if (trim($order->phone) !== '') {
            $requestData['client_phone'] = trim($order->phone);
        }

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
        $response = $this->exec('/change/invoice/preview/', $requestData, $block->epay_test);
        if ($block->epay_test) {
            file_put_contents(
                'paykeeper.log',
                date('Y-m-d H:i:s ') . 'registerOrder: ' .
                var_export($requestData, true) . "\n" .
                var_export($response, true) . "\n\n",
                FILE_APPEND
            );
        }
        return $response;
    }


    /**
     * Получает описание заказа
     * @param Order $order Заказ
     * @return string
     */
    public function getOrderDescription(Order $order)
    {
        $text = sprintf(
            ORDER_NUM,
            (int)$order->id,
            $this->getCurrentHostName()
        );
        return $text;
    }


    /**
     * Получает статус оплаты заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return bool Статус оплаты заказа
     * @throws Exception Ошибка при выполнении
     */
    public function getOrderIsPaid(Order $order, Block_Cart $block, Page $page)
    {
        if (!$order->payment_id) {
            $errorText = 'Заказ не отправлен на оплату';
            throw new Exception($errorText);
        }
        $url = '/info/invoice/byid/?id=' . $order->payment_id;
        $json = $this->exec($url, [], $block->epay_test);

        if (!$json) {
            $errorText = 'Не удалось получить результат запроса состояния заказа';
            throw new Exception($errorText);
        } elseif ($json['result'] == 'fail') {
            $errorText = 'В процессе оплаты заказа'
                       . ($order->id ? ' #' . $order->id : '')
                       . ' возникла ошибка: ' . $json['msg'];
            $history = new Order_History([
                'uid' => (int)Application::i()->user->id,
                'order_id' => (int)$order->id,
                'status_id' => (int)$order->status_id,
                'paid' => 0,
                'post_date' => date('Y-m-d H:i:s'),
                'description' => $errorText,
            ]);
            $history->commit();
            if ($block->epay_test) {
                file_put_contents(
                    'paykeeper.log',
                    date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' .
                    $url . "\n" .
                    var_export($json, true) . "\n" .
                    'Error: ' . $errorText . "\n\n",
                    FILE_APPEND
                );
            }
            throw new Exception($errorText);
        } elseif (!$json['id'] || !$json['status']) {
            if ($block->epay_test) {
                file_put_contents(
                    'paykeeper.log',
                    date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' .
                    $url . "\n" .
                    var_export($json, true) . "\n" .
                    'Error: Не удалось получить статус состояния заказа' . "\n\n",
                    FILE_APPEND
                );
            }
            throw new Exception('Не удалось получить статус состояния заказа');
        }

        if ($block->epay_test) {
            file_put_contents(
                'paykeeper.log',
                date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' .
                $url . "\n\n",
                FILE_APPEND
            );
        }

        return $this->isSuccessfulStatus($json['status']);
    }


    /**
     * Получает виджет электронной оплаты
     * @return Snippet
     */
    public function getEPayWidget()
    {
        return Snippet::importByURN('epay');
    }
}
