<?php
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;

class Block_YML extends Block
{
    protected static $tablename2 = 'cms_shop_blocks_yml';

    protected static $links = [
        'pages' => [
            'tablename' => 'cms_blocks_pages_assoc',
            'field_from' => 'block_id',
            'field_to' => 'page_id',
            'classname' => Page::class,
        ],
        'catalog_cats' => [
            'tablename' => 'cms_shop_blocks_yml_pages_assoc',
            'field_from' => 'id',
            'field_to' => 'page_id',
            'classname' => Page::class,
        ]
    ];

    protected static $cognizableVars = [
        'Location',
        'currencies',
        'types'
    ];

    public static $defaultFields = [
        [
            'available',
            'bid',
            'cbid',
            'url',
            /*'buyurl', */
            'price',
            'oldprice',
            /*'wprice', */
            'currencyId',
            /*'xCategory',*/
            'categoryId',
            /*'market_category',*/
            'picture',
            'store',
            'pickup',
            'delivery',
            /*'deliveryIncluded', */
            'local_delivery_cost',
            /*'orderingTime'*/
        ],
        [
            /*'aliases',
            'additional', */
            'description',
            'sales_notes',
            /*'promo', */
            'manufacturer_warranty',
            'seller_warranty',
            'country_of_origin',
            'downloadable',
            'adult',
            'age',
            'barcode',
            'cpa',
            /*'fee', */
            'rec',
            'expiry',
            'weight',
            'dimensions',
            'param',
            /*'related_offer'*/
        ],
    ];

    /**
     * Типы YML-форматов
     * @var array <pre>array<
     *      string[] URN формата согласно документации Яндекс-Маркета => string[] Перечисление дополнительных полей согласно Яндекс-Маркета
     * ></pre>
     */
    public static $ymlTypes = [
        '' => [
            'name',
            'vendor',
            'vendorCode'
        ],
        'vendor.model' => [
            'typePrefix',
            'vendor',
            'vendorCode',
            'model'
            /*, 'provider', 'tarifplan'*/
        ],
        'book' => [
            'author',
            'name',
            'publisher',
            'series',
            'year',
            'ISBN',
            'volume',
            'part',
            'language',
            'binding',
            'page_extent',
            'table_of_contents'
        ],
        'audiobook' => [
            'author',
            'name',
            'publisher',
            'series',
            'year',
            'ISBN',
            'volume',
            'part',
            'language',
            'table_of_contents',
            'performed_by',
            'performance_type',
            'storage',
            'format',
            'recording_length'
        ],
        'artist.title' => [
            'artist',
            'title',
            'year',
            'media',
            'starring',
            'director',
            'originalName',
            'country'
        ],
        'tour' => [
            'worldRegion',
            'country',
            'region',
            'days',
            'dataTour',
            'name',
            'hotel_stars',
            'room',
            'meal',
            'included',
            'transport'
            /*, 'price_min',
            'price_max',
            'options'*/
        ],
        'event-ticket' => [
            'name',
            'place',
            'hall',
            'hall_plan',
            'hall_part',
            'date',
            'is_premiere',
            'is_kids'
        ],
    ];


    /**
     * Настройки YML-поля (значение по умолчанию)
     * @var array <pre>array<
     *     string[] URN поля в системе Яндекс.Маркет => array Параметры поля RAAS\Field
     * ></pre>
     */
    public static $ymlFields = [
        'additional' => [
            'multiple' => true,
        ],
        'adult' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";'
        ],
        'age' => [
            'type' => 'number',
            'min' => 0,
            'callback' => '$ages = [0, 6, 12, 16, 18];
foreach ($ages as $age) {
    if ($age >= (int)$x) {
        return $age;
    }
}
return 18;',
        ],
        'aliases' => [],
        'artist' => [],
        'author' => [],
        'available' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";',
            'default' => true
        ],
        'barcode' => [
            'multiple' => true,
        ],
        'bid' => [
            'type' => 'number',
            'min' => 0
        ],
        'binding' => [],
        'buyurl' => [
            'type' => 'url'
        ],
        'cbid' => [
            'type' => 'number',
            'min' => 0
        ],
        'country' => [],
        'country_of_origin' => [],
        'cpa' => [
            'type' => 'checkbox'
        ],
        'currencyId' => [
            'required' => true,
        ],
        'dataTour' => [
            'type' => 'date',
            'multiple' => true,
            'callback' => 'return date("d/m/Y", strtotime($x));'
        ],
        'date' => [
            'required' => true,
            'type' => 'datetime-local',
            'callback' => 'return date("YYYY-MM-DDThh:mm", strtolower($x));',
        ],
        'days' => [
            'required' => true,
            'type' => 'number',
            'min' => 0
        ],
        'delivery' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";'
        ],
        'deliveryIncluded' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";'
        ],
        'description' => [
            'default' => 'description'
        ],
        'dimensions' => [
            'pattern' => '(\\d|\\.)\\/(\\d|\\.)\\/(\\d|\\.)',
            'callback' => '$y = str_replace("x", "/", $x);
$y = str_replace(",", ".", $y);
$y = str_replace(" ", "", $y);
return $y;'
        ],
        'director' => [],
        'downloadable' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";'
        ],
        'expiry' => [
            'callback' => 'return "P" . (int)$x . "Y";'
        ],
        'fee' => [
            'type' => 'number',
            'step' => 0.01,
            'min' => 0
        ],
        'format' => [],
        // 'group_id' => [
        //      'type' => 'number',
        //      'min' => 0
        //  ],
        'hall' => [
            'required' => true
        ],
        'hall plan' => [
            'type' => 'url'
        ],
        'hall_part' => [],
        'hotel_stars' => [
            'type' => 'number',
            'min' => 0,
            'callback' => 'return (int)$x . str_repeat("*", (int)$x);'
        ],
        'included' => [
            'required' => true
        ],
        'is_kids' => [
            'type' => 'checkbox'
        ],
        'is_premiere' => [
            'type' => 'checkbox'
        ],
        'ISBN' => [],
        'language' => [],
        'local_delivery_cost' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.01
        ],
        'manufacturer_warranty' => [
            'callback' => 'if ((int)$x > 0) {
    return "P" . (int)$x;
} elseif (!in_array(
    trim(mb_strtolower($x)),
    ["0", "no", "none", "false", "нет"]
)) {
    return true;
}
return false;'
        ],
        'market_category' => [
            'type' => 'number',
            'min' => 0
        ],
        'meal' => [],
        'media' => [],
        'model' => [],
        'name' => [
            'required' => true,
            'default' => 'name'
        ],
        'oldprice' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.01
        ],
        'options' => [],
        'orderingTime' => [
            'type' => 'datetime-local',
        ],
        'originalName' => [],
        'page_extent' => [
            'type' => 'number',
            'min' => 0
        ],
        'part' => [
            'type' => 'number',
            'min' => 0
        ],
        'performance_type' => [],
        'performed_by' => [],
        'pickup' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";'
        ],
        'picture' => [
            'type' => 'image',
            'multiple' => true,
            'callback' => 'if ($x instanceof \\RAAS\\Attachment) ?
    return "http" . ($_SERVER["HTTPS"] == "on" ? "s" : "") . "://" .
           $_SERVER["HTTP_HOST"] . "/" . $x->fileURL;
} else {
    return $x;
}'
        ],
        'place' => [
            'required' => true
        ],
        'price' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.01,
            'required' => true,
        ],
        'price_max' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.01,
        ],
        'price_min' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.01,
        ],
        'promo' => [],
        'provider' => [],
        'publisher' => [],
        'rec' => [
            'type' => 'material',
            'callback' => 'return implode(",", array_map(function ($y) {
    return (int)$y->id;
}, (array)$x));'
        ],
        'recording_length' => [],
        'region' => [],
        'related_offer' => [
            'type' => 'material',
            'callback' => 'return $x->id;',
            'multiple' => true,
        ],
        'room' => [],
        'sales_notes' => [],
        'seller_warranty' => [
            'callback' => 'if ((int)$x > 0) {
    return "P" . (int)$x;
} elseif (!in_array(
    trim(mb_strtolower($x)),
    ["0", "no", "none", "false", "нет"]
)) {
    return true;
}
return false;'
        ],
        'series' => [],
        'starring' => [],
        'storage' => [],
        'store' => [
            'type' => 'checkbox',
            'callback' => 'return (int)$x ? "true" : "false";'
        ],
        'table_of_contents' => [
            'type' => 'textarea'
        ],
        'tarifplan' => [],
        'title' => [
            'required' => true,
            'default' => 'name'
        ],
        'transport' => [
            'required' => true
        ],
        'typePrefix' => [],
        'vendor' => [],
        'vendorCode' => [],
        'volume' => [
            'type' => 'number',
            'min' => 0
        ],
        'weight' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.001
        ],
        'worldRegion' => [],
        'wprice' => [
            'type' => 'number',
            'min' => 0,
            'step' => 0.01
        ],
        'xCategory' => [
            'type' => 'number',
            'min' => 0
        ],
        'year' => [
            'type' => 'number',
            'min' => 1970
        ],
    ];

    public function __construct($import_data = null)
    {
        parent::__construct($import_data);
    }


    public function commit()
    {
        if (!$this->name) {
            $this->name = Module::i()->view->_('YANDEX_MARKET');
        }
        $t = $this;
        parent::commit();
        if ($this->meta_cats) {
            $sqlQuery = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_pages_assoc
                          WHERE id = " . (int)$this->id;
            static::$SQL->query($sqlQuery);
            $arr = array_map(
                function ($x) use ($t) {
                    return [
                        'id' => (int)$t->id,
                        'page_id' => (int)$x
                    ];
                },
                $this->meta_cats
            );
            static::$SQL->add(
                static::$dbprefix . "cms_shop_blocks_yml_pages_assoc",
                $arr
            );
        }
        if ($this->meta_currencies) {
            $sqlQuery = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_currencies
                          WHERE id = " . (int)$this->id;
            static::$SQL->query($sqlQuery);
            $arr = array_map(
                function ($x) use ($t) {
                    return array_merge(['id' => (int)$t->id], (array)$x);
                },
                $this->meta_currencies
            );
            static::$SQL->add(
                static::$dbprefix . "cms_shop_blocks_yml_currencies",
                $arr
            );
        }
    }


    public function addType(
        Material_Type $MType,
        $type = '',
        array $fields = [],
        array $params = [],
        array $ignored_fields = [],
        $param_exceptions = false,
        $params_callback = ''
    ) {
        $this->removeType($MType);
        $arr = [
            'id' => $this->id,
            'mtype' => (int)$MType->id,
            'type' => trim($type),
            'param_exceptions' => (int)$param_exceptions,
            'params_callback' => trim($params_callback)
        ];
        static::$SQL->add(
            static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc",
            $arr
        );

        $arr = [];
        foreach ($fields as $key => $row) {
            $row2 = [
                'id' => $this->id,
                'mtype' => (int)$MType->id,
                'field_name' => $key
            ];
            foreach ([
                'field_id',
                'field_callback',
                'field_static_value'
            ] as $k) {
                if (isset($row[$k])) {
                    $row2[$k] = trim($row[$k]);
                }
            }
            $arr[] = $row2;
        }
        static::$SQL->add(
            static::$dbprefix . "cms_shop_blocks_yml_fields",
            $arr
        );

        $arr = [];
        foreach ($params as $row) {
            $row2 = [
                'id' => $this->id,
                'mtype' => (int)$MType->id
            ];
            foreach ([
                'param_name',
                'field_id',
                'field_callback',
                'param_static_value',
                'param_unit'
            ] as $k) {
                if (isset($row[$k])) {
                    $row2[$k] = trim($row[$k]);
                }
            }
            $arr[] = $row2;
        }
        static::$SQL->add(
            static::$dbprefix . "cms_shop_blocks_yml_params",
            $arr
        );

        if ($ignored_fields) {
            $arr = [];
            foreach ($ignored_fields as $val) {
                $row2 = [
                    'id' => $this->id,
                    'mtype' => (int)$MType->id,
                    'field_id' => trim($val)
                ];
                $arr[] = $row2;
            }
            static::$SQL->add(
                static::$dbprefix . "cms_shop_blocks_yml_ignored_fields",
                $arr
            );
        }
    }


    public function removeType(Material_Type $MType)
    {
        $sqlQuery = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc
                      WHERE id = " . (int)$this->id
                  . "   AND mtype = " . (int)$MType->id;
        static::$SQL->query($sqlQuery);

        $sqlQuery = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_fields
                      WHERE id = " . (int)$this->id
                  . "   AND mtype = " . (int)$MType->id;
        static::$SQL->query($sqlQuery);

        $sqlQuery = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_params
                      WHERE id = " . (int)$this->id
                  . "   AND mtype = " . (int)$MType->id;
        static::$SQL->query($sqlQuery);

        $sqlQuery = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_ignored_fields
                      WHERE id = " . (int)$this->id
                  . "   AND mtype = " . (int)$MType->id;
        static::$SQL->query($sqlQuery);
    }


    public function getAddData()
    {
        return [
            'id' => (int)$this->id,
            'shop_name' => (string)$this->shop_name,
            'company' => (string)$this->company,
            'agency' => (string)$this->agency,
            'email' => (string)$this->email,
            'cpa' => (int)(bool)$this->cpa,
            'default_currency' => (string)$this->default_currency,
            'local_delivery_cost' => (int)$this->local_delivery_cost,
        ];
    }


    protected function _currencies()
    {
        $sqlQuery = "SELECT *
                       FROM " . static::$dbprefix . "cms_shop_blocks_yml_currencies
                      WHERE id = " . (int)$this->id;
        $sqlResult = (array)static::$SQL->get($sqlQuery);
        $Set = [];
        foreach ($sqlResult as $row) {
            $Set[$row['currency_name']] = [
                'rate' => $row['currency_rate'],
                'plus' => $row['currency_plus']
            ];
        }
        return $Set;
    }

    protected function _types()
    {
        $sqlQuery = "SELECT *
                       FROM " . static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc
                      WHERE id = " . (int)$this->id;
        $sqlResult = (array)static::$SQL->get($sqlQuery);
        $Set = [];
        foreach ($sqlResult as $row) {
            $mtype = new Material_Type((int)$row['mtype']);
            if ((int)$mtype->id) {
                $mtarr = [];
                $mtarr['type'] = $row['type'];
                $mtarr['param_exceptions'] = (bool)(int)$row['param_exceptions'];
                $mtarr['params_callback'] = $row['params_callback'];

                $sqlQuery = "SELECT *
                               FROM " . static::$dbprefix . "cms_shop_blocks_yml_fields
                              WHERE id = " . (int)$this->id
                          . "   AND mtype = " . (int)$mtype->id;
                $sqlResult2 = (array)static::$SQL->get($sqlQuery);
                foreach ($sqlResult2 as $row2) {
                    $mfarr = [];
                    if ($row2['field_id']) {
                        $f = null;
                        if (is_numeric($row2['field_id'])) {
                            $f = new Material_Field((int)$row2['field_id']);
                        }
                        if ($f && $f->id) {
                            $mfarr['field'] = $f;
                        } else {
                            $mfarr['field_id'] = trim($row2['field_id']);
                        }
                    }
                    if ($row2['field_static_value']) {
                        $mfarr['value'] = trim($row2['field_static_value']);
                    }
                    if ($row2['field_callback']) {
                        $mfarr['callback'] = trim($row2['field_callback']);
                    }
                    $mtarr['fields'][$row2['field_name']] = $mfarr;
                }
                unset($mfarr);

                $sqlQuery = "SELECT *
                               FROM " . static::$dbprefix . "cms_shop_blocks_yml_params
                              WHERE id = " . (int)$this->id
                          . "   AND mtype = " . (int)$mtype->id;
                $sqlResult2 = (array)static::$SQL->get($sqlQuery);
                foreach ($sqlResult2 as $row2) {
                    $mfarr = ['name' => trim($row2['param_name'])];
                    if ($row2['field_id']) {
                        $f = null;
                        if (is_numeric($row2['field_id'])) {
                            $f = new Material_Field((int)$row2['field_id']);
                        }
                        if ($f && $f->id) {
                            $mfarr['field'] = $f;
                        } else {
                            $mfarr['field_id'] = trim($row2['field_id']);
                        }
                    }
                    if ($row2['param_static_value']) {
                        $mfarr['value'] = trim($row2['param_static_value']);
                    }
                    if ($row2['field_callback']) {
                        $mfarr['callback'] = trim($row2['field_callback']);
                    }
                    if ($row2['param_unit']) {
                        $mfarr['unit'] = trim($row2['param_unit']);
                    }
                    $mtarr['params'][] = $mfarr;
                }
                unset($mfarr);

                $sqlQuery = "SELECT *
                               FROM " . static::$dbprefix . "cms_shop_blocks_yml_ignored_fields
                              WHERE id = " . (int)$this->id
                          . "   AND mtype = " . (int)$mtype->id;
                $sqlResult2 = (array)static::$SQL->get($sqlQuery);
                foreach ($sqlResult2 as $row2) {
                    $f = null;
                    if (is_numeric($row2['field_id'])) {
                        $f = new Material_Field((int)$row2['field_id']);
                    }
                    if ($f && $f->id) {
                        $mtarr['ignored'][] = $f;
                    } else {
                        $mtarr['ignored'][] = trim($row2['field_id']);
                    }
                }
                $mtype->settings = $mtarr;
                $Set[(int)$mtype->id] = $mtype;
            }
        }
        return $Set;
    }


    /**
     * Обработчик события сохранения страницы
     *
     * Добавляет в обработку новую страницу, если родительская там уже была
     * @param Page $page Сохраненная страница
     * @param mixed $data Дополнительные данные
     */
    public static function pageCommitEventListener(Page $page, $data)
    {
        if ($data['new']) {
            $sqlQuery = "SELECT id
                           FROM cms_shop_blocks_yml_pages_assoc
                          WHERE page_id = ?";
            $blocksIds = static::$SQL->getcol([$sqlQuery, (int)$page->pid]);
            $sqlArr = [];
            foreach ($blocksIds as $blockId) {
                $sqlArr[] = ['id' => $blockId, 'page_id' => $page->id];
            }
            if ($sqlArr) {
                static::$SQL->add('cms_shop_blocks_yml_pages_assoc', $sqlArr);
            }
        }
    }
}
