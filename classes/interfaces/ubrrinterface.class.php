<?php
/**
 * Файл класса интерфейса электронной оплаты через Уральский банк реконструкции и развития
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SimpleXMLElement;
use SOME\Text;
use RAAS\Application;
use RAAS\Exception;
use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс интерфейса электронной оплаты через Уральский банк реконструкции и развития
 * @deprecated 2024-05-01, AVS: Используется API PayKeeper
 */
class UBRRInterface extends EPayInterface
{
    const EPAY_URN = 'ubrr';

    const BANK_NAME = 'УБРиР';

    const REQUEST_STATUS_HOLD = 'APPROVED';

    const REQUEST_STATUS_PAID = 'PREAUTH-APPROVED';

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
     * Проверка заказа
     * @param Order $order Заказ для проверки
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @return [
     *             'success' ?=> array<int[] ID# блока => сообщение об успешной операции>,
     *             'localError' ?=> array<string[] URN поля ошибки => сообщение об ошибке>
     *         ]
     */
    public function result(Order $order, Block_Cart $block, Page $page): array
    {
        if ($this->session['ubrrSession'] ?? null) {
            if ($block->epay_test) {
                $this->doLog('UBRR Session ID: ' . $this->session['ubrrSession']);
            }
            $out = parent::result($order, $block, $page);
        } else {
            $out['localError'] = ['order' => INVALID_CRC];
        }
        return $out;
    }


    /**
     * Обращается к интерфейсу банка
     * @param string $method Метод для обращения
     * @param array $requestData Данные запроса
     * @param bool $isTest Тестовый режим
     * @param string $certPass Пароль сертификата
     * @return array Данные ответа
     * @codeCoverageIgnore Не смогу получить многие данные из-за отсутствия валидного сертификата для тестирования
     */
    public function exec(string $method, array $requestData = [], bool $isTest = false, string $certPass = ''): array
    {
        $data = ['TKKPG' => ['Request' => array_merge(['Operation' => $method], $requestData)]];

        $xml = $this->arrayToXML($data);
        $url = $this->getURL($isTest);


        $caFile = $this->getBankCertificate(); // корневой сертификат банка
        $certFile = $this->getClientCertificate(); // сертификат торговца
        $keyFile = $this->getClientKey(); // ключ сертификата

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($caFile && is_file($caFile)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_CAINFO, $caFile);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_POST, 1);
        if ($certFile && is_file($certFile)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        }
        if ($keyFile && is_file($keyFile)) {
            curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        }
        if ($certPass) {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPass);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_VERBOSE, 1); // Для проверки - вывод в stdout
        // ответ TWPG
        $response = curl_exec($ch);
        if ($isTest) {
            $this->doLogRequest($url, $xml, (string)$response);
        }
        // 0 - успешное выполнение запроса
        $errorCode = (int)curl_errno($ch);
        if ($errorCode) {
            $result['errorCode'] = $errorCode;
            $result['errorMessage'] = 'cURL error: ' . curl_error($ch);
            return $result;
        }

        $sxe = new SimpleXMLElement($response);
        $sxeResponse = $sxe->Response;
        $result = [];
        if (trim((string)$sxeResponse->Status) == static::REQUEST_STATUS_SUCCESS) {
            $result = $this->sxeToArray($sxeResponse);
            unset($result['Status'], $result['Operation']);
        } else {
            $result['errorCode'] = (int)$sxeResponse->Status;
            switch (trim((string)$sxeResponse->Status)) {
                case static::REQUEST_STATUS_INVALID_FORMAT:
                    $result['errorMessage'] = 'Invalid format';
                    break;
                case static::REQUEST_STATUS_ACCESS_DENIED:
                    $result['errorMessage'] = 'Access denied';
                    break;
                case static::REQUEST_STATUS_INVALID_OPERATION:
                    $result['errorMessage'] = 'Invalid operation';
                    break;
                case static::REQUEST_STATUS_SYSTEM_ERROR:
                    $result['errorMessage'] = 'System error';
                    break;
                default:
                    $result['errorMessage'] = 'Unknown error';
                    break;
            }
        }
        return $result;
    }


    /**
     * Получает путь к корневому сертификату банка
     * @return string
     */
    public function getBankCertificate()
    {
        $caFile = realpath(Application::i()->baseDir . '/../bank.crt');
        return $caFile;
    }


    /**
     * Получает путь к сертификату клиента
     * @return string
     */
    public function getClientCertificate()
    {
        $certFile = realpath(Application::i()->baseDir . '/../user.crt');
        return $certFile;
    }


    /**
     * Получает путь к ключу клиента
     * @return string
     */
    public function getClientKey()
    {
        $keyFile = realpath(Application::i()->baseDir . '/../user.key');
        return $keyFile;
    }


    public function getURL(bool $isTest = false): string
    {
        if ($isTest) {
            $url = 'https://91.208.121.69:7443/Exec';
        } else {
            $url = 'https://twpg.ubrr.ru:8443/Exec';
        }
        return $url;
    }


    /**
     * Получение XML для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @param string $emailField URN поля E-mail
     * @param string $phoneField URN поля Телефон
     * @return array
     */
    public function getRegisterOrderData(
        Order $order,
        Block_Cart $block,
        Page $page,
        $emailField = 'email',
        $phoneField = 'phone'
    ): array {
        $pageURL = htmlspecialchars($this->getCurrentHostURL() . $page->url);
        $result = [
            'Language' => mb_strtoupper($page->lang) ?: 'RU',
            'Order' => [
                'OrderType' => 'Purchase',
                'Merchant' => $block->epay_login,
                'Amount' => ceil($order->sum * 100),
                'Currency' => $this->getCurrency($block->epay_currency),
                'Description' => $this->getOrderDescription($order),
                'ApproveURL' => $pageURL . 'result/',
                'CancelURL' => $pageURL . 'result/',
                'DeclineURL' => $pageURL . 'result/',
            ],
        ];
        $faData = [];
        if ($emailField && ($email = $order->$emailField)) {
            $result['Order']['email'] = $email;
            $faData[] = 'Email=' . htmlspecialchars($email);
        }
        if ($phoneField && ($phone = $order->$phoneField)) {
            $phone = Text::beautifyPhone($phone, 11);
            $result['Order']['phone'] = $phone;
            $faData[] = 'Phone=' . htmlspecialchars($phone);
        }
        if ($faData) {
            $result['Order']['AddParams']['FA-DATA'] = implode('; ', $faData);
        }
        return $result;
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $response = $this->exec('CreateOrder', $data, (bool)$block->epay_test, (string)$block->epay_pass1);
        return $response;
    }


    public function parseResponseCommonErrors(array $response): array
    {
        $result = [];
        if ($response['errorCode'] ?? null) {
            $result[] = ['code' => $response['errorCode'], 'message' => ($response['errorMessage'] ?? '')];
        }
        return $result;
    }


    public function parseInitResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($paymentId = $response['Order']['OrderID'] ?? null) &&
            ($sessionId = $response['Order']['SessionID'] ?? null) &&
            ($responseURL = $response['Order']['URL'] ?? null)
        ) {
            $result['paymentId'] = $paymentId;
            $result['sessionId'] = $sessionId;
            $result['paymentURL'] = $responseURL . '?ORDERID=' . urlencode($paymentId)
                . '&SESSIONID=' . urlencode($sessionId);
            $_SESSION['ubrrSession'] = $this->session['ubrrSession'] = $sessionId;
        }
        return $result;
    }


    public function getOrderStatusData(Order $order, Block_Cart $block, Page $page): array
    {
        $result = [
            'Language' => mb_strtoupper($page->lang) ?: 'RU',
            'Order' => [
                'Merchant' => $block->epay_login,
                'OrderID' => $order->payment_id,
            ],
            'SessionID' => ($this->session['ubrrSession'] ?? ''),
        ];
        return $result;
    }


    public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        return $this->exec('GetOrderStatus', $data, (bool)$block->epay_test, (string)$block->epay_pass1);
    }


    public function parseOrderStatusResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if ($status = $response['Order']['OrderStatus'] ?? null) {
            $result['status'] = $status;
        }
        return $result;
    }


    /**
     * Получает валюту заказа
     * @param string $currency Валюта заказа в текстовом виде
     * @return int
     */
    public function getCurrency(string $currency): int
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
     * Возвращает данные SimpleXMLElement в виде массива
     *
     * (при этом теряя атрибуты, но сохраняя дочерние узлы)
     * @param SimpleXMLElement $sxe SimpleXMLElement для разбора
     * @return array
     */
    public function sxeToArray(SimpleXMLElement $sxe)
    {
        $out = [];
        foreach ((array)$sxe as $index => $node) {
            $out[$index] = is_object($node) ? $this->sxeToArray($node) : trim((string)$node);
        }
        return $out;
    }


    /**
     * Преобразует массив данных в XML
     * @param array $data Данные для преобразования
     * @param bool $root Корневой вызов (добавить заголовки)
     * @return string
     */
    public function arrayToXML(array $data, bool $root = true): string
    {
        $result = '';
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = $this->arrayToXML($val, false);
            } else {
                $val = htmlspecialchars($val);
            }
            $result .= '<' . $key . '>' . $val . '</' . $key . '>';
        }
        if ($root) {
            $result = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $result;
        }
        return $result;
    }
}
