<?php
/**
 * Расширенный интерфейс корзины
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Application;
use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\User;

/**
 * Расширенный интерфейс корзины
 */
class CartInterfaceExtended extends CartInterface
{
    /**
     * Список городов (без точек выдачи)
     */
    public $cities = [];

    public function __construct(
        Block_Cart $block = null,
        Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        array $files = []
    ) {
        parent::__construct($block, $page, $get, $post, $cookie, $session, $server, $files);
        $delivery = new Material($post['delivery'] ?? null);
        $quickorder = (bool)($post['quickorder'] ?? false);
        $this->conditionalRequiredFields = [
            'delivery' => function ($field, $post) use ($quickorder) {
                return !$quickorder;
            },
            'payment' => function ($field, $post) use ($quickorder) {
                return !$quickorder;
            },
            'post_code' => function ($field, $post) use ($quickorder, $delivery) {
                return $delivery->id &&
                    (bool)(int)$delivery->delivery &&
                    in_array($delivery->service_urn, ['russianpost']);
            },
            'region' => function ($field, $post) use ($quickorder) {
                return !$quickorder;
            },
            'city' => function ($field, $post) use ($quickorder) {
                return !$quickorder;
            },
            'pickup_point' => function ($field, $post) use ($quickorder, $delivery) {
                return $delivery->id && !(bool)(int)$delivery->delivery && !$quickorder;
            },
            'street' => function ($field, $post) use ($quickorder, $delivery) {
                return $delivery->id && (bool)(int)$delivery->delivery && !$quickorder;
            },
            'house' => function ($field, $post) use ($quickorder, $delivery) {
                return $delivery->id && (bool)(int)$delivery->delivery && !$quickorder;
            },
            'last_name' => function ($field, $post) use ($quickorder) {
                return !$quickorder;
            },
            'first_name' => function ($field, $post) use ($quickorder) {
                return !$quickorder;
            },
        ];
        $this->getCities();
    }


    public function process($debug = false): array
    {
        $result = parent::process();
        $additionalParams = $this->block->additionalParams;
        if ($minOrderSum = (int)($additionalParams['minOrderSum'] ?? 0)) {
            $result['minOrderSum'] = $minOrderSum;
        }
        $result['cities'] = $this->cities;
        return $result;
    }


    /**
     * Получает список городов
     */
    public function getCities()
    {
        $cities = include Application::i()->baseDir . '/cities.brief.php';
        if ($cities) {
            $this->cities = $cities;
        }
    }


    /**
     * Получает тарифы на доставку СДЭК
     * @param string $cityURN URN города
     * @param float $weight Вес, кг
     * @param int[] $sizes Размеры (ДxШxВ) в см
     * @return array <pre><code>array<
     *     'pickup'|'delivery'[] Доставка или самовывоз => array<
     *         string[] ID# тарифа => [
     *             'id' => string ID# тарифа,
     *             'isDelivery' => bool true - доставка, false - самовывоз,
     *             'price' => float Стоимость доставки,
     *             'dateFrom' ?=> string Минимальная дата доставки (ГГГГ-ММ-ДД),
     *             'dateTo' ?=> string Максимальная дата доставки (ГГГГ-ММ-ДД),
     *         ]
     *     >
     * ></code></pre>
     */
    public function getCDEKTariffs(string $cityURN, float $weight, array $sizes): array
    {
        $sessionVar = implode('x', array_merge([trim($cityURN)], $sizes, [$weight]));
        $sessionTime = (int)($_SESSION['cdekTariffs'][$sessionVar]['timestamp'] ?? 0);
        if (Application::i()->debug || ($sessionTime < time() - 600)) {
            $additionalParams = $this->block->additionalParams;
            $result = [];
            $params = ($additionalParams['cdek'] ?? []);

            if (!isset($this->cities[$cityURN]['cdekCityId']) ||
                !($params['authLogin'] ?? null) ||
                !($params['secure'] ?? null) ||
                !($params['senderCityId'] ?? null)
            ) {
                return [];
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
            if ($authResponse['access_token'] ?? null) {
                $token = $authResponse['access_token'];
            }

            if (!$token) {
                return [];
            }

            $request = [
                'from_location' => ['code' => (int)$params['senderCityId']],
                'to_location' => ['code' => (int)($this->cities[$cityURN]['cdekCityId'] ?? null)],
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

            if (!($response['tariff_codes'] ?? null)) {
                return [];
            }
            $response = $response['tariff_codes'];

            foreach ($response as $tariff) {
                switch ($tariff['tariff_code'] ?? null) {
                    case ($params['pickupTariff'] ?? 'pickup'):
                        $tariffType = 'pickup';
                        break;
                    case ($params['deliveryTariff'] ?? 'delivery'):
                        $tariffType = 'delivery';
                        break;
                    default:
                        continue 2;
                        break;
                }
                $tariffSum = (float)($tariff['delivery_sum'] ?? 0);
                if ($params['priceRatio'] ?? '') {
                    if (stristr($params['priceRatio'], '%')) {
                        $tariffSum *= (100 + (float)$params['priceRatio']) / 100;
                    } else {
                        $tariffSum += (float)$params['priceRatio'];
                    }
                }
                $tariffSum = ceil($tariffSum);
                $result[$tariffType][trim((string)$tariff['tariff_code'])] = [
                    'id' => trim((string)$tariff['tariff_code']),
                    'isDelivery' => ($tariffType == 'delivery'),
                    'price' => $tariffSum,
                    'minPeriod' => (1 + $tariff['period_min']),
                    'maxPeriod' => (1 + $tariff['period_max']),
                    'dateFrom' => date('Y-m-d', time() + (86400 * (1 + $tariff['period_min']))),
                    'dateTo' => date('Y-m-d', time() + (86400 * (1 + $tariff['period_max']))),
                ];
            }
            $sessionVal = ['data' => $result, 'timestamp' => time()];
            $_SESSION['cdekTariffs'][$sessionVar] = $sessionVal;
        }
        return $_SESSION['cdekTariffs'][$sessionVar]['data'];
    }


    /**
     * Получает тарифы на доставку Почтой России
     * @param string $postalCode Индекс
     * @param float $weight Вес, кг
     * @param int[] $sizes Размеры (ДxШxВ) в см
     * @param float $sum Сумма заказа
     * @return array <pre><code>array<
     *     'pickup'|'delivery'[] Доставка или самовывоз => array<
     *         string[] ID# тарифа => [
     *             'id' => string ID# тарифа,
     *             'isDelivery' => bool true - доставка, false - самовывоз,
     *             'price' => float Стоимость доставки,
     *             'dateFrom' ?=> string Минимальная дата доставки (ГГГГ-ММ-ДД),
     *             'dateTo' ?=> string Максимальная дата доставки (ГГГГ-ММ-ДД),
     *         ]
     *     >
     * ></code></pre>
     */
    public function getRussianPostTariffs(string $postalCode, float $weight, array $sizes, float $sum): array
    {
        $sessionArr = [trim($postalCode), $weight];
        $sessionVar = implode('x', $sessionArr);
        $sessionTime = (int)($_SESSION['russianPostTariffs'][$sessionVar]['timestamp'] ?? 0);
        if (Application::i()->debug || ($sessionTime < time() - 600)) {
            $additionalParams = $this->block->additionalParams;
            $result = [];
            $params = ($additionalParams['russianpost'] ?? []);

            $baseUrl = 'https://tariff.pochta.ru/v2/calculate/tariff/delivery';
            $baseRequestParams = [
                'json' => 1,
                'from' => $params['senderIndex'] ?? '000000',
                'to' => $postalCode,
                'weight' => $weight * 1000,
                'group' => 0, // Единичное отправление (0)
                'service' => implode(',', (array)($params['services'] ?? [])), // Пакет СМС уведомлений отправителю при единичном приеме (41), Пакет СМС уведомлений получателю при единичном приеме (42)
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
                        $tariff['minPeriod'] = (1 + $response['delivery']['min']);
                        $tariff['dateFrom'] = date('Y-m-d', time() + (86400 * (1 + $response['delivery']['min'])));
                    }
                    if ($response['delivery']['deadline'] ?? null) {
                        $maxTime = strtotime($response['delivery']['deadline']) + 86400;
                        $tariff['dateTo'] = date('Y-m-d', $maxTime);
                        $tariff['maxPeriod'] = (int)((strtotime($tariff['dateTo']) - strtotime(date('Y-m-d'))) / 86400);
                    }
                    $result[$tariffURN][$tariffId] = $tariff;
                }
            }
            $sessionVal = ['data' => $result, 'timestamp' => time()];
            $_SESSION['russianPostTariffs'][$sessionVar] = $sessionVal;
        }
        return $_SESSION['russianPostTariffs'][$sessionVar]['data'];
    }


    /**
     * Получает доступный период доставки в днях
     * @param string $currentTime Текущее дата-время в формате ГГГГ-ММ-ДД ЧЧ:ММ:СС
     * @param string $timeTo Время закрытия в формате ЧЧ:ММ
     * @param int[] $weekendDays Выходные дни (согласно date('w'))
     * @param string $holidaysRX Выходные дни (regexp согласно ММ-ДД)
     * @param int $timeToPeriod Запас времени перед закрытием, минут
     * @return int
     */
    public function getAvailablePickupDate(
        string $currentTime,
        string $timeTo = '18:00',
        array $weekendDays = [0, 6],
        string $holidaysRX = '/(01-0\\d)|(02-23)|(03-08)|(05-01)|(05-09)|(06-12)|(11-04)|(12-3\\d)/umis',
        int $timeToPeriod = 30
    ): int {
        $result = 0;
        $currentTimestamp = strtotime($currentTime);
        $currentTimestampPlusPeriod = $currentTimestamp + $timeToPeriod * 60;
        $currentTimePlusPeriod = date('H:i', $currentTimestampPlusPeriod);

        for ($result = 0; true; $result++) {
            $iteration = $currentTimestamp + $result * 86400;
            if (preg_match($holidaysRX, date('m-d', $iteration))) {
                continue; // Праздник
            }
            if (in_array(date('w', $iteration), $weekendDays)) {
                continue; // Выходной
            }
            if (!$result) { // Сегодня
                if ($currentTimePlusPeriod > $timeTo) { // Не успеем
                    continue;
                }
            }
            return $result;
        }
    }


    /**
     * Получает тарифы на собственную доставку
     * @param string $city Наименование города
     * @param float $weight Вес, кг
     * @param int[] $sizes Размеры (ДxШxВ) в см
     * @param float $sum Сумма заказа
     * @param Material $deliveryMaterial Материал доставки
     * @return array <pre><code>array<
     *     'pickup'|'delivery'[] Доставка или самовывоз => array<
     *         string[] ID# тарифа => [
     *             'id' => string ID# тарифа,
     *             'isDelivery' => bool true - доставка, false - самовывоз,
     *             'price' => float Стоимость доставки,
     *             'dateFrom' ?=> string Минимальная дата доставки (ГГГГ-ММ-ДД),
     *             'dateTo' ?=> string Максимальная дата доставки (ГГГГ-ММ-ДД),
     *         ]
     *     >
     * ></code></pre>
     */
    public function getSelfTariffs(
        string $city,
        float $weight,
        array $sizes,
        float $sum,
        Material $deliveryMaterial
    ): array {
        $sessionArr = array_merge([trim($city)], $sizes, [$weight, $sum]);
        $sessionVar = implode('x', $sessionArr);
        $sessionTime = (int)($_SESSION['selfTariffs'][$sessionVar]['timestamp'] ?? 0);
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
                return [];
            }
            $company = $companies[0];
            $schedule = $company->schedule;
            $timeTo = null;
            preg_match_all('/(^| )(\\d{2}(:\\d{2})?)( |$)/umis', $schedule, $regs);
            if ($regs[0]) {
                $timeTo = $regs[2][count($regs[2]) - 1];
                if (!stristr($timeTo, ':')) {
                    $timeTo .= ':';
                }
            }

            $period = $this->getAvailablePickupDate(date('Y-m-d H:i:s'), $timeTo);
            $result['pickup'][''] = [
                'id' => '',
                'isDelivery' => false,
                'price' => 0,
                'minPeriod' => $period,
                'maxPeriod' => $period,
                'dateFrom' => date('Y-m-d', time() + $period * 86400),
                'dateTo' => date('Y-m-d', time() + $period * 86400),
            ];

            if ($deliveryMaterial->id) {
                $result['delivery'][''] = [
                    'id' => '',
                    'isDelivery' => true,
                    'price' => (float)$deliveryMaterial->price,
                    'minPeriod' => $period + 1,
                    'maxPeriod' => $period + 2,
                    'dateFrom' => date('Y-m-d', time() + ($period + 1) * 86400),
                    'dateTo' => date('Y-m-d', time() + ($period + 2) * 86400),
                ];
            }

            $sessionVal = ['data' => $result, 'timestamp' => time()];
            $_SESSION['selfTariffs'][$sessionVar] = $sessionVal;
        }
        return $_SESSION['selfTariffs'][$sessionVar]['data'];
    }


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
    public function getSelfPoints(string $city): array
    {
        $company = $this->page->company;
        $companyMaterialType = Material_Type::importByURN('company');
        if (!$companyMaterialType || !$companyMaterialType->id) {
            return [];
        }
        $companyMaterialTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($companyMaterialType->id);
        $companies = Material::getSet([
            'where' => ["vis", "pid IN (" . implode(", ", $companyMaterialTypesIds) . ")"],
            'orderBy' => "NOT priority, priority",
        ]);
        $result = [];
        foreach ($companies as $company) {
            $office = $company->office;
            $result[] = [
                'id' => (int)$company->id,
                'name' => 'Офис продаж ' . $company->name,
                'address' => $company->street_address
                    . ($office ? ', ' . $office : ''),
                'description' => '',
                'lat' => (float)$company->lat,
                'lon' => (float)$company->lon,
                'canPay' => true,
                'schedule' => trim((string)$company->schedule),
                'phones' => array_map(function ($x) {
                    return Text::beautifyPhone($x, 10);
                }, (array)$company->fields['phone']->getValues()),
                'serviceURN' => '',
            ];
        }
        return $result;
    }


    /**
     * Получает способы доставки с тарифами, точками выдачи и доступными способами оплаты
     * @param string $cityURN URN города
     * @param float $sumForDelivery Сумма заказа
     * @param int[] $sizes Размеры в см
     * @param float $weight Вес в кг
     * @param string|null $postalCode Индекс места назначения
     * @param bool $withPoints Получить точки выдачи
     */
    public function getDeliveries(
        string $cityURN,
        float $sumForDelivery,
        array $sizes,
        float $weight,
        string $postalCode = null,
        bool $withPoints = false
    ): array {
        $result = [];
        if ($withPoints) {
            if (!Application::i()->debug && isset($_SESSION['citiesData@' . $cityURN])) {
                $cityData = $_SESSION['citiesData@' . $cityURN];
            } else {
                $citiesJSON = include Application::i()->baseDir . '/cities.php';
                $cityData = $citiesJSON[$cityURN];
                $_SESSION['citiesData@' . $cityURN] = $cityData;
            }
            $points = $cityData['points'] ?? [];
            $points = array_values(array_filter($points, function ($x) use ($sizes, $weight) {
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
            }));
        } else {
            $cityData = $this->cities[$cityURN];
        }

        // Найдем доступные способы оплаты для доставок
        $deliveriesPaymentsAssoc = [];
        $paymentMaterialType = Material_Type::importByURN('payment');
        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $paymentDeliveriesField = $paymentMaterialType->fields['delivery'];
        $deliveryMaterials = Material::getSet([
            'where' => ["vis", "pid = " . (int)$deliveryMaterialType->id],
            'orderBy' => "NOT priority, priority, name",
        ]);
        $sqlQuery = "SELECT tDelivery.id AS delivery_id, tPayment.id AS payment_id
                       FROM " . Material::_tablename() . " AS tPayment
                  LEFT JOIN cms_data AS tD ON tD.pid = tPayment.id AND tD.fid = :paymentDeliveryFieldId
                  LEFT JOIN " . Material::_tablename() . " AS tDelivery ON ((tDelivery.id = tD.value) OR (tD.value IS NULL))
                      WHERE tPayment.pid = :paymentMaterialTypeId
                        AND tPayment.vis
                        AND tDelivery.pid = :deliveryMaterialTypeId
                        AND tDelivery.vis
                   ORDER BY NOT tDelivery.priority, tDelivery.priority, NOT tPayment.priority, tPayment.priority";
        $sqlBind = [
            'paymentMaterialTypeId' => (int)$paymentMaterialType->id,
            'paymentDeliveryFieldId' => (int)$paymentDeliveriesField->id,
            'deliveryMaterialTypeId' => (int)$deliveryMaterialType->id,
        ];
        $sqlResult = Material::_SQL()->get([$sqlQuery, $sqlBind]);
        foreach ($sqlResult as $sqlRow) {
            $deliveriesPaymentsAssoc[trim((string)$sqlRow['delivery_id'])][trim((string)$sqlRow['payment_id'])] = (int)$sqlRow['payment_id'];
        }

        // Указан город, найдем варианты доставки

        $affectedServicesURNs = [];
        $deliveries = $tariffs = [];
        $selfDelivery = null;
        foreach ($deliveryMaterials as $deliveryMaterial) {
            $citiesBeautified = array_map(function ($x) {
                return Text::beautify($x);
            }, (array)$deliveryMaterial->cities);
            $delivery = [
                'id' => (int)$deliveryMaterial->id,
                'name' => trim((string)($deliveryMaterial->brief ?: $deliveryMaterial->name)),
                'description' => trim((string)$deliveryMaterial->description),
                'fullName' => trim((string)$deliveryMaterial->name),
                'isDelivery' => (bool)(int)$deliveryMaterial->delivery,
                'serviceURN' => trim((string)$deliveryMaterial->service_urn),
                'paymentIds' => array_values((array)($deliveriesPaymentsAssoc[$deliveryMaterial->id] ?? [])),
                'citiesBeautified' => $citiesBeautified,
                'expiration' => (int)$deliveryMaterial->expiration,
                'minSum' => (float)$deliveryMaterial->min_sum,
            ];
            if ($citiesBeautified && !in_array(Text::beautify($this->post['city'] ?? ''), $citiesBeautified)) {
                continue;
            }

            if ($delivery['minSum'] && ($sumForDelivery > $delivery['minSum'])) {
                $delivery['price'] = 0;
                $delivery['freeDeliveryApplied'] = true;
            }
            $affectedServicesURNs[$delivery['serviceURN']] = $delivery['serviceURN'];
            $deliveries[(string)$deliveryMaterial->id] = $delivery;
            if (!$delivery['serviceURN'] && $delivery['isDelivery']) {
                $selfDelivery = $deliveryMaterial;
            }
        }
        if (isset($affectedServicesURNs[''])) {
            $tariffs[''] = $this->getSelfTariffs($cityData['name'], $weight, $sizes, $sumForDelivery, $selfDelivery);
            if ($withPoints) {
                $points = array_merge($points, $this->getSelfPoints($cityData['name']));
            }
        }
        if (isset($affectedServicesURNs['cdek'])) {
            $tariffs['cdek'] = $this->getCDEKTariffs($cityURN, $weight, $sizes);
        }
        if (isset($affectedServicesURNs['russianpost'])) {
            $postalCode = trim((string)$postalCode) ?: $cityData['postalCode'];
            $tariffs['russianpost'] = $this->getRussianPostTariffs($postalCode, $weight, $sizes, $sumForDelivery);
        }
        $newDeliveries = [];
        foreach ($deliveries as $deliveryId => $delivery) {
            if ($matchingTariffs = $tariffs[$delivery['serviceURN']][$delivery['isDelivery'] ? 'delivery' : 'pickup'] ?? []) {
                foreach ($matchingTariffs as $tariffId => $tariff) {
                    if (!($delivery['freeDeliveryApplied'] ?? false)) {
                        $delivery['price'] = (float)$tariff['price'];
                    }
                    foreach (['minPeriod', 'maxPeriod', 'dateFrom', 'dateTo'] as $key) {
                        $delivery[$key] = $tariff[$key];
                    }
                    if (isset($tariff['price'])) {
                        $delivery['tariffId'] = $tariff['id'];
                    } else {
                        $delivery['error'] = true;
                    }
                }
            } else {
                $delivery['error'] = true;
            }
            if (!($delivery['error'] ?? null)) {
                $newDeliveries[$deliveryId] = $delivery;
                $result['methods'][] = $delivery;
            }
        }
        $deliveries = $newDeliveries;
        if ($withPoints) {
            $result['points'] = $points;
        }
        return $result;
    }


    /**
     * Получает доступные способы оплаты
     * @param array $deliveryPaymentIds Ограничить списком ID# способов оплаты
     * @param float $sumForDiscount Сумма для расчета скидки
     * @param bool $postamateSelected Выбран постамат
     * @return array
     */
    public function getPayments(
        array $deliveryPaymentIds = [],
        float $sumForDiscount = 0,
        bool $postamateSelected = false
    ): array {
        $result = [];
        $paymentMaterialType = Material_Type::importByURN('payment');
        $paymentMaterialsWhere = [
            "vis",
            "pid = " . (int)$paymentMaterialType->id,
        ];
        if ($deliveryPaymentIds) {
            $paymentMaterialsWhere[] = "id IN (" . implode(", ", $deliveryPaymentIds) . ")";
        }
        $paymentMaterials = Material::getSet([
            'where' => $paymentMaterialsWhere,
            'orderBy' => "NOT priority, priority, name",
        ]);
        if ($postamateSelected) {
            $paymentMaterials = array_filter($paymentMaterials, function ($x) {
                return (bool)(int)$x->can_use_with_postamates;
            });
        }
        foreach ($paymentMaterials as $paymentMaterial) {
            $commission = 0;
            if ($paymentCommission = trim((string)$paymentMaterial->commission)) {
                if (stristr($paymentCommission, '%')) {
                    $commission = ceil($sumForDiscount * (int)$paymentCommission / 100);
                } else {
                    $commission = (int)$paymentCommission;
                }
            }
            $payment = [
                'id' => (int)$paymentMaterial->id,
                'name' => trim((string)$paymentMaterial->name),
                'description' => trim((string)$paymentMaterial->description),
                'epay' => (bool)(int)$paymentMaterial->epay,
                'price' => (float)$commission,
            ];
            $result['methods'][] = $payment;
        }
        return $result;
    }


    public function getAdditionals(Cart $cart, array $post = [], User $user = null): array
    {
        $result = [];
        $sum = (float)$cart->sum;
        $result['weight'] = $_POST['weight'] = $this->post['weight'] = $post['weight'] = $weight = ceil($cart->weight * 1000) / 1000;
        $sizes = $cart->sizes;
        $result['sizes'] = $sizes;
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
        if ($post['promo'] ?? null) {
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
                $sumForDiscount += $discountSum;
            } else {
                $result['discount']['error'] = true;
            }
        }

        if (($post['cityURN'] ?? null) && ($post['city'] ?? null)) {
            $cityURN = $post['cityURN'];
            $sumForDelivery = $cart->sum + $discountSum;

            $deliveryData = $this->getDeliveries(
                $cityURN,
                $sumForDelivery,
                $sizes,
                $weight,
                $post['post_code'] ?? '',
                true,
            );
            $result['delivery'] = $deliveryData;

            if ($post['delivery']) {
                $payments = [];
                $matchingDeliveries = array_values(array_filter($deliveryData['methods'] ?? [], function ($x) use ($post) {
                    return $x['id'] == $post['delivery'];
                }));
                if ($matchingDeliveries) {
                    $delivery = $matchingDeliveries[0];
                    $result['delivery']['tariffId'] = $delivery['tariffId'];
                    if ($delivery['dateFrom']) {
                        $result['delivery']['dateFrom'] = $delivery['dateFrom'];
                    }
                    if ($delivery['dateTo']) {
                        $result['delivery']['dateTo'] = $delivery['dateTo'];
                    }
                    $minPriceApplied = false; // Применена бесплатная доставка
                    $realDeliveryPrice = null;
                    if (!($delivery['error'] ?? false)) {
                        if ($delivery['price'] !== null) { // Может быть 0 в случае бесплатной доставки
                            $result['delivery']['price'] = $delivery['price'];
                            if ((
                                $delivery['price'] ||
                                $delivery['isDelivery'] ||
                                ($delivery['freeDeliveryApplied'] ?? false)
                            ) && !($delivery['error'] ?? false)) {
                                $result['items'][] = new CartItem([
                                    'id' => $delivery['id'],
                                    'name' => 'Доставка',
                                    'realprice' => (float)$delivery['price'],
                                    'additional' => ['type' => 'delivery'],
                                ]);
                                $sumForDiscount += (float)$delivery['price'];
                            }
                        } else {
                            $result['delivery']['error'] = true;
                        }
                    } elseif ($delivery['error']) {
                        $result['delivery']['error'] = true;
                    }
                } else {
                    $result['delivery']['error'] = true;
                }

                $postamateSelected = false;
                if ($post['pickup_point_id']) {
                    $matchingPoints = array_values(array_filter($deliveryData['points'], function ($x) use ($post) {
                        return $x['id'] == $post['pickup_point_id'];
                    }));
                    if ($matchingPoints) {
                        $selectedPoint = $matchingPoints[0];
                        if (!$selectedPoint['canPay']) {
                            $postamateSelected = true;
                        }
                    }
                }
                $paymentsData = $this->getPayments($delivery['paymentIds'] ?? [], $sumForDiscount, $postamateSelected);
                $result['payment'] = $paymentsData;
                if ($post['payment']) {
                    $matchingPayments = array_values(array_filter($paymentsData['methods'], function ($x) use ($post) {
                        return $x['id'] == $post['payment'];
                    }));
                    if ($matchingPayments) {
                        $payment = $matchingPayments[0];
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
    }
}
