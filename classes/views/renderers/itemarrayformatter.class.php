<?php
/**
 * Форматтер массива для товара
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;

/**
 * Класс форматтера массива для товара
 */
class ItemArrayFormatter
{
    /**
     * Материал товара
     * @var Material
     */
    public $item;

    /**
     * Использовать кэш свойств
     * @var bool
     */
    public $usePropsCache = false;

    /**
     * Конструктор класса
     * @param Material $item Материал товара
     */
    public function __construct(Material $item, $usePropsCache = false)
    {
        $this->item = $item;
        $this->usePropsCache = $usePropsCache;
    }


    /**
     * Форматирует данные
     * @param array $with <pre>array<(
     *     string[] URN поля => function (Material $item Материал): mixed Обработчик данных
     * )|(
     *     int[] Индекс поля => string URN поля
     * )> Массив дополнительных полей для отображения
     * @return array <pre>array<string[] Свойство товара => mixed></pre>
     */
    public function format(array $with = [])
    {
        $result = [
            'id' => (int)$this->item->id,
            'name' => trim($this->item->name),
            'url' => trim($this->item->cache_url),
        ];

        if ($this->usePropsCache) {
            $propsCache = (array)json_decode($item->cache_shop_props, true);
        }

        if ($this->usePropsCache && isset($propsCache['price']['values'][0])) {
            $result['price'] = (float)$propsCache['price']['values'][0];
        } else {
            $result['price'] = (float)$this->item->price;
        }

        if ($this->usePropsCache && isset($propsCache['price_old']['values'][0])) {
            $result['price_old'] = $result['priceold'] = (float)$propsCache['price_old']['values'][0];
        } else {
            $result['price_old'] = $result['priceold'] = (float)$this->item->price_old;
        }

        if ($this->usePropsCache && isset($propsCache['min']['values'][0])) {
            $result['min'] = (int)$propsCache['min']['values'][0];
        } elseif ($val = (int)$this->item->min) {
            $result['min'] = $val;
        } else {
            $result['min'] = 1;
        }

        if ($this->usePropsCache && isset($propsCache['max']['values'][0])) {
            $result['max'] = (int)$propsCache['max']['values'][0];
        } elseif ($val = (int)$this->item->max) {
            $result['max'] = $val;
        } else {
            $result['max'] = '';
        }

        if ($this->usePropsCache && isset($propsCache['step']['values'][0])) {
            $result['step'] = (int)$propsCache['step']['values'][0];
        } elseif ($val = (int)$this->item->step) {
            $result['step'] = $val;
        } else {
            $result['step'] = 1;
        }

        if ($this->usePropsCache && isset($propsCache['images']['values'][0])) {
            $result['image'] = $propsCache['images']['values'][0]['smallURL'];
        } elseif ($visImages = $this->item->visImages) {
            $result['image'] = '/' . $this->item->visImages[0]->smallURL;
        } else {
            $result['image'] = '';
        }

        foreach ($with as $key => $val) {
            $value = null;
            if (is_numeric($key) && is_string($val)) {
                $urn = $val;
                if ($this->usePropsCache && $propsCache[$urn]['values']) {
                    $value = $propsCache[$urn]['values'];
                } else {
                    $value = $this->item->$val;
                }
            } elseif (is_string($key)) {
                $urn = $key;
                if (is_callable($val)) {
                    $value = $val(
                        $this->item,
                        $this->usePropsCache ? $propsCache : null
                    );
                } else {
                    $value = $val;
                }
            }
            if ($value !== null) {
                $result[$urn] = $value;
            }
        }
        return $result;
    }
}
