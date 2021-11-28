<?php
/**
 * Стандартный интерфейс корзины
 * @param Block_Cart $Block Текущий блок <pre><code>Block_Cart(
 *     'additionalParams' => [
 *         'cdek' =>? [
 *             'authLogin' => string Логин
 *             'secure' => string Пароль,
 *             'senderCityId' => int ID# города отправителя в системе СДЭК,
 *             'pickupTariff' => int ID# тарифа самовывоза в системе СДЭК
 *             'deliveryTariff' => int ID# тарифа доставки в системе СДЭК
 *         ] Настройки доставки СДЭК,
 *     ],
 *     'russianPost' =>? [
 *         'login' => string Логин,
 *         'password' => string Пароль,
 *         'token' => string Токен,
 *     ], Настройки доставки Почты России,
 *     'minOrderSum' =>? int Минимальная сумма заказа
 * )</code></pre>
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Application;
use RAAS\Controller_Frontend as RAASControllerFrontend;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\User;

$cartType = new Cart_Type((int)$Block->cart_type);
$user = RAASControllerFrontend::i()->user;
$cart = new Cart($cartType, $user);
$weight = $cart->weight;
$sizes = $cart->sizes;
$_POST['weight'] = $weight;

$interface = new CartInterface(
    $Block,
    $Page,
    $_GET,
    $_POST,
    $_COOKIE,
    $_SESSION,
    $_SERVER,
    $_FILES
);

$cdekText = file_get_contents(Application::i()->baseDir . '/sdek.pvz.json');
$cdekJSON = (array)json_decode($cdekText, true);
$cdekJSON = (array)$cdekJSON['pvz'];

/**
 * Получает тарифы на доставку СДЭК
 * @param string $city Наименование города
 * @param float $weight Вес, кг
 * @param int[] $sizes Размеры (ДxШxВ) в см
 * @return array|null <pre><code>array<
 *     'pickup'|'delivery'[] Доставка или самовывоз => array<
 *         string[] ID# тарифа => [
 *             'id' => string ID# тарифа,
 *             'isDelivery' => bool true - доставка, false - самовывоз,
 *             'price' => float Стоимость доставки,
 *             'dateFrom' ?=> string Минимальная дата доставки (ГГГГ-ММ-ДД),
 *             'dateTo' ?=> string Максимальная дата доставки (ГГГГ-ММ-ДД),
 *         ]
 *     >
 * ></code></pre>, либо null, если не найдено
 */
$getCDEKTariffs = function (
    $city,
    $weight,
    array $sizes
) use (
    $cdekJSON,
    $Block
) {
    $sessionVar = implode('x', array_merge([trim($city)], $sizes, [$weight]));
    $sessionTime = (int)$_SESSION['cdekTariffs'][$sessionVar]['timestamp'];
    if (Application::i()->debug || ($sessionTime < time() - 600)) {
        $result = [];
        $params = $Block->additionalParams['cdek'];
        $matchingPVZ = array_filter($cdekJSON, function ($x) use ($city) {
            return trim(mb_strtolower($x['city'])) == trim(mb_strtolower($city));
        });
        $matchingPVZ = array_values($matchingPVZ);
        if ($matchingPVZ) {
            $cdekCityId = $matchingPVZ[0]['cityCode'];
        }

        $response = null;
        if (!$cdekCityId ||
            !$params['authLogin'] ||
            !$params['secure'] ||
            !$params['senderCityId']
        ) {
            return null;
        }

        $authRequest = [
            'grant_type' => 'client_credentials',
            'client_id' => $params['authLogin'],
            'client_secret' => $params['secure'],
        ];
        $ch = curl_init('https://api.cdek.ru/v2/oauth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $authRequest);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $authResponse = curl_exec($ch);
        $authResponse = (array)json_decode($authResponse, true);
        if ($authResponse['access_token']) {
            $token = $authResponse['access_token'];
        }

        if (!$token) {
            return null;
        }

        $request = [
            'from_location' => ['code' => (int)$params['senderCityId']],
            'to_location' => ['code' => (int)$cdekCityId],
            'packages' => [[
                'weight' => (int)($weight * 1000),
                'length' => (int)$sizes[0],
                'width' => (int)$sizes[1],
                'height' => (int)$sizes[2],
            ]],
        ];
        $ch = curl_init('https://api.cdek.ru/v2/calculator/tarifflist');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        $response = curl_exec($ch);
        $response = (array)json_decode($response, true);

        if (!$response['tariff_codes']) {
            return null;
        }
        $response = $response['tariff_codes'];

        foreach ($response as $tariff) {
            switch ($tariff['tariff_code']) {
                case $params['pickupTariff']:
                    $tariffType = 'pickup';
                    break;
                case $params['deliveryTariff']:
                    $tariffType = 'delivery';
                    break;
                default:
                    continue 2;
                    break;
            }
            $result[$tariffType][trim($tariff['tariff_code'])] = [
                'id' => trim($tariff['tariff_code']),
                'isDelivery' => ($tariffType == 'delivery'),
                'price' => ceil((float)$tariff['delivery_sum']),
                'dateFrom' => date(
                    'Y-m-d',
                    time() + (86400 * (1 + $tariff['period_min']))
                ),
                'dateTo' => date(
                    'Y-m-d',
                    time() + (86400 * (1 + $tariff['period_max']))
                ),
            ];
        }
        $sessionVal = ['data' => $result, 'timestamp' => time()];
        $_SESSION['cdekTariffs'][$sessionVar] = $sessionVal;
    }
    return $_SESSION['cdekTariffs'][$sessionVar]['data'];
};


/**
 * Получает список пунктов выдачи СДЭК
 * @param string $city Наименование города
 * @param float $weight Вес, кг
 * @param int[] $sizes Размеры (ДxШxВ) в см
 * @return array <pre><code>array<[
 *     'id' => string ID# пункта выдачи,
 *     'name' => string Наименование пункта выдачи,
 *     'address' => string Адрес пункта выдачи
 *     'description' => string Подсказка к адресу,
 *     'lat' => float Широта,
 *     'lon' => float Долгота,
 *     'schedule' ?=> string Время работы,
 *     'phones' ?=> string[] Телефоны (Последние 10 цифр),
 *     'images' ?=> string[] URL картинок,
 * ]></code></pre>
 */
$getCDEKPoints = function ($city, $weight, $sizes) use ($cdekJSON) {
    $pvz = array_values(array_filter(
        $cdekJSON,
        function ($x) use ($city, $sizes) {
            if (trim(mb_strtolower($x['city'])) != trim(mb_strtolower($city))) {
                return false;
            }
            $pvzSizes = array_values($x['dimensions']);
            if ($pvzSizes && count($pvzSizes) >= 3) {
                $selfSizes = $sizes;
                sort($selfSizes);
                sort($pvzSizes);
                for ($i = 0; $i < 3; $i++) {
                    if ($selfSizes[$i] >= $pvzSizes[$i]) {
                        return false;
                    }
                }
            }
            return true;
        }
    ));

    $result = [];
    foreach ($pvz as $item) {
        $resultItem = [
            'id' => trim($item['code']),
            'name' => trim($item['name']),
            'address' => trim($item['address']),
            'description' => trim($item['addressComment']),
            'lat' => (float)$item['coordY'],
            'lon' => (float)$item['coordX'],
        ];
        if ($item['workTime']) {
            $resultItem['schedule'] = $item['workTime'];
        }
        if ($item['phoneDetailList']) {
            $resultItem['phones'] = array_map(function ($x) {
                return Text::beautifyPhone($x['number'], 10);
            }, $item['phoneDetailList']);
        }
        if ($item['officeImageList']) {
            $resultItem['images'] = array_map(function ($x) {
                return $x['url'];
            }, $item['officeImageList']);
        }
        $result[] = $resultItem;
    }
    return $result;
};


/**
 * Получает почтовый индекс города
 * @param string $city Наименование города
 * @return string|null null, если не найдено
 */
$getPostalCode = function ($city) use ($cdekJSON) {
    $pvz = array_values(array_filter($cdekJSON, function ($x) use ($city) {
        return trim(mb_strtolower($x['city'])) == trim(mb_strtolower($city));
    }));
    if (!$pvz) {
        return null;
    }
    $postalCodes = array_values(array_unique(array_map(function ($x) {
        return $x['postalCode'];
    }, $pvz)));
    if (count($postalCodes) == 1) {
        return $postalCodes[0];
    }
    sort($postalCodes);
    $firstCode = $postalCodes[0];
    $lastCode = $postalCodes[count($postalCodes) - 1];
    $result = '000000';
    for ($i = 0; ($firstCode[$i] == $lastCode[$i]) && ($i < 6); $i++) {
        $result[$i] = $firstCode[$i];
    }
    return $result;
};


/**
 * Получает тарифы на доставку Почтой России
 * @param string $postalCode Индекс
 * @param float $weight Вес, кг
 * @param int[] $sizes Размеры (ДxШxВ) в см
 * @param float $sum Сумма заказа
 * @return array|null <pre><code>array<
 *     'pickup'|'delivery'[] Доставка или самовывоз => array<
 *         string[] ID# тарифа => [
 *             'id' => string ID# тарифа,
 *             'isDelivery' => bool true - доставка, false - самовывоз,
 *             'price' => float Стоимость доставки,
 *             'dateFrom' ?=> string Минимальная дата доставки (ГГГГ-ММ-ДД),
 *             'dateTo' ?=> string Максимальная дата доставки (ГГГГ-ММ-ДД),
 *         ]
 *     >
 * ></code></pre>, либо null, если не найдено
 */
$getRussianPostTariffs = function (
    $postalCode,
    $weight,
    array $sizes,
    $sum
) use (
    $Block
) {
    $sessionArr = array_merge([trim($postalCode)], $sizes, [$weight, $sum]);
    $sessionVar = implode('x', $sessionArr);
    $sessionTime = (int)$_SESSION['russianPostTariffs'][$sessionVar]['timestamp'];
    if (Application::i()->debug || ($sessionTime < time() - 600)) {
        $result = [];
        $params = $Block->additionalParams['russianpost'];

        if (!$postalCode ||
            !$params['login'] ||
            !$params['password'] ||
            !$params['token']
        ) {
            return null;
        }
        $userKey = base64_encode($params['login'] . ':' . $params['password']);

        $request = [
            'courier' => false,
            'declared-value' => ceil((float)$sum * 100),
            'dimension' => [
                'length' => (int)$sizes[0],
                'width' => (int)$sizes[1],
                'height' => (int)$sizes[2],
            ],
            'index-to' => $postalCode,
            'inventory' => true,
            'mail-category' => 'ORDINARY',
            'mail-type' => 'POSTAL_PARCEL',
            'mass' => (float)($weight * 1000),
            'with-order-of-notice' => false,
        ];


        $ch = curl_init('https://otpravka-api.pochta.ru/1.0/tariff');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json;charset=UTF-8',
            'Authorization: AccessToken ' . $params['token'],
            'X-User-Authorization: Basic ' . $userKey
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $response = (array)json_decode($response, true);

        if (!$response['total-rate']) {
            return null;
        }

        $tariff = [
            'id' => '',
            'isDelivery' => true,
            'price' => ceil((float)$response['total-rate'] / 100),
        ];
        if ($response['delivery-time']['min-days']) {
            $tariff['dateFrom'] = date(
                'Y-m-d',
                time() + (86400 * (1 + $response['delivery-time']['min-days']))
            );
        }
        if ($response['delivery-time']['max-days']) {
            $tariff['dateTo'] = date(
                'Y-m-d',
                time() + (86400 * (1 + $response['delivery-time']['max-days']))
            );
        }
        $result['delivery'][''] = $tariff;
        $sessionVal = ['data' => $result, 'timestamp' => time()];
        $_SESSION['russianPostTariffs'][$sessionVar] = $sessionVal;
    }
    return $_SESSION['russianPostTariffs'][$sessionVar]['data'];
};


/**
 * Получает тарифы на собственную доставку
 * @param string $city Наименование города
 * @param float $weight Вес, кг
 * @param int[] $sizes Размеры (ДxШxВ) в см
 * @param float $sum Сумма заказа
 * @param Material $deliveryMaterial Материал доставки
 * @return array|null <pre><code>array<
 *     'pickup'|'delivery'[] Доставка или самовывоз => array<
 *         string[] ID# тарифа => [
 *             'id' => string ID# тарифа,
 *             'isDelivery' => bool true - доставка, false - самовывоз,
 *             'price' => float Стоимость доставки,
 *             'dateFrom' ?=> string Минимальная дата доставки (ГГГГ-ММ-ДД),
 *             'dateTo' ?=> string Максимальная дата доставки (ГГГГ-ММ-ДД),
 *         ]
 *     >
 * ></code></pre>, либо null, если не найдено
 */
$getSelfTariffs = function (
    $city,
    $weight,
    array $sizes,
    $sum,
    $deliveryMaterial
) use (
    $Block
) {
    $sessionArr = array_merge([trim($city)], $sizes, [$weight, $sum]);
    $sessionVar = implode('x', $sessionArr);
    $sessionTime = (int)$_SESSION['selfTariffs'][$sessionVar]['timestamp'];
    if (Application::i()->debug || ($sessionTime < time() - 600)) {
        $result = [];

        $companyMaterialType = Material_Type::importByURN('company');
        $companies = Material::getSet([
            'where' => "pid = " . (int)$companyMaterialType->id,
            'orderBy' => "NOT priority, priority, name",
        ]);
        $companies = array_filter($companies, function ($x) use ($city) {
            return trim(mb_strtolower($x->city)) == trim(mb_strtolower($city));
        });
        $companies = array_values($companies);

        if (!$companies) {
            return null;
        }

        $result['pickup'][''] = [
            'id' => '',
            'isDelivery' => false,
            'price' => 0,
            'dateFrom' => date('Y-m-d', time() + 86400),
            'dateTo' => date('Y-m-d', time() + 86400),
        ];

        if ($deliveryMaterial->id) {
            $deliverySum = 0;
            if (!(float)$deliveryMaterial->min_sum ||
                ((float)$deliveryMaterial->min_sum > $sum)
            ) {
                $deliverySum = ceil((float)$deliveryMaterial->price);
            }
            $result['delivery'][''] = [
                'id' => '',
                'isDelivery' => true,
                'price' => $deliverySum,
                'dateFrom' => date('Y-m-d', time() + 86400),
                'dateTo' => date('Y-m-d', time() + 86400),
            ];
        }

        $sessionVal = ['data' => $result, 'timestamp' => time()];
        $_SESSION['selfTariffs'][$sessionVar] = $sessionVal;
    }
    return $_SESSION['selfTariffs'][$sessionVar]['data'];
};


/**
 * Получает список собственных пунктов выдачи
 * @param string $city Наименование города
 * @return array <pre><code>array<[
 *     'id' => string ID# пункта выдачи,
 *     'name' => string Наименование пункта выдачи,
 *     'address' => string Адрес пункта выдачи
 *     'description' => string Подсказка к адресу,
 *     'lat' => float Широта,
 *     'lon' => float Долгота,
 *     'schedule' ?=> string Время работы,
 *     'phones' ?=> string[] Телефоны (Последние 10 цифр),
 *     'images' ?=> string[] URL картинок,
 * ]></code></pre>
 */
$getSelfPoints = function ($city) {
    $companyMaterialType = Material_Type::importByURN('company');
    $companies = Material::getSet([
        'where' => "pid = " . (int)$companyMaterialType->id,
        'orderBy' => "NOT priority, priority, name",
    ]);
    $companies = array_filter($companies, function ($x) use ($city) {
        return trim(mb_strtolower($x->city)) == trim(mb_strtolower($city)) &&
            (float)$x->lat &&
            (float)$x->lon;
    });
    $companies = array_values($companies);
    $result = [];
    foreach ($companies as $company) {
        $office = $company->office;
        $result[] = [
            'id' => trim((int)$company->id),
            'name' => 'Офис продаж ' . $company->name,
            'address' => $company->street_address
                . ($office ? ', ' . $office : ''),
            'description' => '',
            'lat' => (float)$company->lat,
            'lon' => (float)$company->lon,
            'schedule' => trim($company->schedule),
            'phones' => array_map(function ($x) {
                return Text::beautifyPhone($x, 10);
            }, (array)$company->fields['phone']->getValues()),
        ];
    }
    return $result;
};


/**
 * Функция расчета дополнительных пунктов для корзины
 * @param Cart $cart Корзина
 * @param array $post POST-данные
 * @param User $user Пользователь
 * @return array <pre>[
 *     'items' => array<CartItem>,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getAdditionals = function (
    Cart $cart,
    array $post = [],
    User $user = null
) use (
    &$getCDEKTariffs,
    &$getCDEKPoints,
    &$getPostalCode,
    &$getRussianPostTariffs,
    &$getSelfTariffs,
    &$getSelfPoints,
    $cdekJSON,
    $weight,
    $sizes
) {
    $result = [];

    $sum = (float)$cart->sum;
    $sumForDiscount = 0;
    foreach ($cart->items as $cartItem) {
        if ($cartItem->amount) {
            $material = $cartItem->material;
            if ((float)$material->price_old <= (float)$material->price) {
                $sumForDiscount += (float)$cartItem->sum;
            }
        }
    }

    $discountSum = 0;
    if ($post['promo']) {
        $promoMaterialType = Material_Type::importByURN('discount');
        $sqlQuery = "SELECT pid FROM cms_data WHERE fid = ? AND value = ?";
        $sqlBind = [
            (int)$promoMaterialType->fields['code']->id,
            $post['promo'],
        ];
        $sqlResult = Material::_SQL()->getvalue([$sqlQuery, $sqlBind]);
        if ((int)$sqlResult) {
            $card = new Material((int)$sqlResult);
            $discountSum = ceil($sumForDiscount * $card->discount / -100);
            $result['discount'] = [
                'id' => (int)$card->id,
                'name' => $card->name,
                'discount' => $card->discount,
                'price' => $discountSum,
            ];
            $result['items'][] = new CartItem([
                'id' => (int)$card->id,
                'name' => $card->name,
                'realprice' => (float)$discountSum,
                'additional' => ['type' => 'discount'],
            ]);
        } else {
            $result['discount']['error'] = true;
        }
    }

    if ($post['city']) {
        // Указан город, найдем варианты доставки
        $sumForDelivery = $cart->sum + $discountSum;
        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $deliveryMaterials = Material::getSet([
            'where' => ["vis", "pid = " . (int)$deliveryMaterialType->id],
            'orderBy' => "NOT priority, priority, name",
        ]);
        $affectedServicesURNs = [];
        $deliveries = $tariffs = $points = $payments = [];
        $selfDelivery = null;
        foreach ($deliveryMaterials as $deliveryMaterial) {
            $delivery = [
                'id' => (int)$deliveryMaterial->id,
                'name' => trim($deliveryMaterial->brief ?: $deliveryMaterial->name),
                'fullName' => trim($deliveryMaterial->name),
                'isDelivery' => (bool)(int)$deliveryMaterial->delivery,
                'serviceURN' => trim($deliveryMaterial->service_urn),
            ];
            $affectedServicesURNs[$delivery['serviceURN']] = $delivery['serviceURN'];
            $deliveries[trim($deliveryMaterial->id)] = $delivery;
            if (!$delivery['serviceURN'] && $delivery['isDelivery']) {
                $selfDelivery = $deliveryMaterial;
            }
        }
        if (isset($affectedServicesURNs[''])) {
            $tariffs[''] = $getSelfTariffs(
                $post['city'],
                $weight,
                $sizes,
                $sumForDelivery,
                $selfDelivery
            );
            $points[''] = $getSelfPoints($post['city']);
        }
        if ($affectedServicesURNs['cdek']) {
            $tariffs['cdek'] = $getCDEKTariffs($post['city'], $weight, $sizes);
            $points['cdek'] = $getCDEKPoints($post['city'], $weight, $sizes);
        }
        if ($affectedServicesURNs['russianpost']) {
            $postalCode = trim($post['post_code']) ?: $getPostalCode($post['city']);
            $tariffs['russianpost'] = $getRussianPostTariffs(
                $postalCode,
                $weight,
                $sizes,
                $sumForDelivery
            );
        }
        $newDeliveries = [];
        foreach ($deliveries as $deliveryId => $delivery) {
            if ($matchingTariffs = $tariffs[$delivery['serviceURN']][$delivery['isDelivery'] ? 'delivery' : 'pickup']) {
                foreach ($matchingTariffs as $tariffId => $tariff) {
                    if (isset($tariff['price'])) {
                        $delivery['tariffId'] = $tariff['id'];
                        $delivery['price'] = (float)$tariff['price'];
                        if ($tariff['dateFrom']) {
                            $delivery['dateFrom'] = $tariff['dateFrom'];
                        }
                        if ($tariff['dateTo']) {
                            $delivery['dateTo'] = $tariff['dateTo'];
                        }
                    } else {
                        $delivery['error'] = true;
                    }
                }
            } else {
                $delivery['error'] = true;
            }
            $newDeliveries[$deliveryId] = $delivery;
            $result['delivery']['methods'][] = $delivery;
        }
        $deliveries = $newDeliveries;
        foreach ($points as $serviceURN => $servicePoints) {
            foreach ($servicePoints as $point) {
                $point['serviceURN'] = $serviceURN;
                $result['delivery']['points'][] = $point;
            }
        }
        if ($post['delivery']) {
            if ($delivery = $deliveries[$post['delivery']]) {
                $result['delivery']['tariffId'] = $delivery['tariffId'];
                if ($delivery['dateFrom']) {
                    $result['delivery']['dateFrom'] = $delivery['dateFrom'];
                }
                if ($delivery['dateTo']) {
                    $result['delivery']['dateTo'] = $delivery['dateTo'];
                }
                if (isset($delivery['price'])) {
                    $result['delivery']['price'] = $delivery['price'];
                    $result['items'][] = new CartItem([
                        'id' => $delivery['id'],
                        'name' => 'Доставка',
                        'realprice' => (float)$delivery['price'],
                        'additional' => ['type' => 'delivery'],
                    ]);
                }
                if ($delivery['error']) {
                    $result['delivery']['error'] = true;
                }
            } else {
                $result['delivery']['error'] = true;
            }
            $paymentMaterialType = Material_Type::importByURN('payment');
            $paymentMaterials = Material::getSet([
                'where' => ["vis", "pid = " . (int)$paymentMaterialType->id],
                'orderBy' => "NOT priority, priority, name",
            ]);
            $paymentMaterials = array_filter(
                $paymentMaterials,
                function ($x) use ($post) {
                    $paymentDeliveries = array_values(array_filter(
                        array_map(function ($x) {
                            return (int)$x->id;
                        }, (array)$x->delivery),
                        'trim'
                    ));
                    if ($paymentDeliveries &&
                        !in_array($post['delivery'], $paymentDeliveries)
                    ) {
                        return false;
                    }
                    return true;
                }
            );
            foreach ($paymentMaterials as $paymentMaterial) {
                $commission = 0;
                if ($paymentCommission = trim($paymentMaterial->commission)) {
                    if (stristr($paymentCommission, '%')) {
                        $commission = ceil($sum * (int)$paymentCommission / 100);
                    } else {
                        $commission = (int)$paymentCommission;
                    }
                }
                $payment = [
                    'id' => (int)$paymentMaterial->id,
                    'name' => trim($paymentMaterial->name),
                    'epay' => (bool)(int)$paymentMaterial->epay,
                    'price' => $commission,
                ];
                if ($commission) {
                    $payment['price'] = $commission;
                }
                $payments[trim($payment['id'])] = $payment;
                $result['payment']['methods'][] = $payment;
            }
            if ($post['payment']) {
                if ($payment = $payments[$post['payment']]) {
                    if ($payment['price']) {
                        $result['payment']['price'] = $payment['price'];
                        $result['items'][] = new CartItem([
                            'id' => $payment['id'],
                            'name' => View_Web::i()->_('COMMISSION'),
                            'realprice' => (float)$payment['price'],
                            'additional' => ['type' => 'payment'],
                        ]);
                    }
                }
            }
        }
    }

    return $result;
};

$interface->additionalsCallback = $getAdditionals;

$delivery = new Material($_POST['delivery']);
$isDelivery = (bool)(int)$delivery->delivery;

$interface->conditionalRequiredFields = [
    'post_code' => function ($field, $post) use ($delivery, $isDelivery) {
        return $delivery->id &&
            $isDelivery &&
            in_array($delivery->service_urn, ['mail']);
    },
    'pickup_point' => function ($field, $post) {
        return $delivery->id && !$isDelivery;
    },
    'street' => function ($field, $post) {
        return $delivery->id && $isDelivery;
    },
    'house' => function ($field, $post) {
        return $delivery->id && $isDelivery;
    },
];

$result = $interface->process();
$result['cdekJSON'] = $cdekJSON;
if ((int)$Block->additionalParams['minOrderSum']) {
    $result['minOrderSum'] = (int)$Block->additionalParams['minOrderSum'];
}
return $result;
