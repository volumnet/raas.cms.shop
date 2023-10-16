<?php
/**
 * eCommerce для Яндекса
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\PageRecursiveCache;

/**
 * Класс eCommerce для Яндекса
 */
class ECommerce
{
    /**
     * URN поля "Марка"
     * @var string
     */
    public static $brandField = 'brand';

    /**
     * URN поля "Стоимость"
     * @var string
     */
    public static $priceField = 'price';

    /**
     * Получает данные продукта
     * @param Material|null $item Товар
     * @param int|null $position Позиция
     * @param Page|null $page Категория (страница) для явного указания
     * @param array|null $propsCache Кэш свойств товара
     * @return array
     */
    public static function getProduct(
        Material $item = null,
        $position = null,
        Page $page = null,
        array $propsCache = null
    ) {
        if ($item && $item->id) {
            $brand = '';
            if ($propsCache && $propsCache[static::$brandField]['values'][0]['name']) {
                $brand = $propsCache[static::$brandField]['values'][0]['name'];
            }
            if (!$brand && ($field = $item->fields[static::$brandField])) {
                $brand = $field->doRich();
                if ($brand instanceof Material) {
                    $brand = $brand->name;
                }
            }
            $brand = trim($brand);
            $result = [
                'id' => (int)$item->id,
                'name' => trim($item->name),
                'brand' => $brand,
                'category' => static::getCategory($page ?: $item->cache_url_parent_id),
            ];
            if ($position !== null) {
                $result['position'] = $position;
            }
            if ($propsCache && $propsCache[static::$priceField]['values'][0]) {
                $price = (float)$propsCache[static::$priceField]['values'][0];
            }
            if (!$price) {
                $price = (float)$item->realprice ?: $item->{static::$priceField};
            }
            if ($price) {
                $result['price'] = $price;
            }
            if ((float)$item->price_old && ((float)$item->price_old > $price)) {
                $result['discount'] = (int)(((float)$item->price_old - $price) / (float)$item->price_old);
            }
            if ($amount = $item->amount) {
                $result['quantity'] = $amount;
            }
            return $result;
        }
        return [];
    }


    /**
     * Получает путь категории
     * @param Page|int $page Категория (страница) или ее ID#
     * @return string
     */
    public static function getCategory($page)
    {
        $cache = PageRecursiveCache::i()->getSelfAndParentsCache($page);
        $eCommerceCategory = array_map(function ($x) {
            return $x['name'];
        }, $cache);
        $result = implode(' / ', $eCommerceCategory);
        return $result;
    }
}
