<?php
/**
 * Форматтер массива для статуса заказа
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;

/**
 * Класс форматтера массива для статуса заказа
 */
class OrderStatusArrayFormatter
{
    /**
     * Материал товара
     * @var Order_Status
     */
    public $item;

    /**
     * Конструктор класса
     * @param Order_Status $item Статус заказа
     */
    public function __construct(Order_Status $item)
    {
        $this->item = $item;
    }


    /**
     * Форматирует данные
     * @param array $with <pre>array<(
     *     string[] URN поля => function (Order_Status $item Статус заказа): mixed Обработчик данных
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
        ];

        foreach ($with as $key => $val) {
            $value = null;
            if (is_numeric($key) && is_string($val)) {
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
