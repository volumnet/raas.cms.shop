<?php
/**
 * Форматтер массива для товара заказа
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;

/**
 * Класс форматтера массива для товара заказа
 */
class OrderItemArrayFormatter
{
    /**
     * Материал товара заказа
     * @var Material
     */
    public $item;

    /**
     * Конструктор класса
     * @param Material $item Материал товара заказа
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
        $itemFormatter = new ItemArrayFormatter($this->item);
        $itemData = $itemFormatter->format($with);
        $result = array_merge($itemData, [
            'id' => (int)$this->item->id,
            'name' => trim($this->item->name),
            'meta' => trim($this->item->meta),
            'metaJSON' => (array)json_decode($this->meta),
            'amount' => (int)$this->item->amount,
            'realprice' => (float)$this->item->realprice,
        ]);
        if ($this->item->additional) {
            $result['additional'] = $this->item->additional;
        }
        return $result;
    }
}
