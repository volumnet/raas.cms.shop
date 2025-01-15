<?php
/**
 * Файл класса интерфейса электронной оплаты через банк Точка
 * https://enter.tochka.com/doc/v2/redoc
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
 * Класс интерфейса электронной оплаты через банк Точка
 */
class TochkaInterface extends EPayInterface
{
    const EPAY_URN = 'tochka';

    const BANK_NAME = 'Точка';

    const REQUEST_STATUS_PAID = 'APPROVED';

    /**
     * Ставка НДС - без НДС
     */
    const TAX_TYPE_NO_VAT = 'none';

    /**
     * Предмет расчета - товар
     */
    const PAYMENT_SUBJECT_COMMODITY = 'goods';

    /**
     * Признак способа расчета - Полная предоплата
     */
    const PAYMENT_METHOD_FULL_PREPAYMENT = 'full_prepayment';

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
     * @param Order $order Обрабатываемый заказ
     * @param Block_Cart $block Блок настроек
     * @param Page $page Текущая страница
     * @param ?string $debugInput Проверочный входной запрос для отладки
     * @return array|null <pre><code>[
     *     'orderId' =>? string ID# заказа,
     *     'paymentId' =>? string ID# платежа
     * ]</code></pre> Данные вебхука или null, если вебхук не обнаружен
     */
    public function checkWebhook(?string $debugInput = null)
    {
        if (($this->server['REQUEST_METHOD'] ?? '') == 'POST') {
            $jwt = $this->post['@raw'] ?: file_get_contents('php://input'); // $this->post['@raw'] для проверки
            $jwtArr = explode('.', $jwt);
            $request = base64_decode($jwtArr[1]);
            // @codeCoverageIgnoreStart
            // Это не важно
            if (substr($request, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $request = substr($request, 3);
            }
            // @codeCoverageIgnoreEnd
            $json = @json_decode($request, true);
            if ($json) {
                if ($paymentId = $json['operationId'] ?? null) {
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
        $headers = [
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
        if ($isTest) {
            $url = 'https://enter.tochka.com/sandbox/v2/acquiring/v1.0/';
        } else {
            $url = 'https://enter.tochka.com/uapi/acquiring/v1.0/';
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
        $cartData = [];
        $positiveItemsData = $this->getPositiveItems($order);
        $positiveItems = $positiveItemsData['items'];
        $totalSumKop = $positiveItemsData['sum'];
        foreach ($positiveItems as $i => $item) {
            $itemData = [
                'name' => $item->name,
                'amount' => (float)($item->epayPriceKop / 100),
                'quantity' => (int)$item->amount,
            ];
            $cartData[] = $itemData;
        }

        $requestData = [
            'customerCode' => (string)$block->epay_login,
            'amount' => (float)($totalSumKop / 100),
            'purpose' => $this->getOrderDescription($order),
            'redirectUrl' => $pageUrl . 'result/?orderId=' . (int)$order->id,
            'failRedirectUrl' => $pageUrl . 'result/?orderId=' . (int)$order->id,
            'paymentMode' => ['sbp', 'card'],
            'Items' => $cartData,
        ];

        $fullNameArr = [];
        if (trim((string)$order->full_name)) {
            $fullNameArr[] = trim((string)$order->full_name);
        } else {
            foreach (['full_name', 'last_name', 'first_name', 'second_name'] as $fieldURN) {
                if (trim((string)$order->$fieldURN)) {
                    $fullNameArr[] = trim((string)$order->$fieldURN);
                }
            }
        }
        $fullNameArr = array_values(array_filter($fullNameArr));
        if ($fullNameArr) {
            $requestData['Client']['name'] = implode(' ', $fullNameArr);
        }
        if (trim((string)$order->email) !== '') {
            $requestData['Client']['email'] = trim((string)$order->email);
        }
        if (trim((string)$order->phone) !== '') {
            $requestData['Client']['phone'] = Text::beautifyPhone($order->phone, 11);
        }

        return ['Data' => $requestData];
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $response = $this->exec('payments_with_receipt', $data, (bool)$block->epay_test);
        return $response;
    }


    public function parseResponseCommonErrors(array $response): array
    {
        $result = [];
        if (($response['code'] ?? null) && ($response['Errors'] ?? null)) {
            foreach ((array)$response['Errors'] as $errorData) {
                $result[] = ['code' => $errorData['errorCode'], 'message' => $errorData['message']];
            }
        }
        return $result;
    }


    public function parseInitResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if (($response['Data']['operationId'] ?? null) && ($response['Data']['paymentLink'] ?? null)) {
            $result['paymentId'] = $response['Data']['operationId'];
            $result['paymentURL'] = $response['Data']['paymentLink'];
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
        if (($response['Data']['Operation'][0]['operationId'] ?? null) &&
            ($response['Data']['Operation'][0]['status'] ?? null)) {
            $result['status'] = $response['Data']['Operation'][0]['status'];
        }
        return $result;
    }
}
