<?php
/**
 * Файл класса интерфейса электронной оплаты через Уральский банк реконструкции и развития
 */
namespace RAAS\CMS\Shop;

use RAAS\Exception;
use SimpleXMLElement;
use RAAS\CMS\Snippet;
use RAAS\CMS\Page;
use RAAS\Application;

/**
 * Класс интерфейса электронной оплаты через Уральский банк реконструкции и развития
 */
class UBRRInterface extends EPayInterface
{
    /**
     * Значение поля Status при создании заказа: успешно
     */
    const REQUEST_STATUS_SUCCESS = '00';

    /**
     * Значение поля Status при создании заказа: неверный формат сообщения
     * (нет обязательных полей и т.д.)
     */
    const REQUEST_STATUS_INVALID_FORMAT = '30';

    /**
     * Значение поля Status при создании заказа: интернет-магазин не имеет доступа
     * к операции создания заказа (или такой интернет-магазин не зарегистрирован)

     */
    const REQUEST_STATUS_ACCESS_DENIED = '10';

    /**
     * Значение поля Status при создании заказа: недопустимая операция
     */
    const REQUEST_STATUS_INVALID_OPERATION = '54';

    /**
     * Значение поля Status при создании заказа: системная ошибка
     */
    const REQUEST_STATUS_SYSTEM_ERROR = '96';

    /**
     * Валюта заказа: рубли
     */
    const CURRENCY_RUB = 643;

    /**
     * Валюта заказа: доллары США
     */
    const CURRENCY_USD = 840;

    /**
     * Обработка интерфейса
     * @param Order $order Текущий заказ, для которого осуществляем обработку
     * @return array(
     *             'epayWidget' ?=> Snippet Виджет оплаты
     *             'Item' ?=> Order Текущий заказ
     *             'success' ?=> array<int[] ID# блока => сообщение об успешной операции>,
     *             'localError' ?=> array<string[] URN поля ошибки => сообщение об ошибке>,
     *             'paymentURL' ?=> string Платежный URL,
     *         )
     */
    public function process(Order $order = null)
    {
        $out = array();
        try {
            if (in_array($this->get['action'], array('result', 'fail')) ||
                ($order->id && $this->post['epay'])
            ) {
                if (in_array($this->get['action'], array('result', 'fail'))) {
                    if (!($order && $order->id) && $this->session['orderId']) {
                        $order = new Order($this->session['orderId']);
                    }
                }
                switch ($this->get['action']) {
                    case 'result':
                        $out = $this->result($order, $this->block, $this->page, $this->session);
                        break;
                    case 'fail':
                        $out = $this->fail($order, $this->block, $this->page, $this->session);
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
            $out['localError'] = array('order' => $exception->getMessage() . ' #' . $exception->getCode());
        }
        return $out;
    }


    /**
     * Проверка заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param array $session Данные сессии
     * @return array(
     *             'success' ?=> array<int[] ID# блока => сообщение об успешной операции>,
     *             'localError' ?=> array<string[] URN поля ошибки => сообщение об ошибке>
     *         )
     */
    public function result(Order $order, Block_Cart $block, Page $page, array $session = array())
    {
        if ($session['ubrrOrderId'] && $session['ubrrSession'] && $order->id) {
            if ($block->epay_test) {
                file_put_contents(
                    'ubrr.log',
                    date('Y-m-d H:i:s ') . 'result: ' .
                    $session['ubrrOrderId'] . ' / ' . $session['ubrrSession'] .
                    "\n",
                    FILE_APPEND
                );
            }
            $orderIsPaid = $this->getOrderIsPaid(
                $order,
                $block,
                $page,
                $session
            );
            if ($orderIsPaid) {
                $history = new Order_History();
                $history->uid = Application::i()->user->id;
                $history->order_id = (int)$order->id;
                $history->status_id = (int)$order->status_id;
                $history->paid = 1;
                $history->post_date = date('Y-m-d H:i:s');
                $history->description = 'Оплачено через интернет-эквайринг УБРиР'
                                      . ' (ID# заказа в системе банка: ' . $session['ubrrOrderId'] . ')';
                $history->commit();

                $order->paid = 1;
                $order->commit();
                $out['success'][(int)$block->id] = sprintf(ORDER_SUCCESSFULLY_PAID, $order->id);
            } else {
                $out['localError'] = array('order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $order->id));
            }
        } else {
            $out['localError'] = array('order' => INVALID_CRC);
        }
        return $out;
    }


    /**
     * Обработка неудачного завершения
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param array $session Данные сессии
     * @return array(
     *             'localError' ?=> array<string[] URN поля ошибки => сообщение об ошибке>
     *         )
     */
    public function fail(Order $order, Block_Cart $block, Page $page, array $session = array())
    {
        if ($block->epay_test) {
            file_put_contents(
                'ubrr.log',
                date('Y-m-d H:i:s ') . 'fail: ' .
                $session['ubrrOrderId'] . ' / ' . $session['ubrrSession'] .
                "\n",
                FILE_APPEND
            );
            $orderIsPaid = $this->getOrderIsPaid(
                $order,
                $block,
                $page,
                $session
            );
        }
        $out = array();
        $out['localError']['order'] = sprintf(ORDER_HAS_NOT_BEEN_PAID, $order->id);
        return $out;
    }


    /**
     * Инициализация заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array(
     *             'paymentURL' ?=> string Платежный URL,
     *         )
     */
    public function init(Order $order, Block_Cart $block, Page $page)
    {
        $out = array();
        $_SESSION['orderId'] = $order->id;
        $response = $this->registerOrder($order, $block, $page);
        $orderId = $response['Order']['OrderID'];
        $sessionId = $response['Order']['SessionID'];
        $out['paymentURL'] = $response['Order']['URL']
                           . '?ORDERID=' . urlencode($orderId)
                           . '&SESSIONID=' . urlencode($sessionId);
        $out['requestForPayment'] = true;
        $_SESSION['ubrrSession'] = $sessionId;
        $_SESSION['ubrrOrderId'] = $orderId;
        if ($block->epay_test) {
            file_put_contents(
                'ubrr.log',
                date('Y-m-d H:i:s ') . 'init: ' . $orderId . ' / ' .
                $sessionId . "\n",
                FILE_APPEND
            );
        }
        return $out;
    }


    /**
     * Обращается к интерфейсу банка
     * @param string $xml XML-сообщение для отправки банку
     * @param Block_Cart $block Блок с настройками
     * @return string XML-сообщение ответа
     */
    public function exec($xml, Block_Cart $block)
    {
        // file_put_contents(
        //     'ubrr.log',
        //     date('Y-m-d H:i:s ') . 'exec XML: ' .
        //     $session['ubrrOrderId'] . ' / ' . $session['ubrrSession'] .
        //     "\n" . $xml . "\n",
        //     FILE_APPEND
        // );
        $isTest = (bool)(int)$block->epay_test;
        $url = $this->getURL($isTest);

        // корневой сертификат банка
        $caFile = $this->getBankCertificate();
        // сертификат торговца
        $certFile = $this->getClientCertificate();
        // ключ сертификата
        $keyFile = $this->getClientKey();
        // пароль ключа (если есть)
        $privateCertPass = $block->epay_pass1;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (is_file($caFile)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_CAINFO, $caFile);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_POST, 1);
        if (is_file($certFile)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        }
        if (is_file($keyFile)) {
            curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        }
        if ($privateCertPass) {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $privateCertPass);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        // ответ TWPG
        $result = curl_exec($ch);
        // 0 - успешное выполнение запроса
        $errorCode = (int)curl_errno($ch);
        // file_put_contents(
        //     'ubrr.log',
        //     date('Y-m-d H:i:s ') . 'exec RESPONSE: ' .
        //     $result . "\n" . $errorCode . "\n",
        //     FILE_APPEND
        // );
        if ($errorCode) {
            throw new Exception('cURL error: ' . curl_error($ch), $errorCode);
        }

        return $result;
    }


    /**
     * Получает путь к корневому сертификату банка
     * @return string
     */
    public function getBankCertificate()
    {
        $caFile = realpath('../bank.crt');
        return $caFile;
    }


    /**
     * Получает путь к сертификату клиента
     * @return string
     */
    public function getClientCertificate()
    {
        $certFile = realpath('../user.crt');
        return $certFile;
    }


    /**
     * Получает путь к ключу клиента
     * @return string
     */
    public function getClientKey()
    {
        $keyFile = realpath('../user.key');
        return $keyFile;
    }



    /**
     * Получает URL API банка
     * @param bool $isTest Тестовый режим
     * @return string
     * @todo
     */
    public function getURL($isTest = false)
    {
        if ($isTest) {
            $url = 'https://91.208.121.69:7443/Exec';
        } else {
            $url = 'https://twpg.ubrr.ru:8443/Exec';
        }
        return $url;
    }


    /**
     * Является ли статус успешным
     * @param string $status Внутреннее представление статуса
     */
    public function isSuccessfulStatus($status)
    {
        return in_array($status, array('APPROVED', 'PREAUTH-APPROVED'));
    }


    /**
     * Получение XML для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param string $emailField URN поля E-mail
     * @param string $phoneField URN поля Телефон
     * @return string
     */
    public function getRegisterOrderXML(Order $order, Block_Cart $block, Page $page, $emailField = 'email', $phoneField = 'phone')
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . '<TKKPG>'
             .   '<Request>'
             .     '<Operation>CreateOrder</Operation>'
             .     '<Language>' . mb_strtoupper($page->lang) . '</Language>'
             .     '<Order>'
             .       '<OrderType>Purchase</OrderType>'
             .       '<Merchant>' . htmlspecialchars($block->epay_login) . '</Merchant>'
             .       '<Amount>' . ceil($order->sum * 100) . '</Amount>'
             .       '<Currency>' . $this->getCurrency($block->epay_currency) . '</Currency>'
             .       '<Description>' . htmlspecialchars($this->getOrderDescription($order)) . '</Description>'
             .       '<ApproveURL>' . htmlspecialchars($this->getCurrentHostURL() . $page->url) . '?action=result</ApproveURL>'
             .       '<CancelURL>' . htmlspecialchars($this->getCurrentHostURL() . $page->url) . '?action=fail</CancelURL>'
             .       '<DeclineURL>' . htmlspecialchars($this->getCurrentHostURL() . $page->url) . '?action=fail</DeclineURL>';
        $faData = array();
        if ($emailField && ($email = $order->$emailField)) {
            $xml .=  '<email>' . htmlspecialchars($email) . '</email>';
            $faData[] = 'Email=' . htmlspecialchars($email);
        }
        if ($phoneField && ($phone = $order->$phoneField)) {
            $xml .=  '<phone>' . htmlspecialchars($phone) . '</phone>';
            $faData[] = 'Phone=' . htmlspecialchars($phone);
        }
        if ($faData) {
            $xml .=  '<AddParams>'
                 .     '<FA-DATA>' . implode('; ', $faData) . '</FA-DATA>'
                 .   '</AddParams>';
        }
        $xml .=    '</Order>'
             .   '</Request>'
             . '</TKKPG>';
        return $xml;
    }


    /**
     * Регистрирует заказ
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array Ответ сервера
     * @throws Exception Ошибка при выполнении
     */
    public function registerOrder(Order $order, Block_Cart $block, Page $page)
    {
        $xml = $this->getRegisterOrderXML($order, $block, $page);
        $responseXML = $this->exec($xml, $block);
        $response = $this->parseResponseXML($responseXML);
        return $response;
    }


    /**
     * Получает валюту заказа
     * @param string $currency Валюта заказа в текстовом виде
     * @return int
     */
    public function getCurrency($currency)
    {
        $currency = mb_strtoupper($currency);
        switch ($currency) {
            case 'USD':
                return static::CURRENCY_USD;
                break;
            default:
                return static::CURRENCY_RUB;
                break;
        }
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
     * Разбирает произвольный ответ
     * @param string $xml Текст XML ответа
     * @throws Exception Ошибка при выполнении
     */
    public function parseResponseXML($xml)
    {
        $sxe = new SimpleXMLElement($xml);
        $response = $sxe->Response;
        if (trim($response->Status) == static::REQUEST_STATUS_SUCCESS) {
            $data = $this->sxeToArray($response);
            unset($data['Status'], $data['Operation']);
            return $data;
        } else {
            $errorMessage = $this->getRequestStatusMessage(trim($response->Status));
            throw new Exception($errorMessage, (int)$response->Status);
        }
    }


    /**
     * Возвращает данные SimpleXMLElement в виде массива
     *
     * (при этом теряя атрибуты, но сохраняя дочерние узлы)
     * @param SimpleXMLElement $sxe SimpleXMLElement для разбора
     * @return array
     */
    public function sxeToArray(SimpleXMLElement $sxe)
    {
        $out = array();
        foreach ((array)$sxe as $index => $node) {
            $out[$index] = is_object($node) ? $this->sxeToArray($node) : trim($node);
        }
        return $out;
    }


    /**
     * Получает текстовое сообщение об ошибке по статусу ответа
     * @param string $requestStatus Код статуса ответа в константах static::REQUEST_STATUS_
     * @return string
     */
    public function getRequestStatusMessage($requestStatus)
    {
        switch ($requestStatus) {
            case static::REQUEST_STATUS_INVALID_FORMAT:
                return 'Invalid format';
                break;
            case static::REQUEST_STATUS_ACCESS_DENIED:
                return 'Access denied';
                break;
            case static::REQUEST_STATUS_INVALID_OPERATION:
                return 'Invalid operation';
                break;
            case static::REQUEST_STATUS_SYSTEM_ERROR:
                return 'System error';
                break;
            default:
                return 'Unknown error';
                break;
        }
    }


    /**
     * Получение XML для получения статуса заказа
     * @param string $orderId ID# заказа в системе банка
     * @param string $merchantName Идентификатор интернет-магазина в TWEC PG
     *                             - должен совпадать с Common Name X.509
     *                             сертификата сервера интернет-магазина,
     *                             используемого в качестве клиентского
     *                             сертификата при установке SSL-соединения
     * @param 'ru'|'en' $language Язык
     * @param string $sessionId ID# сессии
     * @return string
     */
    public function getOrderStatusXML($orderId, $merchantName, $language, $sessionId)
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . '<TKKPG>'
             .   '<Request>'
             .     '<Operation>GetOrderStatus</Operation>'
             .     '<Language>' . mb_strtoupper($language) . '</Language>'
             .     '<Order>'
             .       '<Merchant>' . htmlspecialchars($merchantName) . '</Merchant>'
             .       '<OrderID>' . htmlspecialchars($orderId) . '</OrderID>'
             .     '</Order>'
             .     '<SessionID>' . htmlspecialchars($sessionId) . '</SessionID>'
             .   '</Request>'
             . '</TKKPG>';
        return $xml;
    }


    /**
     * Получает статус оплаты заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param array $session Данные сессии
     * @return bool Заказ успешно оплачен
     * @throws Exception Ошибка при выполнении
     */
    public function getOrderIsPaid(Order $order, Block_Cart $block, Page $page, array $session = array())
    {
        $xml = $this->getOrderStatusXML(
            $session['ubrrOrderId'],
            $block->epay_login,
            $page->lang,
            $session['ubrrSession']
        );
        $responseXML = $this->exec($xml, $block);
        $response = $this->parseResponseXML($responseXML);
        if ($block->epay_test) {
            file_put_contents(
                'ubrr.log',
                date('Y-m-d H:i:s ') . 'getOrderIsPaid: ' . $session['ubrrOrderId'] . ' / ' .
                $session['ubrrSession'] . ' = ' . $responseXML . "\n",
                FILE_APPEND
            );
        }
        return $this->isSuccessfulStatus($response['Order']['OrderStatus']);
    }


    /**
     * Получает виджет электронной оплаты
     * @return Snippet
     */
    public function getEPayWidget()
    {
        return Snippet::importByURN('ubrr');
    }
}
