<?php
/**
 * Форматтер массива для заказа
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\CMS\FieldArrayFormatter;
use RAAS\CMS\Form_Field;

/**
 * Класс форматтера массива для заказа
 */
class OrderArrayFormatter
{
    /**
     * Заказ для форматирования
     * @var Order
     */
    public $order;

    /**
     * Конструктор класса
     * @param Order $order Форма для форматирования
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Форматирует данные
     * @param array $with <pre>array<(
     *     string[] URN поля => function (Order $order Заказ): mixed Обработчик данных
     * )|(
     *     int[] Индекс поля => string URN поля
     * )> Массив дополнительных полей заказа для отображения
     * @param array $itemWith <pre>array<(
     *     string[] URN поля => function (Material $item Товар заказа): mixed Обработчик данных
     * )|(
     *     int[] Индекс поля => string URN поля
     * )> Массив дополнительных полей каждого поля товара для отображения
     * @param array $fieldWith <pre>array<(
     *     string[] URN поля => function (Form_Field $form Форма): mixed Обработчик данных
     * )|(
     *     int[] Индекс поля => string URN поля
     * )> Массив дополнительных полей каждого поля поля для отображения
     * @param array $fieldWith <pre>array<(
     *     string[] URN поля => function (Order_Status $form Форма): mixed Обработчик данных
     * )|(
     *     int[] Индекс поля => string URN поля
     * )> Массив дополнительных полей каждого поля статуса для отображения
     * @return array <pre>array<string[] Свойство заказа => mixed></pre>
     */
    public function format(
        array $with = [],
        array $itemWith = [],
        array $fieldWith = [],
        array $statusWith = []
    ) {
        $result = (array)$this->order->getArrayCopy();
        foreach (['id', 'uid', 'pid', 'page_id', 'status_id'] as $key) {
            if ($result[$key] !== null) {
                $result[$key] = (int)$result[$key];
            }
        }

        foreach (['vis', 'paid'] as $key) {
            if ($result[$key] !== null) {
                $result[$key] = (bool)(int)$result[$key];
            }
        }

        $result['sum'] = (float)$this->order->sum;

        $statusFormatter = new OrderStatusArrayFormatter($this->order->status);
        $result['status'] = $statusFormatter->format($statusWith);


        $result['items'] = array_map(function ($item) use ($itemWith) {
            $orderItemFormatter = new OrderItemArrayFormatter($item);
            return $orderItemFormatter->format($itemWith);
        }, $this->order->items);

        $result['fields'] = array_map(function ($field) use ($fieldWith) {
            $fieldArrayFormatter = new FieldArrayFormatter($field);
            $vals = $field->getValues(true);
            $result = [];
            foreach ($vals as $val) {
                $url = '';
                $text = $field->doRich($val);
                switch ($field->datatype) {
                    case 'email':
                        $url = 'mailto:' . $val;
                        break;
                    case 'tel':
                        $url = 'tel:%2B' . Text::beautifyPhone($val, 11);
                        break;
                    case 'url':
                        $url = $val;
                        break;
                    case 'material':
                        if ($tmpUrl = $val->url) {
                            $url = $tmpUrl;
                        }
                        $text = $val->name;
                        $val = $val->id;
                        break;
                    case 'checkbox':
                        if (!$field->multiple) {
                            $text = View_Web::i()->_($val ? '_YES' : '_NO');
                        }
                        break;
                }
                $result['values'][] = [
                    'value' => $text,
                    'url' => $url,
                ];
            }
            $result = array_merge(
                $result,
                $fieldArrayFormatter->format($fieldWith)
            );
            return $result;
        }, $this->order->visFields);

        foreach ($with as $key => $val) {
            $value = null;
            if (is_numeric($key) && is_string($val)) {
                $urn = $val;
                if (is_scalar($val) || is_array($val)) {
                    $value = $this->form->$val;
                }
            } elseif (is_string($key)) {
                $urn = $key;
                $value = is_callable($val) ? $val($this->form) : $val;
            }
            if ($value !== null) {
                $result[$urn] = $value;
            }
        }
        return $result;
    }
}
