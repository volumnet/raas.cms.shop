<?php
/**
 * Файл класса интерфейса электронной оплаты через PayKeeper
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
 * Класс интерфейса электронной оплаты через PayKeeper
 */
class PayKeeperInterface extends EPayInterface
{
    const EPAY_URN = 'paykeeper';

    const BANK_NAME = 'PayKeeper';

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
     * Предмет расчета - товар
     */
    const PAYMENT_SUBJECT_COMMODITY = 'goods';

    /**
     * Признак способа расчета - Полная предоплата
     */
    const PAYMENT_METHOD_FULL_PREPAYMENT = 'prepay';

    /**
     * Токен безопасности
     * @var string
     */
    protected $token = '';

    public function findOrder()
    {
        $block = $this->block;
        $epayInterface = $this->getPaymentInterface($this->block);
        if ($paymentId = $this->get['payment_id'] ?? null) {
            $order = Order::importByPayment($paymentId, $epayInterface);
            if ($order && $order->id) {
                return $order;
            }
        }
        return parent::findOrder();
    }


    public function checkWebhook()
    {
        if (($postId = $this->post['id'] ?? '') &&
            ($postOrderId = $this->post['orderid'] ?? '') &&
            ($postKey = $this->post['key'] ?? '')
        ) {
            return ['paymentId' => $postId, 'orderId' => $postOrderId];
        }
        return null;
    }


    public function processWebhookResponse(Order $order, Block_Cart $block, Page $page, array $webhookData)
    {
        if (($postId = $webhookData['paymentId']) && ($postOrderId = $webhookData['orderId'])) {
            $hashPass = (string)$block->epay_pass2;
            $postKey = $this->post['key'] ?? '';
            $postSum = $this->post['sum'] ?? '';
            $postClientId = $this->post['clientid'] ?? '';
            $isTest = (bool)$block->epay_test;
            $checkKey = md5($postId . $postSum . $postClientId . $postOrderId . $hashPass);
            if ($isTest) {
                $this->doLog('checkKey ' . $postKey . ' / ' . $checkKey);
            }
            if ($hashPass && ($postKey == $checkKey)) {
                $returnHash = md5($postId . $hashPass);
                if ($isTest) {
                    $this->doLog('returnHash OK ' . $returnHash);
                }
                echo 'OK ' . $returnHash;
            }
        }
    }


    public function exec(string $method, array $requestData = [], bool $isTest = false): array
    {
        $login = $password = '';
        if ($this->block) {
            $login = (string)$this->block->epay_login;
            $password = (string)$this->block->epay_pass1;
        }
        if (!$this->token && ($method != '/info/settings/token/')) {
            $tokenJSON = $this->exec('/info/settings/token/', [], $isTest);
            // @codeCoverageIgnoreStart
            // В рамках одного метода нет смысла тестировать токен, т.к. он непосредственно уходит в cURL
            if ($tokenJSON['token'] ?? null) {
                $this->token = $tokenJSON['token'];
            }// @codeCoverageIgnoreEnd
        }
        // if ($method != '/info/settings/token/') {
        //     var_dump($requestData); exit;
        // }
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        if ($login && $password) {
            $headers[] = 'Authorization: Basic '
                . base64_encode($login . ':' . $password);
        }
        $url = $this->getURL($isTest) . $method;
        // var_dump($url, $headers); exit;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $request = '';
        if ($requestData) {
            // @codeCoverageIgnoreStart
            // Сложности при тестировании рекурсивного вызова для получения токена
            if ($this->token) {
                $requestData['token'] = $this->token;
            }
            // @codeCoverageIgnoreEnd
            $request = http_build_query($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
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
        if ($isTest) {
            $url = 'https://demo.paykeeper.ru';
        } else {
            $serverHost = '';
            if ($this->block) {
                $additionalParams = $this->block->additionalParams;
                if ($additionalParams['paykeeperHost'] ?? null) {
                    $serverHost = (string)$additionalParams['paykeeperHost'];
                }
            }
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
     * Получение данные для регистрации заказа
     * @param Order $order Заказ для регистрации
     * @param Block_Cart $block Блок настроек
     * @param Page $page Страница, относительно которой совершается проверка
     * @return array
     */
    public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array
    {
        $pageUrl = $this->getCurrentHostURL() . $page->url;
        if ($order->full_name) {
            $clientId = $order->full_name;
        } else {
            $clientIdArr = [
                (string)$order->last_name,
                (string)$order->first_name,
                (string)$order->second_name,
            ];
            $clientIdArr = array_values(array_filter($clientIdArr, 'trim'));
            $clientId = implode(' ', $clientIdArr);
        }

        $cartData = [];
        $positiveItemsData = $this->getPositiveItems($order);
        $positiveItems = $positiveItemsData['items'];
        $totalSumKop = $positiveItemsData['sum'];
        foreach ($positiveItems as $i => $item) {
            $itemData = [
                'name' => $item->name,
                'price' => (float)($item->epayPriceKop / 100),
                'quantity' => (float)$item->amount,
                'sum' => round($item->epayPriceKop * $item->amount / 100),
                'tax' => $order->taxType ?: static::TAX_TYPE_NO_VAT,
                'item_type' => $order->itemType ?: static::PAYMENT_SUBJECT_COMMODITY,
                'payment_mode' => $order->paymentType ?: static::PAYMENT_METHOD_FULL_PREPAYMENT,
            ];
            $cartData[] = $itemData;
        }

        $fz54Data = [
            'service_name' => $this->getOrderDescription($order),
            'receipt_properties' => ['client' => ['identity' => $clientId]],
            'cart' => $cartData,
        ];

        if (trim((string)$order->inn) !== '') {
            $fz54Data['receipt_properties']['client']['inn'] = trim((string)$order->inn);
        }

        $requestData = [
            'pay_amount' => (float)($totalSumKop / 100),
            'clientid' => $clientId,
            'orderid' => (int)$order->id,
            'service_name' => json_encode($fz54Data),
            'user_result_callback' => $pageUrl . 'result/', // Система сама добавляет GET-параметры https://docs.paykeeper.ru/metody-integratsii/html-forma/
        ];
        if (trim((string)$order->email) !== '') {
            $requestData['client_email'] = trim((string)$order->email);
        }
        if (trim((string)$order->phone) !== '') {
            $requestData['client_phone'] = Text::beautifyPhone($order->phone, 11);
        }


        return $requestData;
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        return $this->exec('/change/invoice/preview/', $data, (bool)$block->epay_test);
    }


    public function parseResponseCommonErrors(array $response): array
    {
        $result = [];
        if ((($response['result'] ?? null) == 'fail') && ($response['msg'] ?? null)) {
            $result[] = ['message' => $response['msg']];
        }
        return $result;
    }


    public function parseInitResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($response['invoice_id'] ?? null) && ($response['invoice_url'] ?? null)) {
            $result['paymentId'] = $response['invoice_id'];
            $result['paymentURL'] = $response['invoice_url'];
        }
        return $result;
    }


    public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        return $this->exec('/info/invoice/byid/?id=' . $order->payment_id, [], (bool)$block->epay_test);
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
