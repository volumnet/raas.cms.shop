<?php
/**
 * Файл класса интерфейса электронной оплаты
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use Exception;
use RAAS\Application;
use RAAS\Redirector;
use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс интерфейса электронной оплаты
 */
abstract class EPayInterface extends AbstractInterface
{
    /**
     * URN системы
     */
    const EPAY_URN = 'epay';

    /**
     * Название системы (название банка)
     */
    const BANK_NAME = '';

    /**
     * Значение поля Status получении состояния заказа: предавторизованная сумма
     * захолдирована (для двухстадийных платежей)
     * !!!МЕНЯЕТСЯ ДЛЯ ДОЧЕРНИХ КЛАССОВ, ЗДЕСЬ ДЛЯ ПРИМЕРА НЕСУЩЕСТВУЮЩЕЕ ЗНАЧЕНИЕ
     */
    const REQUEST_STATUS_HOLD = 'Предавторизованная сумма захолдирована';

    /**
     * Значение поля Status получении состояния заказа: проведена полная
     * авторизация суммы заказа
     * !!!МЕНЯЕТСЯ ДЛЯ ДОЧЕРНИХ КЛАССОВ, ЗДЕСЬ ДЛЯ ПРИМЕРА НЕСУЩЕСТВУЮЩЕЕ ЗНАЧЕНИЕ
     */
    const REQUEST_STATUS_PAID = 'Проведена полная авторизация суммы заказа';

    /**
     * Конструктор класса
     * @param Block_Cart $block Блок, для которого применяется интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     */
    public function __construct(
        Block_Cart $block = null,
        Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = []
    ) {
        parent::__construct($block, $page, $get, $post, $cookie, $session, $server);
    }


    /**
     * Обрабатывает интерфейс
     * 2024-05-01, AVS: Использование /success и /fail считаем устаревшим (вызывается result())
     * @param Order|null Заказ для обработки
     * @return array <pre><code>[
     *     'epayWidget' ?=> Snippet Виджет оплаты
     *     'Item' ?=> Order Текущий заказ
     *     'success' ?=> array<int[] ID# блока => сообщение об успешной операции>,
     *     'localError' ?=> array<string[] URN поля ошибки => сообщение об ошибке>,
     *     'paymentURL' ?=> string Платежный URL,
     * ]</code></pre>
     */
    public function process(Order $order = null): array
    {
        $out = [];
        $additionalURLArray = $this->page->additionalURLArray;
        $addURN = ($additionalURLArray[0] ?? null);
        $isEpay = (bool)($this->post['epay'] ?? null);

        $isProceedPage = in_array($addURN, ['result', 'success', 'fail']); // Страница после оплаты
        try {
            if ($isProceedPage || ($order && $order->id && $isEpay)) {
                if ($isProceedPage && !($order && $order->id)) { // Если не передан заказ
                    $order = $this->findOrder();
                }
                if (!($order && $order->id)) { // Заказ всё еще не найден
                    throw new Exception('Заказ не найден');
                }
                switch ($addURN) {
                    case 'result':
                    case 'success':
                    case 'fail':
                        $out = $this->result($order, $this->block ?: new Block_Cart(), $this->page ?: new Page());
                        break;
                    default:
                        $out = $this->init($order, $this->block ?: new Block_Cart(), $this->page ?: new Page());
                        break;
                }
                $out['epayWidget'] = $this->getEPayWidget();
                if ($order->id) {
                    $out['Item'] = $order;
                }
            }
        } catch (Exception $exception) {
            $errorData = [];
            if ($errorCode = $exception->getCode()) {
                $errorData[] = '#' . $errorCode;
            }
            if ($errorMessage = $exception->getMessage()) {
                $errorData[] = $errorMessage;
            }
            $out['localError'] = ['Ошибка: ' . implode(' ', $errorData)];
        }
        return $out;
    }


    /**
     * Возвращает платежный интерфейс (строку класса или сниппет) блока, либо null, если не указан
     * @param Block_Cart|null $block Блок для проверки
     * @return Snippet|string|null
     */
    public function getPaymentInterface(Block_Cart $block = null)
    {
        if ($block) {
            if ($block->epay_interface_classname) {
                return $block->epay_interface_classname;
            } elseif ($block->EPay_Interface->id) {
                return $block->EPay_Interface;
            }
        }
        return null;
    }


    /**
     * Ищет заказ
     * @return Order|null Заказ, либо null, если заказ не найден
     */
    public function findOrder()
    {
        if ($webhookData = $this->checkWebhook()) {
            $epayInterface = $this->getPaymentInterface($this->block);
            $paymentId = $webhookData['paymentId'] ?? null;
            $orderId = $webhookData['orderId'] ?? null;
            if ($paymentId) {
                $order = Order::importByPayment($paymentId, $epayInterface);
                if ($order && $order->id && (!$orderId || ($order->id == $orderId))) {
                    return $order;
                }
            } elseif ($orderId) {
                $order = new Order($orderId);
                if ($order->id) {
                    return $order;
                }
            }
        }
        if ($orderId = ($this->session['orderId'] ?? null)) {
            $order = new Order($orderId);
            if ($order->id) {
                return $order;
            }
        }
        return null;
    }


    /**
     * Получает список товаров с положительной ценой, и установленными свойствами epayPriceKop
     * (стоимость товара в копейках со скидкой) так, чтобы актуальная сумма была равна общей стоимости заказа
     *
     * Пример:
     * $order = new Order([
     *     'pid' => 1,
     *     'meta_items' => [
     *         ['material_id' => 10, 'name' => 'Товар 1', 'meta' => '', 'realprice' => 1000, 'amount' => 1],
     *         ['material_id' => 11, 'name' => 'Товар 2', 'meta' => '', 'realprice' => 2000, 'amount' => 2],
     *         ['name' => 'Скидка', 'realprice' => -1000, 'amount' => 1],
     *         ['material_id' => 12, 'name' => 'Товар 3', 'meta' => '', 'realprice' => 3000, 'amount' => 3],
     *     ],
     * ]);
     * 1-й товар со скидкой в коп. 92857,142857142857142857142857143
     * 2-й товар со скидкой в коп. 185714,28571428571428571428571429 * 2
     * 3-й товар со скидкой в коп. 278571,42857142857142857142857143 * 3
     * Сумма округленных 92857 + (185714 * 2) + (278571 * 3) = 92857 + 371428 + 835713 = 1299998
     * Должно получиться 1300000
     * После распределения получаем
     * 1-й товар со скидкой в коп. 92857
     * 2-й товар со скидкой в коп. 185715 (добавилась 1 коп.)
     * 3-й товар со скидкой в коп. 278571
     * Сумма 92857 + (185715 * 2) + (278571 * 3) = 92857 + 371430 + 835713 = 1300000
     *
     * @param Order $order Заказ
     * @return array <pre><code>[
     *     'items' => Material[] Товары с положительной ценой,
     *     'sumKop' => int Финальная сумма заказа в копейках (на случай если нормировать не получилось)
     * ]</code></pre>
     */
    public function getPositiveItems(Order $order)
    {
        // 1. Найдем общую сумму заказа, сумму без скидки и сумму скидки - в копейках
        $orderSumKop = floor($order->sum * 100); // floor - чтобы клиент не переплатил
        $sumWithoutDiscountKop = 0;
        $discountSumKop = 0;
        $positiveOrderItems = [];
        foreach ($order->items as $item) {
            $item->epayPriceKop = (float)($item->realprice * 100);
            if ($item->epayPriceKop > 0) {
                $sumWithoutDiscountKop += round($item->epayPriceKop * $item->amount);
                $positiveOrderItems[] = $item;
            } else {
                $discountSumKop += round(abs($item->epayPriceKop) * $item->amount);
            }
        }
        $discount = $discountSumKop / (float)$sumWithoutDiscountKop; // Скидка как доля (к примеру, 0.15 == 15%)

        // 2. Применим скидку
        $c = count($positiveOrderItems);
        $totalSumKop = 0;
        foreach ($positiveOrderItems as $i => $item) {
            if ($i < $c - 1) { // До последнего товара
                $item->epayPriceKop = round($item->epayPriceKop * (1 - $discount));
            } else { // $i == $c - 1  - последний товар
                // floor - чтобы пользователь не переплатил финальную сумму
                $item->epayPriceKop = floor(($orderSumKop - $totalSumKop) / $item->amount);
            }
            $totalSumKop += (int)($item->epayPriceKop * $item->amount);
        }
        // В результате $totalSumKop <= $orderSumKop, т.к. последний товар мы сделали с такой ценой, чтобы не переплатить

        // 3. Отфильтруем возможные товары с получившейся нулевой ценой
        $positiveOrderItems = array_values(array_filter($positiveOrderItems, function ($item) {
            return $item->epayPriceKop > 0;
        }));

        if ($totalSumKop != $orderSumKop) {
            // 4. Выполним нормировку
            // 4.1. Отсортируем товары по убыванию количества
            usort($positiveOrderItems, function ($a, $b) {
                return ($b->amount * 1000) - ($a->amount * 1000); // x1000 для точности
            });
            // 4.2. Нормируем товары
            for ($i = 0; ($i < count($positiveOrderItems)) && (($delta = $orderSumKop - $totalSumKop) != 0); $i++) {
                $item = $positiveOrderItems[$i];
                if ($item->amount <= $delta) {
                    // Нормируем только в случае, если количество товара меньше недостающих копеек, иначе переплатим
                    $addPrice = floor($delta / $item->amount);
                    if (($item->epayPriceKop + $addPrice) <= ($item->realprice * 100)) { // Товар со скидкой не может быть дороже, чем без скидки
                        $item->epayPriceKop = $item->epayPriceKop + $addPrice;
                        $totalSumKop += round($addPrice * $item->amount);
                    }
                }
            }
        }


        // 5. Отсортируем товары как в оригинальном заказе
        $originalItems = $order->items;
        usort($positiveOrderItems, function ($a, $b) use ($originalItems) {
            $aIndex = array_search($a, $originalItems);
            $bIndex = array_search($b, $originalItems);
            return $aIndex - $bIndex;
        });

        return ['items' => $positiveOrderItems, 'sum' => $totalSumKop];
    }


    /**
     * Получает файл лога относительно папки логов
     * @return string
     */
    public function getLogFile(): string
    {
        $filename = static::EPAY_URN . '.log';
        return $filename;
    }


    /**
     * Записывает сообщение в лог (применяется в тестовом режиме)
     * @param string $message Текст сообщения
     * @param int $backtrace Глубина вложенности после вызывающей функции
     */
    public function doLog(string $message, $backtrace = 1)
    {
        $debugBacktrace = debug_backtrace();
        $methodName = $debugBacktrace[$backtrace]['function'] ?? '';
        $filename = Application::i()->baseDir . '/logs/' . $this->getLogFile();
        $logMessage = date('Y-m-d H:i:s') . ($methodName ? (' ' . $methodName) : '') . ': ' . "\n"
            . $message . "\n\n";
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }


    /**
     * Записывает в лог данные запроса и ответа
     * @param string $url URL запроса
     * @param string $request Текст запроса
     * @param string $response Текст ответа
     */
    public function doLogRequest(string $url, string $request = '', string $response = '')
    {
        $logData = [$url];
        if ($request) {
            $logData[] = 'ЗАПРОС:' . "\n" . $request;
        }
        $logData[] = 'ОТВЕТ:' . "\n" . $response;
        $logMessage = implode("\n", $logData);
        $this->doLog($logMessage, 2);
    }


    /**
     * Записывает сообщение в историю заказа
     * @param Order $order Заказ
     * @param string $message Сообщение
     * @param bool|null $paid Сменить статус оплаты
     */
    public function logHistory(Order $order, string $message, bool $paid = null)
    {
        if ($paid !== null) {
            $orderPaid = (int)$paid;
        } else {
            $orderPaid = (int)$order->paid;
        }
        $history = new Order_History([
            'order_id' => (int)$order->id,
            'status_id' => (int)$order->status_id,
            'paid' => $orderPaid,
            'post_date' => date('Y-m-d H:i:s'),
            'description' => $message
        ]);
        $history->commit();
    }


    /**
     * Получает виджет электронной оплаты
     * @return Snippet|null
     */
    public function getEPayWidget()
    {
        $snippet = Snippet::importByURN(static::EPAY_URN);
        if (!($snippet && $snippet->id)) {
            $snippet = Snippet::importByURN('epay');
        }
        return $snippet;
    }


    /**
     * Является ли статус успешным
     * @param int|string $status Внутреннее представление статуса
     * @return bool
     */
    public function isSuccessfulStatus($status): bool
    {
        return in_array($status, [static::REQUEST_STATUS_PAID, static::REQUEST_STATUS_HOLD]);
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
     * Применяет первичные платежные данные после регистрации заказа
     * @param Order $order Заказ
     * @param Block_Cart $block Блок
     * @param string $paymentId ID# оплаты
     * @param string $paymentURL Платежный URL
     */
    public function processInitialPaymentData(Order $order, Block_Cart $block, string $paymentId, string $paymentURL)
    {
        $order->payment_id = $paymentId;
        if ($block->epay_interface_classname) {
            $order->payment_interface_classname = (string)$block->epay_interface_classname;
            $order->payment_interface_id = 0;
        } elseif ($block->EPay_Interface->id) {
            $order->payment_interface_classname = '';
            $order->payment_interface_id = (int)$block->EPay_Interface->id;
        }
        $order->payment_url = $paymentURL;
        $order->commit();
        $logMessage = 'Зарегистрировано в системе ' . $this->getBankName()
            . ' (ID# заказа в системе банка: ' .  $order->payment_id . ', платежный URL: ' . $order->payment_url . ')';
        $this->logHistory($order, $logMessage);
        if ($block->epay_test) {
            $this->doLog((int)$order->id . ' / ' . $order->payment_id);
        }
    }


    /**
     * Применяет к заказу статус "оплачен", если такового еще не было
     */
    public function applyPaidStatus(Order $order)
    {
        if (!$order->paid) {
            $logMessage = 'Оплачено через ' . $this->getBankName()
                .  ' (ID# заказа в системе банка: ' .  $order->payment_id . ')';
            $this->logHistory($order, $logMessage, true);
            $order->paid = true;
            $order->commit();
        }
    }


    /**
     * Инициализация заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @return array <pre><code>[
     *     'paymentURL' ?=> string Платежный URL,
     *     'localError' ?=> string Массив ошибок в текстовом виде
     * ]</code></pre>
     */
    public function init(Order $order, Block_Cart $block, Page $page)
    {
        $_SESSION['orderId'] = $this->session['orderId'] = $orderId = $order->id;
        $originalResponse = $this->registerOrder($order, $block, $page);
        $response = $this->parseInitResponse($originalResponse);

        $localError = [];
        if (!$originalResponse) {
            $localError[] = 'Не удалось получить результат запроса на оплату';
        } elseif ($response['errors'] ?? null) {
            foreach ((array)$response['errors'] as $error) {
                $errorData = [];
                if ($error['code'] ?? null) {
                    $errorData[] = '#' . $error['code'];
                }
                if ($error['message'] ?? null) {
                    $errorData[] = $error['message'];
                }
                $localError[] = 'В процессе регистрации заказа возникла ошибка: ' . implode(' ', $errorData);
            }
        } elseif (!($response['paymentId'] ?? null) || !($response['paymentURL'] ?? null)) {
            $localError[] = 'Не удалось получить адрес для оплаты';
        }

        if ($localError) {
            if ($block->epay_test) {
                $this->doLog(var_export($localError, true));
            }
            return ['localError' => $localError];
        } else {
            $this->processInitialPaymentData($order, $block, $response['paymentId'], $response['paymentURL']);
            // 2022-07-12, AVS: сделал поддержку AJAX'а
            if (($this->post['AJAX'] ?? null) || ($page->mime == 'application/json')) {
                return ['redirectUrl' => $order->payment_url];
            // @codeCoverageIgnoreStart
            // Не могу тестировать редирект
            } else {
                new Redirector($order->payment_url);
                exit;
            }
            // @codeCoverageIgnoreEnd
        }
    }


    /**
     * Проверка заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @return array <pre><code>[
     *     'success' ?=> array<int[] ID# блока => string сообщение об успешной операции>,
     *     'localError' ?=> array<string[] URN поля ошибки => string сообщение об ошибке>
     * ]</code></pre>
     */
    public function result(Order $order, Block_Cart $block, Page $page): array
    {
        if ($order->id) {
            $orderIsPaid = $this->getOrderIsPaid($order, $block, $page);
            if ($orderIsPaid) {
                $this->applyPaidStatus($order);
                $out['success'][(int)$block->id] = sprintf(ORDER_SUCCESSFULLY_PAID, $order->id);
            } else {
                $out['localError'] = ['order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $order->id)];
            }
            if ($webhookData = $this->checkWebhook()) {
                // @codeCoverageIgnoreStart
                while (ob_get_level()) {
                    ob_end_clean();
                }
                $this->processWebhookResponse($order, $block, $page, $webhookData);
                exit;
                // @codeCoverageIgnoreEnd
            }
        } else {
            $out['localError'] = ['order' => INVALID_CRC];
        }
        return $out;
    }


    /**
     * Проверяет, был ли отправлен Webhook
     * @return array|null <pre><code>[
     *     'orderId' =>? string ID# заказа,
     *     'paymentId' =>? string ID# платежа
     * ]</code></pre> Данные вебхука или null, если вебхук не обнаружен
     */
    public function checkWebhook()
    {
        return null;
    }


    /**
     * Отправляет ответ на webhook
     * @param Order $order Обрабатываемый заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @param array $webhookData <pre><code>[
     *     'orderId' =>? string ID# заказа,
     *     'paymentId' =>? string ID# платежа
     * ]</code></pre>Данные вебхука
     * @return mixed
     * @codeCoverageIgnore Просто заглушка
     */
    public function processWebhookResponse(Order $order, Block_Cart $block, Page $page, array $webhookData)
    {
    }


    /**
     * Регистрирует заказ
     * @param Order $order Текущий заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @return array Ответ сервера
     */
    public function registerOrder(Order $order, Block_Cart $block, Page $page): array
    {
        $requestData = $this->getRegisterOrderData($order, $block, $page);
        $response = $this->registerOrderWithData($order, $block, $page, $requestData);
        return $response;
    }


    /**
     * Получает статус оплаты заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return bool Статус оплаты заказа
     * @throws Exception Ошибка при выполнении
     */
    public function getOrderIsPaid(Order $order, Block_Cart $block, Page $page): bool
    {
        if (!$order->payment_id) {
            $errorText = 'Заказ не отправлен на оплату';
            throw new Exception($errorText);
        }
        $requestData = $this->getOrderStatusData($order, $block, $page);
        $originalResponse = $this->getOrderStatusWithData($order, $block, $page, $requestData);
        $response = $this->parseOrderStatusResponse($originalResponse);

        $errorText = null;
        if (!$originalResponse) {
            $errorText = 'Не удалось получить результат запроса состояния заказа';
        } elseif ($error = ($response['errors'][0] ?? null)) {
            $errorData = [];
            if ($error['code'] ?? null) {
                $errorData[] = '#' . $error['code'];
            }
            if ($error['message'] ?? null) {
                $errorData[] = $error['message'];
            }
            $errorText = 'В процессе оплаты заказа' . ($order->id ? ' #' . $order->id : '')
                . ' возникла ошибка: ' . implode(' ', $errorData);
            $this->logHistory($order, $errorText, false);
        } elseif (!($response['status'] ?? null)) {
            $errorText = 'Не удалось получить статус состояния заказа';
        }

        if ($errorText) {
            if ($block->epay_test) {
                $this->doLog($errorText);
            }
            throw new Exception($errorText);
        }

        return $this->isSuccessfulStatus($response['status']);
    }


    /**
     * Получает название банка
     * @return string
     */
    public function getBankName(): string
    {
        return static::BANK_NAME;
    }


    /**
     * Получает URL API банка
     * @param bool $isTest Тестовый режим
     * @return string
     */
    abstract public function getURL(bool $isTest = false): string;


    /**
     * Получает данные для регистрации заказа
     * @param Order $order Текущий заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @return array
     */
    abstract public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array;

    /**
     * Регистрирует заказ полученными данными
     * @param Order $order Текущий заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @param array $data Данные для регистрации
     * @return array Ответ сервера
     */
    abstract public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array;


    /**
     * Разбирает общие ошибки ответа при инициализации
     * @param array $response Ответ при инициализации
     * @return array <pre><code>array<[
     *     'code' => string Код ошибки,
     *     'message' => string Сообщение об ошибке
     * ]></code></pre>
     */
    abstract public function parseResponseCommonErrors(array $response): array;


    /**
     * Разбирает данные ответа при инициализации
     * @param array $response Ответ при инициализации
     * @return array <pre><code>[
     *     'errors' =>? array<[
     *         'code' => string Код ошибки,
     *         'message' => string Сообщение об ошибке
     *     ]>,
     *     'paymentId' =>? string ID# платежа,
     *     'paymentURL' =>? string Платежный URL,
     *     string[] => mixed Дополнительные данные
     * ]</code></pre>
     */
    abstract public function parseInitResponse(array $response): array;


    /**
     * Получает данные для проверки заказа
     * @param Order $order Текущий заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @return array
     */
    public function getOrderStatusData(Order $order, Block_Cart $block, Page $page): array
    {
        return [];
    }


    /**
     * Получает состояние заказа подготовленные данные
     * @param Order $order Текущий заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @param array $data Данные для получения заказа
     * @return array Ответ сервера
     */
    abstract public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array;

    /**
     * Разбирает данные ответа при получении состояния заказа
     * @param array $response Ответ при получении состояния заказа
     * @return array <pre><code>[
     *     'errors' =>? array<[
     *         'code' => string Код ошибки,
     *         'message' => string Сообщение об ошибке
     *     ]>,
     *     'status' =>? string Внутреннее состояние оплаты,
     * ]</code></pre>
     */
    abstract public function parseOrderStatusResponse(array $response): array;


    /**
     * Обращается к интерфейсу банка
     * @param string $method Метод для обращения
     * @param array $requestData Данные запроса
     * @param bool $isTest Тестовый режим
     * @return array Данные ответа
     */
    abstract public function exec(string $method, array $requestData = [], bool $isTest = false): array;
}
