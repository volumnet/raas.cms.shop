<?php
/**
 * Корзина
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\User;

/**
 * Класс корзины
 * @property-read Cart_Type $cartType Тип корзины
 * @property-read array $rawItems <pre>array<string[] ID# товара => array<
 *      string[] Мета-данные товара => int Количество товара
 * >></pre> Данные по товарам корзины
 * @property-read CartItem[] $items Товары корзины
 * @property-read int $count Количество товаров в корзине
 * @property-read float $sum Сумма корзины
 * @property-read float $weight Вес заказа, кг
 * @property-read array $sizes <pre><code>[
 *     int Длина, см,
 *     int Ширина, см,
 *     int Высота, см
 * ]</code></pre> Размеры заказа
 */
class Cart
{
    /**
     * Тип корзины
     * @var Cart_Type
     */
    protected $cartType;

    /**
     * Данные по товарам корзины
     * @var array <pre>array<string[] ID# товара => array<
     *      string[] Мета-данные товара => int Количество товара
     * >></pre>
     */
    protected $items = [];

    /**
     * Данные по товарам корзины в объектном виде
     * @var CartItem[]
     */
    protected $itemsSet = [];

    /**
     * Контрольная сумма сохраненных данных по товарам корзины в объектном виде
     */
    protected $itemsSetCRC = '';

    /**
     * Пользователь сайта
     * @param User
     */
    protected $_user;

    public function __get($var)
    {
        switch ($var) {
            case 'cartType':
                return $this->$var;
                break;
            case 'rawItems':
                return $this->items;
                break;
            case 'currentItemsCRC':
                return crc32(serialize($this->items));
                break;
            case 'items':
            case 'itemsSet':
                if (!$this->itemsSet || !$this->itemsSetCRC || ($this->itemsSetCRC != $this->currentItemsCRC)) {
                    $result = [];
                    foreach ($this->items as $itemId => $metas) {
                        $material = new Material((int)$itemId);
                        foreach ($metas as $meta => $c) {
                            $row = new CartItem([
                                'id' => $material->id,
                                'name' => $material->name,
                                'meta' => $meta,
                                'realprice' => (float)$this->getPrice($material, (float)$c, (array)json_decode($meta, true)),
                                'amount' => (float)$c,
                            ]);
                            $result[] = $row;
                        }
                    }
                    $this->itemsSet = $result;
                    $this->itemsSetCRC = $this->currentItemsCRC;
                }
                return $this->itemsSet;
                break;
            case 'count':
                $result = 0;
                $items = $this->__get('items');
                foreach ($items as $item) {
                    $result += $item->amount;
                }
                return $result;
                break;
            case 'sum':
                $result = 0;
                $items = $this->__get('items');
                foreach ($items as $item) {
                    $result += $item->sum;
                }
                return $result;
                break;
            case 'weight':
            case 'sizes':
                $materials = [];
                $cartItems = $this->__get('items');
                foreach ($cartItems as $cartItem) {
                    $materials[] = $cartItem->material;
                }
                if ($var == 'weight') {
                    $result = $this->cartType->getWeight($materials);
                } elseif ($var == 'sizes') {
                    $result = $this->cartType->getSizes($materials);
                }
                return $result;
                break;
        }
    }


    /**
     * Конструктор класса
     * @param ?Cart_Type $cartType Тип корзины
     * @param ?User $user Пользователь сайта
     */
    public function __construct(?Cart_Type $cartType = null, ?User $user = null)
    {
        $this->_user = $user;
        if ($cartType) {
            $this->cartType = $cartType;
        } else {
            $set = Cart_Type::getSet();
            $this->cartType = $set[0];
        }
        $this->load();
    }


    /**
     * Устанавливает количество товара
     * @param Material $item Товар
     * @param int $amount Количество
     * @param string $meta Мета-данные
     */
    public function set(Material $item, $amount, $meta = '', $save = true)
    {
        $amount = max(0, (int)$amount);
        if ($this->cartType->no_amount) {
            $amount = min(1, $amount);
        }
        if ($amount > 0) {
            $ids = (array)$this->cartType->material_types_ids;
            $ids = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($ids);
            $ids = array_values(array_unique($ids));
            if ($item->id && in_array($item->material_type->id, $ids)) {
                $this->items[(int)$item->id][(string)$meta] = $amount;
            }
        } else {
            unset($this->items[(int)$item->id][(string)$meta]);
        }
        if ($save) {
            $this->save();
        }
    }


    /**
     * Возвращает количество товара
     * @param Material $item Товар
     * @param string $meta Мета-данные
     * @return int
     */
    public function count(Material $item, $meta = '')
    {
        if (isset($this->items[(int)$item->id][(string)$meta])) {
            return (int)$this->items[(int)$item->id][(string)$meta];
        }
        return 0;
    }


    /**
     * Добавляет количество товара
     * @param Material $item Товар
     * @param int $amount Количество для добавления
     * @param string $meta Мета-данные
     */
    public function add(Material $item, $amount = 1, $meta = '')
    {
        $this->set($item, $this->count($item, $meta) + $amount, $meta);
    }


    /**
     * Уменьшает количество товара
     * @param Material $item Товар
     * @param int $amount Количество для уменьшения
     * @param string $meta Мета-данные
     */
    public function reduce(Material $item, $amount = 1, $meta = '')
    {
        $this->set($item, max(0, $this->count($item, $meta) - $amount), $meta);
    }


    /**
     * Очищает корзину
     */
    public function clear()
    {
        $this->items = [];
        $this->save();
    }


    /**
     * Загружает товары
     */
    protected function load()
    {
        $var = 'cart_' . (int)$this->cartType->id;
        $items1 = @(array)json_decode((string)($_COOKIE[$var] ?? '{}'), true);
        if ($this->_user && (int)$this->_user->id) {
            $items2 = [];
            $sqlQuery = "SELECT *
                           FROM cms_shop_carts
                          WHERE cart_type_id = ?
                            AND uid = ?";
            $sqlBind = [(int)$this->cartType->id, (int)$this->_user->id];
            $sqlResult = Cart_Type::_SQL()->get([$sqlQuery, $sqlBind]);
            foreach ($sqlResult as $row) {
                $materialId = (int)$row['material_id'];
                $meta = $row['meta'];
                $items2[trim($materialId)][trim($meta)] = (int)$row['amount'];
            }
            $items = $items2;
            foreach ($items1 as $materialId => $metaItems) {
                foreach ($metaItems as $meta => $amount) {
                    $items[trim($materialId)][trim($meta)] = $amount;
                }
            }
            $this->items = $items;
            if ($items1 != $items2) {
                $this->save();
            }
        } else {
            $this->items = $items1;
        }
        $this->purge();
    }


    /**
     * Чистит корзину от нулевых товаров
     */
    public function purge()
    {
        $cookieItems = (array)$this->items;
        $result = [];
        $checkAmount = (bool)(int)$this->cartType->check_amount;
        $itemsIds = array_keys($cookieItems);
        $st = microtime(true);
        Field::prefetch($itemsIds);
        foreach ((array)$cookieItems as $materialId => $metaItems) {
            $material = new Material((int)$materialId);
            if (!$material->id) {
                continue;
            }
            if ($checkAmount) { // Для ускорения
                if (!$material->vis) {
                    continue;
                }
                if (!$material->fields['available']) {
                    continue;
                }
                if (!$material->fields['price']) {
                    continue;
                }
                $materialPrice = (float)$material->fields['price']->getValue();
                $materialAvailable = (int)$material->fields['available']->getValue();
            }
            foreach ((array)$metaItems as $meta => $amount) {
                if ($amount > 0) {
                    $newAmount = $amount;
                    if ($checkAmount) {
                        $newAmount = min($newAmount, $materialAvailable);
                        $materialAvailable -= $newAmount;
                    }
                    $result[trim($materialId)][trim($meta)] = $newAmount;
                }
            }
        }
        $this->items = (array)$result;
    }


    /**
     * Сохраняет корзину
     */
    public function save()
    {
        $var = 'cart_' . (int)$this->cartType->id;
        $this->purge();
        Application::i()->setcookie($var, (object)$this->items);
        if ($this->_user && (int)$this->_user->id) {
            $sqlArr = [];
            foreach ($this->items as $itemId => $metas) {
                foreach ($metas as $meta => $c) {
                    $sqlRow = [
                        'cart_type_id' => (int)$this->cartType->id,
                        'uid' => (int)$this->_user->id,
                        'material_id' => (int)$itemId,
                        'meta' => $meta,
                        'amount' => (int)$c
                    ];
                    $sqlArr[] = $sqlRow;
                }
            }
            $sqlQuery = "DELETE FROM cms_shop_carts
                          WHERE cart_type_id = ?
                            AND uid = ?";
            $sqlBind = [(int)$this->cartType->id, (int)$this->_user->id];
            $sqlResult = Cart_Type::_SQL()->query([$sqlQuery, $sqlBind]);
            if ($sqlArr) {
                Cart_Type::_SQL()->add('cms_shop_carts', $sqlArr);
            }
        }
    }


    /**
     * Получает тип материалов из настроек корзины
     * @param Material_Type $materialType Исходный тип материалов
     * @return Material_Type <pre>Material_Type([
     *     ...,
     *     'price_id' => int Свойство, отвечающее за стоимость,
     *     'priceField' => Material_Field Свойство, отвечающее за стоимость,
     *     'priceURN' => string URN свойства, отвечающего за стоимость
     *     'price_callback' => string Текст обработчика стоимости из значения,
     *     'priceCallback' => function ($x): float Обработчик стоимости
     * ])</pre>
     */
    public function getCartMaterialType(Material_Type $materialType)
    {
        $mTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndParentsIds($materialType);
        $mTypesIds = array_reverse($mTypesIds);

        $cartMaterialTypes = (array)$this->cartType->material_types;
        foreach ($mTypesIds as $id) {
            foreach ($cartMaterialTypes as $cartMaterialType) {
                if ($cartMaterialType->id == $id) {
                    $field = new Material_Field((int)$cartMaterialType->price_id);
                    $cartMaterialType->priceURN = 'price';
                    if ($field->id) {
                        $cartMaterialType->priceField = $field;
                        $cartMaterialType->priceURN = $field->urn;
                    }
                    if ($cartMaterialType->price_callback) {
                        $cartMaterialType->priceCallback = function ($x) use ($cartMaterialType) {
                            return eval($cartMaterialType->price_callback);
                        };
                    }
                    return $cartMaterialType;
                }
            }
        }
        return new Material_Type();
    }


    /**
     * Получает URN свойства товара по его типу
     * @param Material_Type $materialType Тип материала
     * @return string
     */
    public function getPriceURN(Material_Type $materialType)
    {
        $cartMaterialType = $this->getCartMaterialType($materialType);
        return $cartMaterialType->priceURN ?: 'price';
    }


    /**
     * Получает стоимость товара
     * @param Material $material Материал товара
     * @param float $amount Количество товара
     * @param array $metaJSON Дополнительные данные
     * @return float
     */
    public function getPrice(Material $material, $amount = 1, array $metaJSON = [])
    {
        $materialType = $this->getCartMaterialType($material->material_type);
        $priceURN = $this->getPriceURN($materialType);
        if (!($fieldCallback = $materialType->priceCallback)) {
            $fieldCallback = null;
        }
        if ($fieldCallback) {
            $price = $fieldCallback($material);
        } else {
            $price = number_format($material->{$priceURN}, 2, '.', '');
        }
        return (float)$price;
    }
}
