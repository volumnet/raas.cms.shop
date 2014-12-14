<?php
namespace RAAS\CMS\Shop;
use \RAAS\Field as RAASField;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\CMS\EditBlockForm;
use \RAAS\CMS\Snippet;
use \RAAS\FormTab;
use \RAAS\FieldCollection;
use \RAAS\FieldSet;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Page;

class EditBlockYMLForm extends EditBlockForm
{
    protected static $currencies = array('RUR', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');

    protected static $rates = array('CBRF', 'NBU', 'NBK', 'CB');
    
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Main::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = array())
    {
        $this->view->js[] = $this->view->publicURL . '/edit_block_yml_currencies.js';
        $this->view->js[] = $this->view->publicURL . '/edit_block_yml_material_types.js';
        parent::__construct($params);
        $this->children['catsTab'] = $this->getCatsTab();
        $this->children = new FieldCollection(array(
            'commonTab' => $this->children['commonTab'], 
            'catsTab' => $this->children['catsTab'], 
            'serviceTab' => $this->children['serviceTab'], 
            'pagesTab' => $this->children['pagesTab']
        ));
    }


    protected function getInterfaceField()
    {
        $field = parent::getInterfaceField();
        $snippet = Snippet::importByURN('__RAAS_shop_yml_interface');
        $field->default = $snippet->id;
        return $field;
    }


    protected function getCommonTab()
    {
        $tab = parent::getCommonTab();
        $t = $this;
        $tab->children['shop_name'] = new RAASField(array('name' => 'shop_name', 'caption' => $this->view->_('SHOP_NAME')));
        $tab->children['company'] = new RAASField(array('name' => 'company', 'caption' => $this->view->_('COMPANY_NAME')));
        $tab->children['agency'] = new RAASField(array('name' => 'agency', 'caption' => $this->view->_('AGENCY_NAME')));
        $tab->children['email'] = new RAASField(array('name' => 'email', 'caption' => $this->view->_('AGENCY_EMAIL')));
        $tab->children['cpa'] = new RAASField(array('type' => 'checkbox', 'name' => 'cpa', 'caption' => $this->view->_('CPA')));
        $tab->children['local_delivery_cost'] = new RAASField(array(
            'type' => 'number', 'name' => 'local_delivery_cost', 'caption' => $this->view->_('LOCAL_DELIVERY_COST'), 'min' => 0, 'step' => 0.01
        ));

        $tab->children['currencies'] = new FieldSet(array(
            'caption' => $this->view->_('CURRENCIES'),
            'children' => array(
                'default_currency' => array(
                    'type' => 'select', 
                    'name' => 'default_currency', 
                    'caption' => $this->view->_('DEFAULT_CURRENCY'), 
                    'default' => 'RUR',
                    'children' => array_map(function($x) use ($t) { return array('value' => (string)$x, 'caption' => $t->view->_('CURRENCY_' . $x)); }, self::$currencies)
                ),
                'currencies' => new FieldSet(array(
                    'template' => 'cms/shop/edit_block_yml_currencies.inc.php',
                    'export' => function($FieldSet) {
                        $temp = array();
                        if (isset($_POST['rate'])) {
                            foreach ((array)$_POST['rate'] as $key => $val) {
                                if (trim($_POST['rate'][$key])) {
                                    $temp[] = array(
                                        'currency_name' => trim($key), 
                                        'currency_rate' => trim((trim($_POST['rate'][$key]) != '-1') ? $_POST['rate'][$key] : str_replace(',', '.', $_POST['rate_txt'][$key])),
                                        'currency_plus' => trim(str_replace(',', '.', $_POST['plus'][$key])),
                                    );
                                }
                            }
                        }
                        if ($temp) {
                            $FieldSet->Form->Item->meta_currencies = $temp;
                        }
                    },
                ))
            )
        ));
        $rates = static::$rates;
        foreach (self::$currencies as $val) {
            $tab->children['currencies']->children['currencies']->children[strtolower($val)] = new FieldSet(array(
                'caption' => $this->view->_('CURRENCY_' . $val),
                'children' => array(
                    'rate' => array(
                        'type' => 'select',
                        'data-role' => 'currency-selector',
                        'name' => 'rate[' . $val . ']',
                        'children' => array_merge(
                            array_map(function($x) use ($t) { return array('value' => $x, 'caption' => $t->view->_('CURRENCY_RATE_' . $x)); }, static::$rates),
                            array(array('value' => '-1', 'caption' => $this->view->_('CURRENCY_RATE_MANUAL')))
                        ),
                        'placeholder' => $this->view->_('_NONE'),
                        'import' => function($Field) use ($val, $rates) { 
                            if (isset($Field->Form->Item->currencies[$val])) {
                                $val2 = $Field->Form->Item->currencies[$val]['rate'];
                                if (in_array($val2, $rates)) {
                                    return $val2;
                                } else {
                                    return '-1';
                                }
                            } else {
                                return '';
                            }
                        }
                    ),
                    'rate_txt' => array(
                        'type' => 'number', 
                        'min' => 0, 
                        'step' => 0.01, 
                        'name' => 'rate_txt[' . $val . ']', 
                        'disabled' => true, 
                        'data-role' => 'currency-rate',
                        'import' => function($Field) use ($val, $rates) { 
                            if (isset($Field->Form->Item->currencies[$val])) {
                                $val2 = $Field->Form->Item->currencies[$val]['rate'];
                                if (!in_array($val2, $rates)) {
                                    return $val2;
                                }
                            }
                            return '';
                        }
                    ),
                    'plus' => array(
                        'type' => 'number', 
                        'min' => 0, 
                        'step' => 0.01, 
                        'name' => 'plus[' . $val . ']', 
                        'data-role' => 'currency-plus',
                        'import' => function($Field) use ($val, $rates) { 
                            if (isset($Field->Form->Item->currencies[$val])) {
                                $val2 = $Field->Form->Item->currencies[$val]['plus'];
                                return $val2;
                            }
                            return '';
                        }
                    )
                )
            ));
        }
        $mt = new Material_Type();
        
        if ($this->Item->id) {
            $tab->children['material_types'] = new FieldSet(array(
                'caption' => $this->view->_('MATERIAL_TYPES'),
                'template' => 'cms/shop/edit_block_yml_material_types.inc.php',
                'meta' => array('Table' => new EditBlockYMLMaterialTypesTable(array('Item' => $this->Item)), 'Page' => new Page((int)$_GET['pid'])),
                'children' => array(
                    'types_select' => new RAASField(array(
                        'type' => 'select', 'caption' => $this->view->_('MATERIAL_TYPE'), 'children' => array('Set' => $mt->children), 'id' => 'types_select'
                    )),
                )
            ));
        }
        $tab->children[] = $this->getWidgetField();
        return $tab;
    }


    protected function getServiceTab()
    {
        $tab = parent::getServiceTab();
        $tab->children[] = $this->getInterfaceField();
        return $tab;
    }


    protected function getCatsTab()
    {
        $tab = new FormTab(array('name' => 'meta_cats', 'caption' => $this->view->_('CATALOG_CATEGORIES')));
        $t = $this;
        $tab->children[] = new RAASField(array(
            'type' => 'checkbox', 
            'name' => 'meta_cats', 
            'caption' => $this->view->_('CATALOG_CATEGORIES'), 
            'multiple' => 'multiple', 
            'children' => $this->meta['CONTENT']['cats'],
            'check' => function($Field) use ($t) {
                if (!isset($_POST['meta_cats']) || !$_POST['meta_cats']) {
                    return array('name' => 'MISSED', 'value' => $Field->name, 'description' => $t->view->_('ERR_NO_CATEGORIES'));
                }
            },
            'import' => function($Field) { return $Field->Form->Item->catalog_cats_ids; },
        ));
        return $tab;
    }

}