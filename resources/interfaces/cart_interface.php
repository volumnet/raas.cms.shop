<?php
/**
 * Стандартный интерфейс корзины
 * @param Block_Cart $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\User;

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


/**
 * Функция расчета скидочных карт
 * @param float $sumForDiscount Сумма для расчета скидки
 * @return array <pre>[
 *     'items' => array<CartItem>,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getDiscountData = function ($sumForDiscount) {
    $orderItems = $addData = [];
    if ($_POST['promo']) {
        $sqlQuery = "SELECT pid FROM cms_data WHERE fid = ? AND value = ?";
        $sqlBind = [
            0,
            $_POST['promo'],
        ];
        $sqlResult = Material::_SQL()->getvalue([$sqlQuery, $sqlBind]);
        if ((int)$sqlResult) {
            $card = new Material((int)$sqlResult);
            $discountSum = $sumForDiscount * $card->discount / -100;
            $addData = [
                'id' => $card->id,
                'name' => $card->name,
                'discount' => $card->discount,
                'price' => $discountSum,
            ];
            $orderItems[] = new CartItem([
                'id' => $card->id,
                'name' => $card->name,
                'realprice' => (float)$discountSum,
                'additional' => ['type' => 'discount'],
            ]);
        } else {
            $addData['error'] = true;
        }
    }
    if ($orderItems || $addData) {
        return array_merge($addData, ['items' => $orderItems]);
    }
};


/**
 * Функция расчета доставки курьером
 * @param float $sumForDelivery Сумма для расчета доставки
 * @return array <pre>[
 *     'delivery' => Material Материал доставки,
 *     'price' => float Стоимость доставки,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getCourierDeliveryData = function ($sumForDelivery) {
    $result = [];
    if ((int)$_POST['district']) {
        $delivery = new Material((int)$_POST['district']);
    } else {
        $delivery = new Material((int)$_POST['delivery']);
    }
    if ($delivery->id && (($sumForDelivery < $delivery->min_sum))) {
        $price = (float)$delivery->price;
    }
    $result['districts'] = Material::getSet([
        'where' => ["vis", "pid = 0"],
        'orderBy' => 'name',
    ]);
    foreach (['delivery' => $delivery, 'price' => $price] as $key => $val) {
        if ($val) {
            $result[$key] = $val;
        }
    }
    return $result;
};


/**
 * Функция расчета доставки Почтой России
 * @param float $sumForDelivery Сумма для расчета доставки
 * @return array <pre>[
 *     'delivery' => Material Материал доставки,
 *     'price' => float Стоимость доставки,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getRussianPostDeliveryPrice = function ($sumForDelivery) {
    $result = [];
    $delivery = new Material((int)$_POST['delivery']);

    $interface = new RussianPostInterface();
    $interface->login = $GLOBALS['pochta']['login'];
    $interface->password = $GLOBALS['pochta']['password'];
    $interface->token = $GLOBALS['pochta']['token'];
    $interface->senderParams = (array)$GLOBALS['pochta']['sender'];
    $interface->priceRatio = (float)$GLOBALS['pochta']['priceRatio'];
    $deliveryData = $interface->calculator(1, $_POST);

    $price = ceil($deliveryData['result']);
    if ($delivery->id && (($sumForDelivery >= $delivery->min_sum))) {
        $price = 0;
    }
    foreach ([
        'delivery' => $delivery,
        'price' => $price,
        'minDays' => $deliveryData['minDays'],
        'maxDays' => $deliveryData['maxDays'],
    ] as $key => $val) {
        if ($val) {
            $result[$key] = $val;
        }
    }
    return $result;
};


/**
 * Функция расчета доставки СДЭК
 * @param float $sumForDelivery Сумма для расчета доставки
 * @return array <pre>[
 *     'delivery' => Material Материал доставки,
 *     'price' => float Стоимость доставки,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getSDEKDeliveryPrice = function ($sumForDelivery) {
    if ($_POST['delivery'] == 'sdek') {
        $tariffId = 0;
    } elseif ($_POST['delivery'] == 'sdekcourier') {
        $tariffId = 0;
    }
    $result = [];
    $delivery = new Material((int)$_POST['delivery']);

    $interface = new SDEKInterface();
    $interface->weightRatio = static::SDEK_WEIGHT_RATIO;
    $interface->priceRatio = static::SDEK_PRICE_RATIO;
    $interface->authLogin = $GLOBALS['sdekConfig']['authLogin'];
    $interface->secure = $GLOBALS['sdekConfig']['secure'];
    $deliveryData = $interface->calculator([
        'cart' => 1,
        'receiverCityId' => (int)$_POST['delivery_city_id'],
        'tariffId' => $tariffId,
    ], 'ru');

    $price = $deliveryData['result']['price'];
    $result['districts'] = Material::getSet([
        'where' => [
            "name = '" . Material::_SQL()->real_escape_string($_POST['delivery_city']) . "'",
            "pid = " . (int)Material_Type::importByURN('districts')->id
        ],
        'orderBy' => 'NOT priority, priority'
    ]);

    if ($possibleDistricts) {
        $delivery = $possibleDistricts[0];
    }
    if ($delivery->id && (($sumForDelivery >= $delivery->min_sum))) {
        $price = 0;
    }
    foreach ([
        'delivery' => $delivery,
        'price' => $price,
    ] as $key => $val) {
        if ($val) {
            $result[$key] = $val;
        }
    }
    return $result;
};


/**
 * Функция расчета доставки Boxberry
 * @param float $sumForDelivery Сумма для расчета доставки
 * @param float $weight Вес в килограммах
 * @return array <pre>[
 *     'delivery' => Material Материал доставки,
 *     'price' => float Стоимость доставки,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getBoxberryDeliveryPrice = function ($sumForDelivery, $weight) {
    $result = [];
    $delivery = new Material((int)$_POST['delivery']);
    $boxberryInterface = new BoxberryAJAXInterface();
    $price = $boxberryInterface->deliveryCosts(
        $weight,
        trim($_POST['delivery_point_id']),
        $sumForDelivery
    );
    if ($delivery->id && (($sumForDelivery >= $delivery->min_sum))) {
        $price = 0;
    }
    foreach ([
        'delivery' => $delivery,
        'price' => $price,
    ] as $key => $val) {
        if ($val) {
            $result[$key] = $val;
        }
    }
    return $result;
};

/**
 * Функция расчета стандартной доставки
 * @param float $sumForDelivery Сумма для расчета доставки
 * @return array <pre>[
 *     'delivery' => Material Материал доставки,
 *     'price' => float Стоимость доставки,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getDefaultDeliveryPrice = function ($sumForDelivery) {
    $delivery = new Material((int)$_POST['delivery']);
    if ($delivery->id && (($sumForDelivery < $delivery->min_sum))) {
        $price = (float)$delivery->price;
    }
    foreach ([
        'delivery' => $delivery,
        'price' => $price,
    ] as $key => $val) {
        if ($val) {
            $result[$key] = $val;
        }
    }
};

/**
 * Функция расчета доставки
 * @param float $sumForDelivery Сумма для расчета доставки
 * @return array <pre>[
 *     'items' => array<CartItem>,
 *     string[] => mixed Дополнительные данные
 * ]</pre>
 */
$getDeliveryData = function (
    $sumForDelivery
) use (
    &$getCourierDeliveryData,
    &$getRussianPostDeliveryPrice,
    &$getSDEKDeliveryPrice,
    &$getBoxberryDeliveryPrice,
    &$getDefaultDeliveryPrice
) {
    $orderItems = $addData = [];
    $deliveryData = null;
    switch ($_POST['delivery']) {
        case 'courier':
            $deliveryData = $getCourierDeliveryData($sumForDelivery);
            break;
        case 'mail':
            $deliveryData = $getRussianPostDeliveryPrice($sumForDelivery);
            break;
        case 'sdek':
        case 'sdekcourier':
            $deliveryData = $getSDEKDeliveryPrice($sumForDelivery);
            break;
        case 'boxberry':
            $weight = 1;
            $deliveryData = $getBoxberryDeliveryPrice($sumForDelivery, $weight);
            break;
        default:
            $deliveryData = $getDefaultDeliveryPrice($sumForDelivery);
            break;
    }
    if ($deliveryData) {
        $addData = $deliveryData;
        unset($addData['delivery']);
        $addData['name'] = 'Доставка';
        if ($deliveryData['delivery'] && $deliveryData['price']) {
            $delivery = $deliveryData['delivery'];
            $orderItems[] = new CartItem([
                'id' => $delivery->id,
                'name' => 'Доставка',
                'realprice' => (float)$deliveryData['price'],
                'additional' => ['type' => 'delivery'],
            ]);
        }
    }
    if ($orderItems || $addData) {
        return array_merge($addData, ['items' => $orderItems]);
    }
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
    &$getDiscountData,
    &$getDeliveryData
) {
    $orderItems = $addData = [];
    $deliveryPrice = 0;

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
    if ($discountData = $getDiscountData($sumForDiscount)) {
        $discountSum = array_sum(array_map(function ($x) {
            return $x->sum;
        }, (array)$discountData['items']));
        $orderItems = array_merge($orderItems, (array)$discountData['items']);
        unset($discountData['items']);
        $addData = array_merge($addData, ['discount' => $discountData]);
    }

    $sumForDelivery = $cart->sum + $discountSum;
    if ($deliveryData = $getDeliveryData($sumForDelivery)) {
        $deliverySum = array_sum(array_map(function ($x) {
            return $x->sum;
        }, (array)$deliveryData['items']));
        $orderItems = array_merge($orderItems, (array)$deliveryData['items']);
        unset($deliveryData['items']);
        $addData = array_merge($addData, ['delivery' => $deliveryData]);
    }

    if ($orderItems || $addData) {
        return array_merge($addData, ['items' => $orderItems]);
    }
    return [];
};

// $interface->additionalsCallback = $getAdditionals;

return $interface->process();
