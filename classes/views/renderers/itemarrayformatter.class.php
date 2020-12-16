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
     * Конструктор класса
     * @param Material $item Материал товара
     */
    public function __construct(Material $item)
    {
        $this->item = $item;
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
            'name' => $this->item->name,
            'price' => (float)$this->item->price,
            'priceold' => (float)($this->item->price_old/* ?: $this->item->price*/),
            'min' => (int)$this->item->min ?: 1,
            'max' => (int)$this->item->max ?: '',
            'step' => (int)$this->item->step ?: 1,
            'image' => $this->item->visImages ? ('/' . $this->item->visImages[0]->smallURL) : '',
        ];
        foreach ($with as $key => $val) {
            $value = null;
            if (is_numeric($key) && is_string($val)) {
                $urn = $val;
                $value = $this->item->$val;
            } elseif (is_string($key)) {
                $urn = $key;
                if (is_callable($val)) {
                    $value = $val($this->item);
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
