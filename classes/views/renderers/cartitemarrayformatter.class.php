<?php
/**
 * Форматтер массива для товара корзины
 */
namespace RAAS\CMS\Shop;

/**
 * Класс форматтера массива для товара корзины
 */
class CartItemArrayFormatter
{
    /**
     * Материал товара корзины
     * @var CartItem
     */
    public $item;

    /**
     * Конструктор класса
     * @param CartItem $item Материал товара корзины
     */
    public function __construct(CartItem $item)
    {
        $this->item = $item;
    }


    /**
     * Форматирует данные
     * @param array $with <pre>array<(
     *     string[] URN поля => function (mixed $value Значение поля): mixed Обработчик данных
     * )|(
     *     int[] Индекс поля => string URN поля
     * )> Массив дополнительных полей для отображения
     * @return array <pre>array<string[] Свойство товара => mixed></pre>
     */
    public function format(array $with = [])
    {
        $itemData = [];
        if ($material = $this->item->material) {
            $itemFormatter = new ItemArrayFormatter($material);
            $itemData = $itemFormatter->format($with);
        }
        $result = array_merge($itemData, [
            'id' => (int)$this->item->id,
            'name' => trim($this->item->name),
            'meta' => trim($this->item->meta),
            'metaJSON' => (object)$this->item->metaJSON,
            'amount' => (int)$this->item->amount,
            'price' => (float)$this->item->realprice,
        ]);
        if ($this->item->additional) {
            $result['additional'] = $this->item->additional;
        }
        return $result;
    }
}
