<?php
namespace RAAS\CMS\Shop;
use \RAAS\Field as RAASField;
use \RAAS\FormTab;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\CMS\EditBlockForm;
use \RAAS\CMS\Snippet;

class EditBlockCartForm extends EditBlockForm
{
    protected static $currencies = array('RUR', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');

    public function __construct(array $params = array())
    {
        parent::__construct($params);
        $this->children['epayTab'] = $this->getEPayTab();
    }


    protected function getInterfaceField()
    {
        $field = parent::getInterfaceField();
        $snippet = Snippet::importByURN('__raas_shop_cart_interface');
        $field->default = $snippet->id;
        return $field;
    }


    protected function getCommonTab()
    {
        $tab = parent::getCommonTab();
        $tab->children[] = new RAASField(array(
            'type' => 'select', 'name' => 'cart_type', 'caption' => Module::i()->view->_('CART'), 'children' => array('Set' => Cart_Type::getSet())
        ));
        $tab->children[] = $this->getWidgetField();
        return $tab;
    }


    protected function getServiceTab()
    {
        $tab = parent::getServiceTab();
        $tab->children[] = $this->getInterfaceField();
        return $tab;
    }


    protected function getEPayTab()
    {
        $tab = new FormTab(array(
            'name' => 'epay',
            'caption' => Module::i()->view->_('EPAY'),
            'children' => array(
                'epay_interface_id' => $this->getEPayField(),
                'epay_login' => array('name' => 'epay_login', 'caption' => Module::i()->view->_('EPAY_LOGIN')),
                'epay_pass1' => array(
                    'type' => 'password', 
                    'name' => 'epay_pass1', 
                    'caption' => Module::i()->view->_('EPAY_PASSWORD1'),
                    'export' => function($Field) use ($t) { 
                        if ($_POST[$Field->name]) {
                            $Field->Form->Item->{$Field->name} = trim($_POST[$Field->name]);
                        }
                    }
                ),
                'epay_pass2' => array(
                    'type' => 'password', 
                    'name' => 'epay_pass2', 
                    'caption' => Module::i()->view->_('EPAY_PASSWORD2'),
                    'export' => function($Field) use ($t) { 
                        if ($_POST[$Field->name]) {
                            $Field->Form->Item->{$Field->name} = trim($_POST[$Field->name]);
                        }
                    }
                ),
                'epay_test' => array('type' => 'checkbox', 'name' => 'epay_test', 'caption' => Module::i()->view->_('TEST_MODE'), 'default' => 1),
                'epay_currency' => array(
                    'type' => 'select', 
                    'name' => 'epay_currency', 
                    'caption' => Module::i()->view->_('CURRENCY'), 
                    'default' => 'RUR',
                    'children' => array_map(function($x) { return array('value' => (string)$x, 'caption' => Module::i()->view->_('CURRENCY_' . $x)); }, self::$currencies)
                ),
            )
        ));
        return $tab;
    }


    protected function getEPayField()
    {
        $field = $this->getInterfaceField();
        $field->required = false;
        $field->caption = Module::i()->view->_('EPAY_INTERFACE');
        $field->name = 'epay_interface_id';
        $field->default = null;
        return $field;
    }
}