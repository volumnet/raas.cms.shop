<?php
/**
 * Форма редактирования блока корзины
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\FormTab;
use RAAS\CMS\Form as CMSForm;
use RAAS\CMS\EditBlockForm;
use RAAS\CMS\InterfaceField;
use RAAS\CMS\Snippet;

/**
 * Форма редактирования блока корзины
 */
class EditBlockCartForm extends EditBlockForm
{
    const DEFAULT_BLOCK_CLASSNAME = Block_Cart::class;

    protected static $currencies = ['RUR', 'USD', 'EUR', 'UAH', 'BYR', 'KZT'];

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->children['epayTab'] = $this->getEPayTab();
    }


    protected function getCommonTab(): FormTab
    {
        $tab = parent::getCommonTab();
        $tab->children['cart_type'] = new RAASField([
            'type' => 'select',
            'name' => 'cart_type',
            'caption' => Module::i()->view->_('CART'),
            'children' => ['Set' => Cart_Type::getSet()],
        ]);
        $tab->children['widget_id'] = $this->getWidgetField();
        return $tab;
    }


    protected function getServiceTab(): FormTab
    {
        $tab = parent::getServiceTab();
        $tab->children['interface_id'] = $this->getInterfaceField();
        return $tab;
    }


    protected function getEPayTab(): FormTab
    {
        $tab = new FormTab([
            'name' => 'epay',
            'caption' => Module::i()->view->_('EPAY'),
            'children' => [
                'epay_interface_id' => $this->getEPayField(),
                'epay_login' => [
                    'name' => 'epay_login',
                    'caption' => Module::i()->view->_('EPAY_LOGIN')
                ],
                'epay_pass1' => [
                    'type' => 'password',
                    'name' => 'epay_pass1',
                    'caption' => Module::i()->view->_('EPAY_PASSWORD1'),
                    'export' => function ($field) {
                        if ($_POST[$field->name]) {
                            $field->Form->Item->{$field->name} = trim($_POST[$field->name]);
                        }
                    }
                ],
                'epay_pass2' => [
                    'type' => 'password',
                    'name' => 'epay_pass2',
                    'caption' => Module::i()->view->_('EPAY_PASSWORD2'),
                    'export' => function ($field) {
                        if ($_POST[$field->name]) {
                            $field->Form->Item->{$field->name} = trim($_POST[$field->name]);
                        }
                    }
                ],
                'epay_test' => [
                    'type' => 'checkbox',
                    'name' => 'epay_test',
                    'caption' => Module::i()->view->_('TEST_MODE'),
                    'default' => 1
                ],
                'epay_currency' => [
                    'type' => 'select',
                    'name' => 'epay_currency',
                    'caption' => Module::i()->view->_('CURRENCY'),
                    'default' => 'RUR',
                    'children' => array_map(function ($x) {
                        return [
                            'value' => (string)$x,
                            'caption' => Module::i()->view->_('CURRENCY_' . $x)
                        ];
                    }, self::$currencies),
                ],
            ]
        ]);
        return $tab;
    }


    protected function getEPayField(): InterfaceField
    {
        $field = new InterfaceField([
            'name' => 'epay_interface_id',
            'meta' => [
                'interfaceClassnameFieldName' => 'epay_interface_classname',
                'rootInterfaceClass' => EPayInterface::class
            ],
            'caption' => Module::i()->view->_('EPAY_INTERFACE'),
        ]);
        return $field;
    }
}
