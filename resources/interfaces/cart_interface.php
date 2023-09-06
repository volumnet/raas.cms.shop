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
 *     'minOrderSum' =>? int Минимальная сумма заказа,
 *     'bindUserBy' =>? string[] Привязка заказа к существующему пользователю -
 *         перечисление URN полей
 *     'createUserBlockId' =>? int ID# блока регистрации для создания
 *         нового пользователя, если не удалось найти существующего
 *         (только при указании bindUserBy)
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

$interface = new CartInterface($Block, $Page, $_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
$cities = include Application::i()->baseDir . '/cities.brief.php';

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
$getCDEKTariffs = function ($city, $weight, array $sizes) use ($Block, $cities) {
    $sessionVar = implode('x', array_merge([trim($city)], $sizes, [$weight]));
    $sessionTime = (int)$_SESSION['cdekTariffs'][$sessionVar]['timestamp'];
    if (Application::i()->debug || ($sessionTime < time() - 600)) {
        $cityURN = Text::beautify($city);
        $result = [];
        $params = $Block->additionalParams['cdek'];

        if (!isset($cities[$cityURN]['cdekCityId']) ||
            !$params['authLogin'] ||
            !$params['secure'] ||
            !$params['senderCityId']
        ) {
            return null;
        }

        $response = null;
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
            'to_location' => ['code' => (int)$cities[$cityURN]['cdekCityId']],
            'currency' => 1, // Рубль
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
            $tariffSum = (float)$tariff['delivery_sum'];
            if ($params['priceRatio'] ?? '') {
                if (stristr($params['priceRatio'], '%')) {
                    $tariffSum *= (100 + (float)$params['priceRatio']) / 100;
                } else {
                    $tariffSum += (float)$params['priceRatio'];
                }
            }
            $tariffSum = ceil($tariffSum);
            $result[$tariffType][trim($tariff['tariff_code'])] = [
                'id' => trim($tariff['tariff_code']),
                'isDelivery' => ($tariffType == 'delivery'),
                'price' => $tariffSum,
                'dateFrom' => date('Y-m-d', time() + (86400 * (1 + $tariff['period_min']))),
                'dateTo' => date('Y-m-d', time() + (86400 * (1 + $tariff['period_max']))),
            ];
        }
        $sessionVal = ['data' => $result, 'timestamp' => time()];
        $_SESSION['cdekTariffs'][$sessionVar] = $sessionVal;
    }
    return $_SESSION['cdekTariffs'][$sessionVar]['data'];
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
$getRussianPostTariffs = function ($postalCode, $weight, array $sizes, $sum) use ($Block) {
    $sessionArr = [trim($postalCode), $weight];
    $sessionVar = implode('x', $sessionArr);
    $sessionTime = (int)$_SESSION['russianPostTariffs'][$sessionVar]['timestamp'];
    if (Application::i()->debug || ($sessionTime < time() - 600)) {
        $result = [];
        $params = $Block->additionalParams['russianpost'];

        $baseUrl = 'https://tariff.pochta.ru/v2/calculate/tariff/delivery';
        $baseRequestParams = [
            'json' => 1,
            'from' => $params['senderIndex'],
            'to' => $postalCode,
            'weight' => $weight * 1000,
            'group' => 0, // Единичное отправление (0)
            'service' => implode(',', (array)$params['services']), // Пакет СМС уведомлений отправителю при единичном приеме (41), Пакет СМС уведомлений получателю при единичном приеме (42)
            'closed' => 0, // Расчет при запрете доставки на дату расчета (closed): нет (не используется).
        ];

        foreach (['pickup', 'delivery'] as $tariffURN) {
            // 23030 - Посылка онлайн обыкновенная
            if ($tariffId = $params[$tariffURN . 'Tariff'] ?? null) {
                $request = $baseRequestParams;
                $request['object'] = (int)$tariffId;
                $url = $baseUrl . '?' . http_build_query($request);
                $response = file_get_contents($url);
                $response = (array)json_decode($response, true);
                // var_dump($url, $response); exit;
                if (!$response) {
                    continue;
                }

                $tariffSum = (float)$response['pay'] / 100;
                if ($params['priceRatio'] ?? '') {
                    if (stristr($params['priceRatio'], '%')) {
                        $tariffSum *= (100 + (float)$params['priceRatio']) / 100;
                    } else {
                        $tariffSum += (float)$params['priceRatio'];
                    }
                }
                $tariffSum = ceil($tariffSum);
                $tariff = [
                    'id' => $tariffId,
                    'isDelivery' => ($tariffURN == 'delivery'),
                    'price' => $tariffSum,
                ];
                if ($response['delivery']['min'] ?? 0) {
                    $tariff['dateFrom'] = date('Y-m-d', time() + (86400 * (1 + $response['delivery']['min'])));
                }
                if ($response['delivery']['deadline'] ?? null) {
                    $tariff['dateTo'] = date('Y-m-d', strtotime($response['delivery']['deadline']) + 86400);
                }
                $result[$tariffURN][$tariffId] = $tariff;
            }
        }
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
$getSelfTariffs = function ($city, $weight, array $sizes, $sum, $deliveryMaterial) use ($Block) {
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
        ];

        if ($deliveryMaterial->id) {
            $deliverySum = 0;
            if (!(float)$deliveryMaterial->min_sum || ((float)$deliveryMaterial->min_sum > $sum)) {
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
            'serviceURN' => '',
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
    &$getRussianPostTariffs,
    &$getSelfTariffs,
    &$getSelfPoints
) {
    $result = [];

    $sum = (float)$cart->sum;
    $result['weight'] = $_POST['weight'] = $post['weight'] = $weight = ceil($cart->weight * 1000) / 1000;
    $result['sizes'] = $sizes = $cart->sizes;
    $_POST['sizes'] = $post['sizes'] = implode('x', $sizes);
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
        $cityURN = Text::beautify($_POST['city']);
        if (!Application::i()->debug && isset($_SESSION['citiesData@' . $cityURN])) {
            $cityData = $_SESSION['citiesData@' . $cityURN];
        } else {
            $citiesJSON = include Application::i()->baseDir . '/cities.php';
            $cityData = $citiesJSON[$cityURN];
            $_SESSION['citiesData@' . $cityURN] = $cityData;
        }
        $points = $cityData['points'] ?? [];
        $points = array_values(array_filter(
            $points,
            function ($x) use ($sizes, $weight) {
                if ($x['sizes']) {
                    $selfSizes = $sizes;
                    sort($selfSizes);
                    for ($i = 0; $i < 3; $i++) {
                        if ($selfSizes[$i] >= $x['sizes'][$i]) {
                            return false;
                        }
                    }
                }
                if ($x['weight'] && $weight > $x['weight']) {
                    return false;
                }
                return true;
            }
        ));

        // Указан город, найдем варианты доставки
        $sumForDelivery = $cart->sum + $discountSum;
        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $deliveryMaterials = Material::getSet([
            'where' => ["vis", "pid = " . (int)$deliveryMaterialType->id],
            'orderBy' => "NOT priority, priority, name",
        ]);
        $affectedServicesURNs = [];
        $deliveries = $tariffs = $payments = [];
        $selfDelivery = null;
        foreach ($deliveryMaterials as $deliveryMaterial) {
            $delivery = [
                'id' => (int)$deliveryMaterial->id,
                'name' => trim($deliveryMaterial->brief ?: $deliveryMaterial->name),
                'description' => trim($deliveryMaterial->description),
                'fullName' => trim($deliveryMaterial->name),
                'isDelivery' => (bool)(int)$deliveryMaterial->delivery,
                'serviceURN' => trim($deliveryMaterial->service_urn),
            ];
            $citiesBeautified = array_map(function ($x) {
                return Text::beautify($x);
            }, (array)$deliveryMaterial->cities);
            $delivery['citiesBeautified'] = $citiesBeautified;
            if ($citiesBeautified && !in_array(Text::beautify($post['city']), $citiesBeautified)) {
                continue;
            }
            $affectedServicesURNs[$delivery['serviceURN']] = $delivery['serviceURN'];
            $deliveries[trim($deliveryMaterial->id)] = $delivery;
            if (!$delivery['serviceURN'] && $delivery['isDelivery']) {
                $selfDelivery = $deliveryMaterial;
            }
        }
        if (isset($affectedServicesURNs[''])) {
            $tariffs[''] = $getSelfTariffs($post['city'], $weight, $sizes, $sumForDelivery, $selfDelivery);
            $points = array_merge($points, $getSelfPoints($post['city']));
        }
        if ($affectedServicesURNs['cdek']) {
            $tariffs['cdek'] = $getCDEKTariffs($post['city'], $weight, $sizes);
        }
        if ($affectedServicesURNs['russianpost']) {
            $postalCode = trim($post['post_code']) ?: $cityData['postalCode'];
            $tariffs['russianpost'] = $getRussianPostTariffs($postalCode, $weight, $sizes, $sumForDelivery);
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
        $result['delivery']['points'] = $points;

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
                }
                if (($delivery['price'] || $delivery['isDelivery']) && !($delivery['error'] ?? false)) {
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
                    $paymentDeliveries = array_map(function ($x) {
                        return (int)$x->id;
                    }, (array)$x->delivery);
                    $paymentDeliveries = array_values(array_filter($paymentDeliveries, 'trim'));
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
                    'description' => trim($paymentMaterial->description),
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
    'delivery' => function ($field, $post) {
        return !$post['quickorder'];
    },
    'payment' => function ($field, $post) {
        return !$post['quickorder'];
    },
    'post_code' => function ($field, $post) use ($delivery, $isDelivery) {
        return $delivery->id &&
            $isDelivery &&
            in_array($delivery->service_urn, ['russianpost']);
    },
    'region' => function ($field, $post) {
        return !$post['quickorder'];
    },
    'city' => function ($field, $post) {
        return !$post['quickorder'];
    },
    'pickup_point' => function ($field, $post) {
        return $delivery->id && !$isDelivery && !$post['quickorder'];
    },
    'street' => function ($field, $post) {
        return $delivery->id && $isDelivery && !$post['quickorder'];
    },
    'house' => function ($field, $post) {
        return $delivery->id && $isDelivery && !$post['quickorder'];
    },
    'last_name' => function ($field, $post) {
        return !$post['quickorder'];
    },
    'first_name' => function ($field, $post) {
        return !$post['quickorder'];
    },
];

$result = $interface->process();
if ((int)$Block->additionalParams['minOrderSum']) {
    $result['minOrderSum'] = (int)$Block->additionalParams['minOrderSum'];
}
$result['cities'] = $cities;
return $result;
