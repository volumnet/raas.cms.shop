<?php
/**
 * Товар корзины
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;

/**
 * Класс товара корзины
 * @property int $id ID# материала
 * @property Material $material Материал товара корзины
 * @property string $name Наименование товара
 * @property string $meta Мета-данные товара
 * @property array $metaJSON Мета-данные товара в форме ассоциативного массива
 * @property float $realprice Реальная цена товара
 * @property int $amount Количество товара
 * @property mixed $additional Дополнительные данные для товара
 * @property-read float $sum Сумма по товару
 */
class CartItem
{
    /**
     * ID# материала
     * @var int
     */
    protected $id = 0;

    /**
     * Наименование товара
     * @var string
     */
    protected $name = '';

    /**
     * Мета-данные товара
     * @var string
     */
    protected $meta = '';

    /**
     * Реальная цена товара
     * @var float
     */
    protected $realprice = 0;

    /**
     * Количество товара
     * @var int
     */
    protected $amount = 1;

    /**
     * Дополнительные данные для товара
     * @var mixed
     */
    protected $additional;

    public function __get($var)
    {
        switch ($var) {
            case 'material':
                return new Material($this->id);
                break;
            case 'metaJSON':
                return (array)json_decode($this->meta);
                break;
            case 'sum':
                return $this->realprice * $this->amount;
                break;
            default:
                return $this->$var;
                break;
        }
    }


    public function __set($var, $val)
    {
        switch ($var) {
            case 'id':
            case 'amount':
                $this->$var = (int)$val;
                break;
            case 'realprice':
                $this->$var = (float)$val;
                break;
            case 'name':
            case 'meta':
                $this->$var = trim($val);
                break;
            case 'metaJSON':
                $this->$var = json_encode((array)$val);
                break;
            case 'material':
                if ($val instanceof Material) {
                    $this->id = (int)$val->id;
                }
                break;
        }
    }


    /**
     * Конструктор класса
     * @param array $data Данные для заполнения
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $val) {
            if (isset($val)) {
                $this->$key = $data[$key];
            }
        }
    }
}
