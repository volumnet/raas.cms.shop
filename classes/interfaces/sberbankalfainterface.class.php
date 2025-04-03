<?php
/**
 * Файл класса объединенного интерфейса электронной оплаты через Сбербанк России или Альфа-Банк
 *
 * Механизмы взаимодействия схожи, поэтому объединены в этот класс
 * У блока:
 * epay_login - Логин (с окончанием -api) для взаимодействия с API
 * epay_pass1 - Пароль
 * epay_test - Тестовый режим
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\CMS\Material;
use RAAS\CMS\Page;

/**
 * Класс объединенного интерфейса электронной оплаты через Сбербанк России или Альфа-Банк
 */
abstract class SberbankAlfaInterface extends EPayInterface
{
    const REQUEST_STATUS_HOLD = 1;

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

    public function findOrder()
    {
        $block = $this->block;
        $epayInterface = $this->getPaymentInterface($this->block);
        if ($paymentId = $this->get['orderId'] ?? null) { // Возврат на страницу
            $order = Order::importByPayment($paymentId, $epayInterface);
            if ($order && $order->id) {
                return $order;
            }
        }
        return parent::findOrder();
    }


    public function checkWebhook()
    {
        if (($mdOrder = $this->get['mdOrder'] ?? null) &&
            ($orderNumber = $this->get['orderNumber'] ?? null) &&
            ($operation = $this->get['operation'] ?? null)
        ) {
            return ['paymentId' => $mdOrder, 'orderId' => $orderNumber];
        }
        return null;
    }


    public function exec(string $method, array $requestData = [], bool $isTest = false): array
    {
        $url = $this->getURL($isTest) . $method . '.do?' . http_build_query($requestData);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        if ($isTest) {
            $this->doLogRequest($url, '', $result);
        }
        $json = @json_decode($result, true);
        $json = (array)$json;
        return $json;
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
    ): array {
        $pageUrl = $this->getCurrentHostURL() . $page->url;
        $jsonParams = [];
        foreach ($order->visFields as $field) {
            if (in_array($field->datatype, ['image', 'file', 'checkbox']) || in_array($field->urn, ['epay'])) {
                continue;
            }
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
        $orderBundle = [];
        if (($phone = trim((string)$order->$phoneField)) !== '') {
            $beautifiedPhone = Text::beautifyPhone($phone);
            $beautifiedPhoneLength = mb_strlen($beautifiedPhone);
            $beautifiedPhone = mb_substr('700000', 0, 11 - $beautifiedPhoneLength) . $beautifiedPhone;
            $orderBundle['customerDetails']['phone'] = $beautifiedPhone;
        }
        if (($email = trim((string)$order->$emailField)) !== '') {
            $orderBundle['customerDetails']['email'] = $email;
        }
        if (($address = trim((string)$order->$deliveryAddressField)) !== '') {
            if (($country = trim((string)$order->$deliveryCountryField)) === '') {
                $country = 'RU';
            }
            if (($city = trim((string)$order->$deliveryCityField)) === '') {
                $city = '-';
            }
            $orderBundle['customerDetails']['deliveryInfo']['postAddress'] = $address;
            $orderBundle['customerDetails']['deliveryInfo']['city'] = $city;
            $orderBundle['customerDetails']['deliveryInfo']['country'] = $country;
        }

        $orderBundle['cartItems'] = [];
        $positiveItemsData = $this->getPositiveItems($order);
        $positiveItems = $positiveItemsData['items'];
        $totalSumKop = $positiveItemsData['sum'];
        foreach ($positiveItems as $i => $item) {
            if (($itemTaxType = trim((string)$order->$taxTypeField)) === '') {
                $itemTaxType = static::TAX_TYPE_NO_VAT;
            }
            if (($itemPaymentMethod = trim((string)$order->$paymentMethodField)) === '') {
                $itemPaymentMethod = static::PAYMENT_METHOD_PREPAY;
            }
            if (($itemPaymentObject = trim((string)$order->$paymentObjectField)) === '') {
                $itemPaymentObject = static::PAYMENT_OBJECT_PRODUCT;
            }
            $itemData = [
                'positionId' => ($i + 1),
                'name' => $item->name,
                'quantity' => ['value' => (int)$item->amount, 'measure' => 'шт.'],
                'itemPrice' => $item->epayPriceKop,
                'itemAmount' => round($item->epayPriceKop * $item->amount),
                'itemCode' => (int)$item->id,
                'tax' => ['taxType' => $itemTaxType],
                'itemAttributes' => [
                    'attributes' => [
                        ['name' => 'paymentMethod', 'value' => $itemPaymentMethod],
                        ['name' => 'paymentObject', 'value' => $itemPaymentObject],
                    ],
                ],
            ];
            $orderBundle['cartItems']['items'][] = $itemData;
        }
        if (($taxSystem = trim((string)$order->$taxSystemField)) === '') {
            $taxSystem = static::TAX_SYSTEM_SIMPLE;
        }
        $requestData = [
            'userName' => $block->epay_login,
            'password' => $block->epay_pass1,
            'orderNumber' => (int)$order->id . (string)($order->paymentSuffix ? ('_' . $order->paymentSuffix) : ''), // 2025-04-03, AVS: добавил paymentSuffix для повторной оплаты
            'amount' => $totalSumKop, // Заменили $order->sum * 100 на $totalSumKop, чтобы не было расхождений при погрешности округления
            'returnUrl' => $pageUrl . 'result/', // Поскольку передача параметров GET-ом, свои параметры не передаем
            'failUrl' => $pageUrl . 'result/', // Поскольку передача параметров GET-ом, свои параметры не передаем
            'dynamicCallbackUrl' => $pageUrl . 'result/', // Поскольку передача параметров GET-ом, свои параметры не передаем
            'description' => $this->getOrderDescription($order),
            'jsonParams' => json_encode($jsonParams),
            'taxSystem' => $taxSystem,
            'orderBundle' => json_encode($orderBundle),
        ];

        return $requestData;
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $response = $this->exec('register', $data, (bool)$block->epay_test);
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
        if (($response['orderId'] ?? null) && ($response['formUrl'] ?? null)) {
            $result['paymentId'] = $response['orderId'];
            $result['paymentURL'] = $response['formUrl'];
        }
        return $result;
    }


    public function getOrderStatusData(Order $order, Block_Cart $block, Page $page): array
    {
        return [
            'userName' => $block->epay_login,
            'password' => $block->epay_pass1,
            'orderId' => $order->payment_id,
        ];
    }


    public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        $result = $this->exec('getOrderStatusExtended', $data, (bool)$block->epay_test);
        return $result;
    }


    public function parseOrderStatusResponse(array $response): array
    {
        $result = ['errors' => $this->parseResponseCommonErrors($response)];
        if ($response['orderStatus'] ?? null) {
            $result['status'] = $response['orderStatus'];
        }
        return $result;
    }
}
