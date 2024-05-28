<?php
/**
 * Форма редактирования блока Яндекс-Маркета
 */
namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\FieldCollection;
use RAAS\FieldSet;
use RAAS\FormTab;
use RAAS\CMS\EditBlockForm;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс формы редактирования блока Яндекс-Маркета
 */
class EditBlockYMLForm extends EditBlockForm
{
    /**
     * Валюты
     * @var string[]
     */
    protected static $currencies = ['RUB', 'USD', 'EUR', 'UAH', 'BYR', 'KZT'];

    /**
     * Курсы валют
     * @var string[]
     */
    protected static $rates = ['CBRF', 'NBU', 'NBK', 'CB'];

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


    public function __construct(array $params = [])
    {
        $this->view->js[] = $this->view->publicURL
            . '/edit_block_yml_currencies.js';
        $this->view->js[] = $this->view->publicURL
            . '/edit_block_yml_material_types.js';
        parent::__construct($params);
        $this->children['catsTab'] = $this->getCatsTab();
        $this->children = new FieldCollection([
            'commonTab' => $this->children['commonTab'],
            'catsTab' => $this->children['catsTab'],
            'serviceTab' => $this->children['serviceTab'],
            'pagesTab' => $this->children['pagesTab']
        ]);
    }


    protected function getInterfaceField(): RAASField
    {
        $field = parent::getInterfaceField();
        $snippet = Snippet::importByURN('__raas_shop_yml_interface');
        $field->default = $snippet->id;
        return $field;
    }


    protected function getCommonTab(): FormTab
    {
        $tab = parent::getCommonTab();
        $t = $this;
        $tab->children['shop_name'] = new RAASField([
            'name' => 'shop_name',
            'caption' => $this->view->_('SHOP_NAME'),
        ]);
        $tab->children['company'] = new RAASField([
            'name' => 'company',
            'caption' => $this->view->_('COMPANY_NAME'),
        ]);
        $tab->children['agency'] = new RAASField([
            'name' => 'agency',
            'caption' => $this->view->_('AGENCY_NAME'),
            'default' => 'Volume Networks',
        ]);
        $tab->children['email'] = new RAASField([
            'name' => 'email',
            'caption' => $this->view->_('AGENCY_EMAIL'),
            'default' => 'info@volumnet.ru',
        ]);
        $tab->children['cpa'] = new RAASField([
            'type' => 'checkbox',
            'name' => 'cpa',
            'caption' => $this->view->_('CPA'),
        ]);
        // @deprecated 2020-12-09 устарело согласно рекомендации
        // https://yandex.ru/support/partnermarket/elements/shop.html
        // $tab->children['local_delivery_cost'] = new RAASField([
        //     'type' => 'number',
        //     'name' => 'local_delivery_cost',
        //     'caption' => $this->view->_('LOCAL_DELIVERY_COST'),
        //     'min' => 0,
        //     'step' => 0.01,
        // ]);
        $deliveryOptionsImport = function ($field) {
            return (array)json_decode((string)$field->Form->Item->{$field->name}, true);
        };
        $deliveryOptionsExport = function ($field) {
            $result = [];
            foreach ((array)($_POST[$field->name . '@cost'] ?? []) as $i => $val) {
                $resultRow = [
                    'cost' => (int)$_POST[$field->name . '@cost'][$i],
                    'days' => trim($_POST[$field->name . '@days'][$i]),
                    'order_before' => trim($_POST[$field->name . '@order_before'][$i]),
                ];
                $result[] = $resultRow;
            }
            $json = json_encode($result);
            $field->Form->Item->{$field->name} = $json;
        };
        $tab->children['delivery_options'] = new RAASField([
            'name' => 'delivery_options',
            'caption' => $this->view->_('DELIVERY_OPTIONS'),
            'template' => 'cms/shop/edit_block_yml_delivery_options.inc.php',
            'import' => $deliveryOptionsImport,
            'export' => $deliveryOptionsExport,
        ]);
        $tab->children['pickup_options'] = new RAASField([
            'name' => 'pickup_options',
            'caption' => $this->view->_('PICKUP_OPTIONS'),
            'template' => 'cms/shop/edit_block_yml_delivery_options.inc.php',
            'import' => $deliveryOptionsImport,
            'export' => $deliveryOptionsExport,
        ]);

        $tab->children['currencies'] = new FieldSet([
            'caption' => $this->view->_('CURRENCIES'),
            'children' => [
                'default_currency' => [
                    'type' => 'select',
                    'name' => 'default_currency',
                    'caption' => $this->view->_('DEFAULT_CURRENCY'),
                    'default' => 'RUB',
                    'children' => array_map(function ($x) use ($t) {
                        return [
                            'value' => (string)$x,
                            'caption' => $t->view->_('CURRENCY_' . $x)
                        ];
                    }, self::$currencies)
                ],
                'currencies' => new FieldSet([
                    'template' => 'cms/shop/edit_block_yml_currencies.inc.php',
                    'export' => function ($FieldSet) {
                        $temp = [];
                        if (isset($_POST['rate'])) {
                            foreach ((array)$_POST['rate'] as $key => $val) {
                                if (trim($_POST['rate'][$key])) {
                                    $temp[] = [
                                        'currency_name' => trim($key),
                                        'currency_rate' => trim(
                                            (trim($_POST['rate'][$key]) != '-1') ?
                                            $_POST['rate'][$key] :
                                            str_replace(
                                                ',',
                                                '.',
                                                $_POST['rate_txt'][$key]
                                            )
                                        ),
                                        'currency_plus' => trim(str_replace(
                                            ',',
                                            '.',
                                            $_POST['plus'][$key]
                                        )),
                                    ];
                                }
                            }
                        }
                        if ($temp) {
                            $FieldSet->Form->Item->meta_currencies = $temp;
                        }
                    },
                ])
            ]
        ]);
        $rates = static::$rates;
        foreach (self::$currencies as $val) {
            $tab->children['currencies']->children['currencies']->children[strtolower($val)] = new FieldSet([
                'caption' => $this->view->_('CURRENCY_' . $val),
                'children' => [
                    'rate' => [
                        'type' => 'select',
                        'data-role' => 'currency-selector',
                        'name' => 'rate[' . $val . ']',
                        'children' => array_merge(
                            array_map(function ($x) use ($t) {
                                return [
                                    'value' => $x,
                                    'caption' => $t->view->_(
                                        'CURRENCY_RATE_' . $x
                                    )
                                ];
                            }, static::$rates),
                            [[
                                'value' => '-1',
                                'caption' => $this->view->_(
                                    'CURRENCY_RATE_MANUAL'
                                )
                            ]]
                        ),
                        'placeholder' => $this->view->_('_NONE'),
                        'import' => function ($Field) use ($val, $rates) {
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
                    ],
                    'rate_txt' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'name' => 'rate_txt[' . $val . ']',
                        'disabled' => true,
                        'data-role' => 'currency-rate',
                        'import' => function ($Field) use ($val, $rates) {
                            if (isset($Field->Form->Item->currencies[$val])) {
                                $val2 = $Field->Form->Item->currencies[$val]['rate'];
                                if (!in_array($val2, $rates)) {
                                    return $val2;
                                }
                            }
                            return '';
                        }
                    ],
                    'plus' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => 0.01,
                        'name' => 'plus[' . $val . ']',
                        'data-role' => 'currency-plus',
                        'import' => function ($Field) use ($val, $rates) {
                            if (isset($Field->Form->Item->currencies[$val])) {
                                $val2 = $Field->Form->Item->currencies[$val]['plus'];
                                return $val2;
                            }
                            return '';
                        }
                    ]
                ]
            ]);
        }
        $mt = new Material_Type();

        if ($this->Item->id) {
            $tab->children['material_types'] = new FieldSet([
                'caption' => $this->view->_('MATERIAL_TYPES'),
                'template' => 'cms/shop/edit_block_yml_material_types.inc.php',
                'meta' => [
                    'Table' => new EditBlockYMLMaterialTypesTable([
                        'Item' => $this->Item
                    ]),
                    'Page' => new Page((int)($_GET['pid'] ?? 0))
                ],
                'children' => [
                    'types_select' => new RAASField([
                        'type' => 'select',
                        'caption' => $this->view->_('MATERIAL_TYPE'),
                        'children' => ['Set' => $mt->children],
                        'id' => 'types_select'
                    ]),
                ]
            ]);
        }
        return $tab;
    }


    protected function getServiceTab(): FormTab
    {
        $tab = parent::getServiceTab();
        $tab->children[] = $this->getInterfaceField();
        return $tab;
    }


    /**
     * Получает вкладку категорий каталога для выгрузки
     * @return FormTab
     */
    protected function getCatsTab(): FormTab
    {
        $tab = new FormTab([
            'name' => 'meta_cats',
            'caption' => $this->view->_('CATALOG_CATEGORIES')
        ]);
        $t = $this;
        $tab->children[] = new RAASField([
            'type' => 'checkbox',
            'name' => 'meta_cats',
            'caption' => $this->view->_('CATALOG_CATEGORIES'),
            'multiple' => 'multiple',
            'children' => $this->meta['CONTENT']['cats'],
            'check' => function ($Field) use ($t) {
                if (!isset($_POST['meta_cats']) || !$_POST['meta_cats']) {
                    return [
                        'name' => 'MISSED',
                        'value' => $Field->name,
                        'description' => $t->view->_('ERR_NO_CATEGORIES')
                    ];
                }
            },
            'import' => function ($Field) {
                return $Field->Form->Item->catalog_cats_ids;
            },
        ]);
        return $tab;
    }
}
