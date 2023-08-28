<?php
/**
 * Файл класса интерфейса электронной оплаты через PayMaster
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
 * Класс интерфейса электронной оплаты через PayMaster
 */
class PayMasterInterface extends EPayInterface
{
    /**
     * Значение поля status получении состояния заказа: проведена полная
     * авторизация суммы заказа
     */
    const REQUEST_STATUS_PAID = 'Settled';

    /**
     * Ставка НДС - без НДС
     */
    const TAX_TYPE_NO_VAT = 'None';

    /**
     * Предмет расчета - товар
     */
    const PAYMENT_SUBJECT_COMMODITY = 'Commodity';

    /**
     * Признак способа расчета - Полная предоплата
     */
    const PAYMENT_METHOD_FULL_PREPAYMENT = 'FullPrepayment';

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
                    if (!($order && $order->id) && $this->get['orderid']) {
                        $order = new Order($this->get['orderid']);
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
            if (!$order->paid) {
                $orderIsPaid = $this->getOrderIsPaid($order, $block, $page);
                if ($orderIsPaid) {
                    $history = new Order_History([
                        'uid' => (int)Application::i()->user->id,
                        'order_id' => (int)$order->id,
                        'status_id' => (int)$order->status_id,
                        'paid' => 1,
                        'post_date' => date('Y-m-d H:i:s'),
                        'description' => 'Оплачено через PayMaster'
                            .  ' (ID# заказа в системе: ' .  $order->payment_id . ')'
                    ]);
                    $history->commit();

                    $order->paid = 1;
                    $order->commit();
                }
            }
            if ($block->epay_test) {
                file_put_contents(
                    'paymaster.log',
                    date('Y-m-d H:i:s ') . 'result: ' .
                    $order->id . ' / ' .
                    $order->payment_id . "\n\n",
                    FILE_APPEND
                );
            }
            if ($this->server['REQUEST_METHOD'] == 'POST') {
                $request = file_get_contents('php://input');
                if (substr($request, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
                    $request = substr($request, 3);
                }
                $json = json_decode($request, true);
                if ($json) {
                    exit; // Для callback-запроса
                }
            }
            if ($order->paid) {
                $out['success'][(int)$block->id] = 'Ваш заказ №' . $order->id . ' был успешно оплачен. Все материалы вы можете увидеть в личном кабинете';
            } else {
                $out['localError'] = ['order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $order->id)];
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
    public function fail(Order $order, Block_Cart $block, Page $page, array $get = [])
    {
        if ($block->epay_test) {
            $logMessage = date('Y-m-d H:i:s ') . 'fail: ';
            if ($order->id) {
                $logMessage .= $order->id . ' / ' . $order->payment_id . "\n\n";
            } else {
                $logMessage .= "order not found\n\n";
            }
            file_put_contents('paymaster.log', $logMessage, FILE_APPEND);
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
        } elseif ($response['code'] && $response['errors']) {
            $localError = array_merge($localError, $response['errors']);
        } elseif (!$response['paymentId'] || !$response['url']) {
            $localError[] = 'Не удалось получить адрес для оплаты';
        }

        if ($localError) {
            if ($block->epay_test) {
                file_put_contents(
                    'paymaster.log',
                    date('Y-m-d H:i:s ') . 'init: ' .
                    var_export($localError, true) . "\n\n",
                    FILE_APPEND
                );
            }
            return ['localError' => $localError];
        } else {
            $order->payment_id = $response['paymentId'];
            $order->payment_interface_id = (int)$block->EPay_Interface->id;
            $order->payment_url = $response['url'];
            $order->commit();
            $history = new Order_History([
                'uid' => (int)Application::i()->user->id,
                'order_id' => (int)$order->id,
                'status_id' => (int)$order->status_id,
                'paid' => (int)$order->paid,
                'post_date' => date('Y-m-d H:i:s'),
                'description' => 'Зарегистрировано в системе PayMaster'
                              .  ' (ID# заказа в системе: '
                              .  $order->payment_id . ', платежный URL: '
                              .  $order->payment_url . ')'
            ]);
            $history->commit();
            if ($block->epay_test) {
                file_put_contents(
                    'paymaster.log',
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
     * @param bool $isPut PUT-протокол
     * @return array Данные ответа
     */
    public function exec($method, array $requestData = [], $isTest = false, $isPut = false)
    {
        $idempotencyKey = crc32(md5($this->block->epay_pass1) . $method . json_encode($requestData));
        $headers = [
            'Idempotency-Key: ' . $idempotencyKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($this->block->epay_pass1) {
            $headers[] = 'Authorization: Bearer ' . $this->block->epay_pass1;
        }
        $url = $this->getURL($isTest) . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($isPut === true) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($requestData) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        if ($requestData) {
            if ($this->block->epay_login) {
                $requestData['merchantId'] = $this->block->epay_login;
            }
            if ($isTest) {
                $requestData['testMode'] = true;
            }
            $request = json_encode($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }
        // var_dump($url, $headers, $requestData); exit;
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
        $url = 'https://paymaster.ru/api/v2/';
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
        $totalSumKop = 0; // Ввели сумму в копейках, чтобы не было расхождений при погрешности округления
        $positiveOrderItems = array_values(array_filter($order->items, function ($x) {
            return $x->realprice > 0;
        }));
        $c = count($positiveOrderItems);
        foreach ($positiveOrderItems as $i => $item) {
            if ($i < $c - 1) { // До последнего товара
                $itemPrice = (float)$item->realprice;
                $itemPrice = round($itemPrice * (1 - $discount) * 100) / 100;
            } else { // $i == $c - 1  - последний товар
                $itemPrice = floor(($order->sum * 100 - $totalSumKop) / $item->amount) / 100; // floor - чтобы пользователь не переплатил финальную сумму
            }
            $itemData = [
                'name' => $item->name,
                'quantity' => (int)$item->amount,
                'price' => $itemPrice,
                'vatType' => $order->taxType ?: static::TAX_TYPE_NO_VAT,
                'paymentSubject' => $order->paymentSubject ?: static::PAYMENT_SUBJECT_COMMODITY,
                'paymentMethod' => $order->paymentMethod ?: static::PAYMENT_METHOD_FULL_PREPAYMENT,
            ];
            $cartData[] = $itemData;
            $totalSumKop += round($itemPrice * 100) * $item->amount;
        }

        $currency = $block->epay_currency;
        if ($currency == 'RUR') {
            $currency = 'RUB';
        }
        $requestData = [
            'invoice' => [
                'description' => $this->getOrderDescription($order),
                'orderNo' => trim($order->id),
            ],
            'amount' => [
                'value' => (float)($totalSumKop / 100),
                'currency' => $currency,
            ],
            'protocol' => [
                'returnUrl' => $this->getCurrentHostURL() . $page->url . $page->additionalURL . 'result/?orderid=' . (int)$order->id,
                'callbackUrl' => $this->getCurrentHostURL() . $page->url . $page->additionalURL . 'result/?orderid=' . (int)$order->id,
            ],
            'customer' => [
                'ip' => $this->server['REMOTE_ADDR'],
            ],
            'receipt' => [
                'client' => [],
                'items' => $cartData,
            ],
        ];
        if (trim($order->email) !== '') {
            $requestData['customer']['email'] = trim($order->email);
            $requestData['receipt']['client']['email'] = trim($order->email);
        }
        if (trim($order->phone) !== '') {
            $requestData['customer']['phone'] = Text::beautifyPhone($order->phone, 11);
            $requestData['receipt']['client']['phone'] = Text::beautifyPhone($order->phone, 11);
        }
        if ((int)$order->uid) {
            $requestData['customer']['account'] = trim($order->uid);
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
        $response = $this->exec('invoices', $requestData, $block->epay_test);
        if ($block->epay_test) {
            file_put_contents(
                'paymaster.log',
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
        $text = sprintf(ORDER_NUM, (int)$order->id, $this->getCurrentHostName());
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
        $url = 'payments/' . $order->payment_id;
        $json = $this->exec($url, [], $block->epay_test);

        if (!$json) {
            $errorText = 'Не удалось получить результат запроса состояния заказа';
            throw new Exception($errorText);
        } elseif ($json['code'] && $json['errors']) {
            $errorText = 'В процессе оплаты заказа'
                       . ($order->id ? ' #' . $order->id : '')
                       . ' возникли ошибка: ' . implode('; ', (array)$json['errors']);
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
                    'paymaster.log',
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
                    'paymaster.log',
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
                'paymaster.log',
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
