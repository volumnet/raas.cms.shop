<?php
/**
 * Файл класса интерфейса электронной оплаты через PayMaster
 */
declare(strict_types=1);

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
    const EPAY_URN = 'paymaster';

    const BANK_NAME = 'PayMaster';

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

    public function findOrder()
    {
        if ($orderId = $this->get['orderId'] ?? null) { // Возврат на страницу (передавали в return_url)
            $order = new Order($orderId);
            if ($order && $order->id) {
                return $order;
            }
        }
        return parent::findOrder();
    }


    /**
     * Проверяет, был ли отправлен Webhook
     * @param ?string $debugInput Проверочный входной запрос для отладки
     * @return array|null <pre><code>[
     *     'orderId' =>? string ID# заказа,
     *     'paymentId' =>? string ID# платежа
     * ]</code></pre> Данные вебхука или null, если вебхук не обнаружен
     */
    public function checkWebhook(?string $debugInput = null)
    {
        if (($this->server['REQUEST_METHOD'] ?? '') == 'POST') {
            $request = $this->post['@raw'] ?: file_get_contents('php://input'); // $this->post['@raw'] для проверки
            if (substr($request, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $request = substr($request, 3);
            }
            $json = @json_decode($request, true);
            if ($json) {
                if ($paymentId = $json['id'] ?? null) {
                    return ['paymentId' => $paymentId];
                }
            }
        }
        return null;
    }


    /**
     * Обращается к интерфейсу банка
     * @param string $method Метод для обращения
     * @param array $requestData Данные запроса
     * @param bool $isTest Тестовый режим
     * @param bool $isPut PUT-протокол
     * @return array Данные ответа
     */
    public function exec(string $method, array $requestData = [], bool $isTest = false, bool $isPut = false): array
    {
        $login = $password = '';
        if ($this->block) {
            $login = (string)$this->block->epay_login;
            $password = (string)$this->block->epay_pass1;
        }
        $idempotencyKey = crc32(md5($password) . $method . json_encode($requestData));
        $headers = [
            'Idempotency-Key: ' . $idempotencyKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($password) {
            $headers[] = 'Authorization: Bearer ' . $password;
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
        $request = '';
        if ($requestData) {
            if ($login) {
                $requestData['merchantId'] = $login;
            }
            if ($isTest) {
                $requestData['testMode'] = true;
            }
            $request = json_encode($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        }
        $result = curl_exec($ch);
        if ($isTest) {
            $this->doLogRequest($url, $request, $result);
        }
        $json = (array)@json_decode($result, true);
        return $json;
    }


    public function getURL(bool $isTest = false): string
    {
        $url = 'https://paymaster.ru/api/v2/';
        return $url;
    }


    /**
     * Получение данные для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array
     */
    public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array
    {
        $pageUrl = $this->getCurrentHostURL() . $page->url;
        // 2024-05-01, AVS: убрал additionalURL (может запутывать)

        $cartData = [];
        $positiveItemsData = $this->getPositiveItems($order);
        $positiveItems = $positiveItemsData['items'];
        $totalSumKop = $positiveItemsData['sum'];
        foreach ($positiveItems as $i => $item) {
            $itemData = [
                'name' => $item->name,
                'quantity' => (int)$item->amount,
                'price' => (float)($item->epayPriceKop / 100),
                'vatType' => $order->taxType ?: static::TAX_TYPE_NO_VAT,
                'paymentSubject' => $order->paymentSubject ?: static::PAYMENT_SUBJECT_COMMODITY,
                'paymentMethod' => $order->paymentMethod ?: static::PAYMENT_METHOD_FULL_PREPAYMENT,
            ];
            $cartData[] = $itemData;
        }

        $currency = $block->epay_currency;
        if (!$currency || ($currency == 'RUR')) {
            $currency = 'RUB';
        }
        $ip = '0.0.0.0';
        if ($this->server['HTTP_X_FORWARDED_FOR'] ?? '') {
            $forwardedFor = explode(',', (string)$this->server['HTTP_X_FORWARDED_FOR']);
            $forwardedFor = array_map('trim', $forwardedFor);
            $ip = $forwardedFor[0];
        } elseif (isset($this->server['REMOTE_ADDR'])) {
            $ip = $this->server['REMOTE_ADDR'];
        }
        $requestData = [
            'invoice' => [
                'description' => $this->getOrderDescription($order),
                'orderNo' => trim((string)$order->id),
            ],
            'amount' => [
                'value' => (float)($totalSumKop / 100),
                'currency' => $currency,
            ],
            'protocol' => [
                'returnUrl' => $pageUrl . 'result/?orderId=' . (int)$order->id,
                'callbackUrl' => $pageUrl . 'result/?orderId=' . (int)$order->id,
            ],
            'customer' => [
                'ip' => $ip,
            ],
            'receipt' => [
                'client' => [],
                'items' => $cartData,
            ],
        ];
        if (trim((string)$order->email) !== '') {
            $requestData['customer']['email'] = trim((string)$order->email);
            $requestData['receipt']['client']['email'] = trim((string)$order->email);
        }
        if (trim((string)$order->phone) !== '') {
            $requestData['customer']['phone'] = Text::beautifyPhone($order->phone, 11);
            $requestData['receipt']['client']['phone'] = Text::beautifyPhone($order->phone, 11);
        }
        if (trim((string)$order->inn)) {
            $requestData['receipt']['client']['inn'] = trim((string)$order->inn);
        }
        if ((int)$order->uid) {
            $requestData['customer']['account'] = trim((string)$order->uid);
        }

        return $requestData;
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $response = $this->exec('payments', $data, (bool)$block->epay_test);
        return $response;
    }


    public function parseResponseCommonErrors(array $response): array
    {
        $result = [];
        if (($response['code'] ?? null) && ($response['errors'] ?? null)) {
            foreach ((array)$response['errors'] as $errorText) {
                $result[] = ['code' => $response['code'], 'message' => $errorText];
            }
        }
        return $result;
    }


    public function parseInitResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($response['id'] ?? null) && ($response['confirmation']['paymentUrl'] ?? null)) {
            $result['paymentId'] = $response['id'];
            $result['paymentURL'] = $response['confirmation']['paymentUrl'];
        }
        return $result;
    }


    public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $result = $this->exec('payments/' . $order->payment_id, [], (bool)$block->epay_test);
        return $result;
    }


    public function parseOrderStatusResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($response['id'] ?? null) && ($response['status'] ?? null)) {
            $result['status'] = $response['status'];
        }
        return $result;
    }
}
