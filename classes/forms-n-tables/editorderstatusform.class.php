<?php
/**
 * Форма редактирования статуса заказа
 */
namespace RAAS\CMS\Shop;

use RAAS\Form as RAASForm;
use RAAS\Field as RAASField;
use RAAS\CMS\Form;
use RAAS\CMS\Form_Field;

/**
 * Класс формы редактирования статуса заказа
 * @property-read ViewSub_Dev $view Представление
 */
class EditOrderStatusForm extends RAASForm
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Dev::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = [])
    {
        $view = $this->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;

        $defaultParams = [
            'caption' => $Item->id ? $Item->name : $this->view->_('EDIT_ORDER_STATUS'),
            'parentUrl' => Sub_Dev::i()->url . '&action=order_statuses',
            'children' => [
                'name' => [
                    'name' => 'name',
                    'caption' => $this->view->_('NAME'),
                    'required' => 'required',
                ],
                'urn' => [
                    'name' => 'urn',
                    'caption' => $this->view->_('URN'),
                ],
                'do_notify' => [
                    'name' => 'do_notify',
                    'caption' => $this->view->_('NOTIFY_USER_ABOUT_STATUS'),
                    'type' => 'checkbox',
                ],
                'notification_title' => [
                    'name' => 'notification_title',
                    'caption' => $this->view->_('STATUS_NOTIFICATION_TITLE'),
                    'class' => 'span5',
                ],
                'notification' => [
                    'name' => 'notification',
                    'caption' => $this->view->_('STATUS_NOTIFICATION'),
                    'type' => 'htmlarea',
                ],
                'legend' => $this->getLegendField(),
            ]
        ];
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }


    /**
     * Получает поле легенды для уведомления
     * @return RAASField
     */
    public function getLegendField()
    {
        $field = new RAASField([
            'name' => 'legend',
            'export' => 'is_null',
            'import' => function (RAASField $field) {
                $sqlQuery = "SELECT tCT.id AS cart_type_id, tFF.id, tFF.urn, tFF.name
                               FROM " . Cart_Type::_tablename() . " AS tCT
                               JOIN " . Form::_tablename() . " AS tF ON tF.id = tCT.form_id
                               JOIN " . Form_Field::_tablename() . " AS tFF ON tFF.pid = tF.id AND tFF.classname = ?
                              WHERE NOT tCT.no_amount
                                AND tFF.datatype NOT IN ('material', 'file', 'image')
                                AND NOT tFF.multiple";
                $sqlResult = Cart_Type::_SQL()->get([$sqlQuery, [Form::class]]);
                $result = [];
                foreach ($sqlResult as $sqlRow) {
                    $result[trim($sqlRow['cart_type_id'])][$sqlRow['urn']] = $sqlRow['name'];
                }
                $result = array_values($result);
                $result = array_reduce(array_slice($result, 1), 'array_intersect_key', $result[0]);
                $result = array_merge(['id' => ViewSub_Dev::i()->_('ORDER_ID')], $result);
                return $result;
            },
            'template' => 'status_notification_field.inc.php',
        ]);
        return $field;
    }
}
