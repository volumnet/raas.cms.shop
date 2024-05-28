<?php
/**
 * Заказ
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\SOME;
use SOME\Text;
use RAAS\Application;
use RAAS\User as RAASUser;
use RAAS\CMS\Controller_Frontend;
use RAAS\CMS\Feedback;
use RAAS\CMS\Form_Field;
use RAAS\CMS\FormInterface;
use RAAS\CMS\Material;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;
use RAAS\CMS\User;

/**
 * Класс заказа
 * @property-read User $user Пользователь, который сделал заказ
 * @property-read Cart_Type $parent Родительский тип корзины
 * @property-read Page $page Страница, с которой сделан заказ
 * @property-read RAASUser $viewer Администратор, который просмотрел заказ
 * @property-read Order_Status $status Статус заказа
 * @property-read Snippet $paymentInterface Интерфейс заказа
 * @property-read Form_Field[] $fields Поля заказа с установленным Owner
 * @property-read Form_Field[] $visFields Видимые поля заказа с установленным Owner
 * @property-read Material[] $items Товары заказа с установленным amount и meta
 * @property-read Order_Status[] $history История заказа
 * @property-read int $count Количество товаров в заказе
 * @property-read float $sum Сумма заказа
 * @property-read float $weight Вес заказа, кг
 * @property-read array $sizes <pre><code>[
 *     int Длина, см,
 *     int Ширина, см,
 *     int Высота, см
 * ]</code></pre> Размеры заказа
 */
class Order extends Feedback
{
    protected static $tablename = 'cms_shop_orders';

    protected static $references = [
        'user' => [
            'FK' => 'uid',
            'classname' => User::class,
            'cascade' => true
        ],
        'parent' => [
            'FK' => 'pid',
            'classname' => Cart_Type::class,
            'cascade' => true
        ],
        'page' => [
            'FK' => 'page_id',
            'classname' => Page::class,
            'cascade' => false
        ],
        'viewer' => [
            'FK' => 'vis',
            'classname' => RAASUser::class,
            'cascade' => false
        ],
        'status' => [
            'FK' => 'status_id',
            'classname' => Order_Status::class,
            'cascade' => false
        ],
        'paymentInterface' => [
            'FK' => 'payment_interface_id',
            'classname' => Snippet::class,
            'cascade' => false
        ],
    ];

    protected static $cognizableVars = ['fields', 'visFields', 'items'];

    protected static $children = [
        'history' => [
            'classname' => Order_History::class,
            'FK' => 'order_id'
        ]
    ];

    public function __get($var)
    {
        switch ($var) {
            case 'count':
                $sum = 0;
                foreach ($this->items as $row) {
                    $sum += $row->amount;
                }
                return $sum;
                break;
            case 'sum':
                $sum = 0;
                foreach ($this->items as $row) {
                    $sum += $row->amount * $row->realprice;
                }
                return $sum;
                break;
            case 'weight':
                $result = $this->parent->getWeight($this->items);
                return $result;
                break;
            case 'sizes':
                $result = $this->parent->getSizes($this->items);
                return $result;
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function commit()
    {
        if ((($this->updates['status_id'] ?? null) !== null) &&
            (($this->properties['status_id'] ?? null) != ($this->updates['status_id'] ?? null)) &&
            $this->status->do_notify
        ) {
            $this->notifyStatus();
        }
        parent::commit();
        if ($this->meta_items) {
            $sqlQuery = "DELETE FROM " . static::_dbprefix() . "cms_shop_orders_goods
                          WHERE order_id = " . (int)$this->id;
            static::$SQL->query($sqlQuery);
            $arr = [];
            foreach ($this->meta_items as $i => $row) {
                $arr[] = array_merge(['order_id' => (int)$this->id, 'priority' => $i + 1], (array)$row);
            }
            static::$SQL->add(static::_dbprefix() . "cms_shop_orders_goods", $arr);
            unset($this->meta_items);
        }
    }


    protected function _fields()
    {
        $temp = $this->parent->Form->fields;
        $arr = [];
        foreach ($temp as $row) {
            $row->Owner = $this;
            $arr[$row->urn] = $row;
        }
        return $arr;
    }


    public static function delete(SOME $Item)
    {
        $sqlQuery = "DELETE FROM " . static::_dbprefix() . "cms_shop_orders_goods
                      WHERE order_id = " . (int)$Item->id;
        static::$SQL->query($sqlQuery);
        parent::delete($Item);
    }


    protected function _items()
    {
        // 2020-06-25, AVS: добавил tOG.name, чтобы доставка в письме значилась
        // под произвольным именем, а не под именем материала
        // 2020-12-27, AVS: поменял FROM и LEFT JOIN местами, чтобы отображались
        // позиции без материала
        $sqlQuery = "SELECT tM.*,
                            tM.name AS originalName,
                            tOG.meta,
                            tOG.name,
                            tOG.realprice,
                            tOG.amount
                        FROM " . self::_dbprefix() . "cms_shop_orders_goods AS tOG
                   LEFT JOIN " . Material::_tablename() . " AS tM ON tOG.material_id = tM.id
                       WHERE tOG.order_id = " . (int)$this->id . "
                    ORDER BY tOG.priority";
        $Set = Material::getSQLSet($sqlQuery);
        return $Set;
    }

    /**
     * Получает список товаров в виде массива текста (для комментариев)
     * @param array<
     *            Material материал с полями заказа (
     *                'meta' => string Мета-данные материала в заказе
     *                'realprice' => float Стоимость материала в заказе
     *                                     (за единицу)
     *                'amount' => int Количество
     *            )|array<[
     *                'material_id' => int ID# материала
     *                'name' => string Наименование материала
     *                'meta' => string Мета-данные материала в заказе
     *                'realprice' => float Стоимость материала в заказе
     *                                     (за единицу)
     *                'amount' => int Количество
     *            ]>
     *        > $items Список товаров
     * @return string[]
     */
    public static function getItemsTextArr(array $items = []): array
    {
        $arr = [];
        foreach ($items as $item) {
            if ($item instanceof Material) {
                $arr[] = '#' . (int)$item->id . ' ' . $item->name
                       . ($item->meta['meta'] ? ' (' . $item->meta['meta'] . ')' : '')
                       . ': ' . (float)$item->realprice . ' x ' . (int)$item->amount
                       . ' = ' . (float)($item->realprice * $item->amount);
            } elseif (is_array($item)) {
                $arr[] = '#' . (int)$item['material_id'] . ' ' . $item['name']
                       . ($item['meta'] ? ' (' . $item['meta'] . ')' : '')
                       . ': ' . (float)$item['realprice'] . ' x ' . (int)$item['amount']
                       . ' = ' . (float)($item['realprice'] * $item['amount']);
            }
        }
        return $arr;
    }


    /**
     * Уведомить пользователя об изменении статуса
     * @param bool $debug Режим отладки
     * @return array|null <pre><code>array<
     *    'emails' => [
     *        'emails' => array<string> e-mail адреса,
     *        'subject' => string Тема письма,
     *        'message' => string Тело письма,
     *        'from' => string Поле "от",
     *        'fromEmail' => string Обратный адрес,
     *        'attachments' => array<[
     *            'tmp_name' => string Путь к реальному файлу,
     *            'type' => string MIME-тип файла,
     *            'name' => string Имя файла
     *        ]> вложения,
     *        'embedded' => array<[
     *            'tmp_name' => string Путь к реальному файлу,
     *            'type' => string MIME-тип файла,
     *            'name' => string Имя файла
     *        ]> встроенные файлы,
     *    ],
     *    'smsPhones' => array<string URL SMS-шлюза>
     * >|null</code></pre> Набор отправляемых писем либо URL SMS-шлюза (только в режиме отладки)
     */
    public function notifyStatus($debug = false)
    {
        $dataArr = ['ID' => (int)$this->id];
        $emails = [];
        foreach ($this->fields as $field) {
            if (!in_array($field->datatype, ['material', 'file', 'image']) &&
                !$field->multiple
            ) {
                $origVal = $field->getValue();
                if ($field->source) {
                    $val = $field->doRich($origVal);
                    $dataArr[$field->urn] = $origVal;
                } else {
                    $val = $origVal;
                }
                $dataArr[mb_strtoupper($field->urn)] = $val;
                if (($field->datatype == 'email') || ($field->urn == 'email')) {
                    $emails[] = $val;
                }
            }
        }
        if ($emails) {
            $subjectTemplate = $this->status->notification_title;
            $subjectTemplate = strtr($subjectTemplate, ['&#39;' => "'"]);
            $subject = Text::renderTemplate($subjectTemplate, $dataArr);

            $messageTemplate = $this->status->notification;
            $messageTemplate = strtr($messageTemplate, ['&#39;' => "'"]);
            $message = Text::renderTemplate($messageTemplate, $dataArr);

            $formInterface = new FormInterface();
            $processEmbedded = $formInterface->processEmbedded($message);
            $message = Text::inlineCSS($processEmbedded['message']);
            $embedded = (array)$processEmbedded['embedded'];

            $fromName = ViewSub_Orders::i()->_('ADMINISTRATION_OF_SITE') . ' ' . Controller_Frontend::i()->idnHost;

            if ($debug) {
                $debugMessages['emails'] = [
                    'emails' => $emails,
                    'subject' => $subject,
                    'message' => $message,
                    'from' => $fromName,
                    'fromEmail' => null,
                    'attachments' => [],
                    'embedded' => $embedded,
                ];
                return $debugMessages;
            } else {
                // @codeCoverageIgnoreStart
                Application::i()->sendmail(
                    $emails,
                    $subject,
                    $message,
                    ViewSub_Orders::i()->_('ADMINISTRATION_OF_SITE') . ' ' . Controller_Frontend::i()->idnHost,
                    null,
                    true,
                    [],
                    $embedded
                );
                // @codeCoverageIgnoreEnd
            }
        }
    }


    /**
     * Находит заказ по ID# оплаты и платежному интерфейсу
     * @param string $paymentId ID# оплаты
     * @param Snippet|null $paymentInterface Интерфейс оплаты (для дополнительной проверки)
     * @return Order|null Возвращает найденный заказ, либо null, если ничего не найдено
     */
    public static function importByPayment($paymentId, Snippet $paymentInterface = null)
    {
        if (!$paymentId) {
            return null;
        }
        $sqlQuery = "SELECT * FROM " . static::_tablename()
                  . " WHERE payment_id = ?";
        $sqlBind = [$paymentId];
        if ($paymentInterface && $paymentInterface->id) {
            $sqlQuery .= " AND payment_interface_id = ?";
            $sqlBind[] = (int)$paymentInterface->id;
        }
        $sqlQuery .= " LIMIT 1";
        $sqlResult = static::$SQL->getline([$sqlQuery, $sqlBind]);
        if (!$sqlResult) {
            return null;
        }
        $order = new static($sqlResult);
        return $order;
    }
}
