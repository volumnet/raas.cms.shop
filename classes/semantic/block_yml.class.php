<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Block;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Material_Field;

class Block_YML extends Block
{
    protected static $tablename2 = 'cms_shop_blocks_yml';

    protected static $links = array(
        'pages' => array('tablename' => 'cms_blocks_pages_assoc', 'field_from' => 'block_id', 'field_to' => 'page_id', 'classname' => 'RAAS\\CMS\\Page'),
        'catalog_cats' => array('tablename' => 'cms_shop_blocks_yml_pages_assoc', 'field_from' => 'id', 'field_to' => 'page_id', 'classname' => 'RAAS\\CMS\\Page')
    );

    protected static $cognizableVars = array('Location', 'currencies', 'types');

    public static $defaultFields = array(
        array(/*'buyurl', */'price', /*'wprice', */'currencyId', /*'xCategory', 'market_category',*/ 'picture', 'store', 'pickup', 'delivery', /*'deliveryIncluded', */'local_delivery_cost', /*'orderingTime'*/),
        array(/*'aliases', 'additional', */'description', 'sales_notes', /*'promo', */'manufacturer_warranty', 'seller_warranty', 'country_of_origin', 'downloadable', 'adult', 'age', 'barcode', 'cpa', /*'fee', */'rec', 'expiry', 'weight', 'dimensions', 'param', /*'related_offer'*/),
    );

    public static $ymlTypes = array(
        '' => array('name', 'vendor', 'vendorCode'),
        'vendor.model' => array('typePrefix', 'vendor', 'vendorCode', 'model'/*, 'provider', 'tarifplan'*/),
        'book' => array('author', 'name', 'publisher', 'series', 'year', 'ISBN', 'volume', 'part', 'language', 'binding', 'page_extent', 'table_of_contents'),
        'audiobook' => array(
            'author', 'name', 'publisher', 'series', 'year', 'ISBN', 'volume', 'part', 'language', 'table_of_contents', 'performed_by', 'performance_type', 
            'storage', 'format', 'recording_length'
        ),
        'artist.title' => array('artist', 'title', 'year', 'media', 'starring', 'director', 'originalName', 'country'),
        'tour' => array(
            'worldRegion', 'country', 'region', 'days', 'dataTour', 'name', 'hotel_stars', 'room', 'meal', 'included', 'transport'/*, 'price_min', 'price_max', 
            'options'*/
        ),
        'event-ticket' => array('name', 'place', 'hall', 'hall_plan', 'hall_part', 'date', 'is_premiere', 'is_kids'),
    );

    public static $ymlFields = array(
        'additional' => array('multiple' => true, ),
        'adult' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'age' => array(
            'type' => 'number', 
            'min' => 0, 
            'callback' => '$ages = array(0, 6, 12, 16, 18); foreach ($ages as $age) { if ($age >= (int)$x) { return $age; } } return 18;',
        ),
        'aliases' => array(),
        'artist' => array(),
        'author' => array(),
        'available' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'barcode' => array('multiple' => true, ),
        'bid' => array('type' => 'number', 'min' => 0),
        'binding' => array(),
        'buyurl' => array('type' => 'url'),
        'cbid' => array('type' => 'number', 'min' => 0),
        'country' => array(),
        'country_of_origin' => array(),
        'cpa' => array('type' => 'checkbox'),
        'currencyId' => array('required' => true, ),
        'dataTour' => array(
            'type' => 'date', 
            'multiple' => true, 
            'callback' => 'return date("d/m/Y", strtotime($x));'
        ),
        'date' => array('required' => true, 'type' => 'datetime-local', 'callback' => 'return date("YYYY-MM-DDThh:mm", strtolower($x));', ),
        'days' => array('required' => true, 'type' => 'number', 'min' => 0),
        'delivery' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'deliveryIncluded' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'description' => array('default' => 'description'),
        'dimensions' => array('pattern' => '(\\d|\\.)\\/(\\d|\\.)\\/(\\d|\\.)'),
        'director' => array(),
        'downloadable' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'expiry' => array('callback' => 'return "P" . (int)$x . "Y";'),
        'fee' => array('type' => 'number', 'step' => 0.01, 'min' => 0),
        'format' => array(),
        // 'group_id' => array('type' => 'number', 'min' => 0),
        'hall' => array('required' => true),
        'hall plan' => array('type' => 'url'),
        'hall_part' => array(),
        'hotel_stars' => array('type' => 'number', 'min' => 0, 'callback' => 'return (int)$x . str_repeat("*", (int)$x));'),
        'included' => array('required' => true),
        'is_kids' => array('type' => 'checkbox'),
        'is_premiere' => array('type' => 'checkbox'),
        'ISBN' => array(),
        'language' => array(),
        'local_delivery_cost' => array('type' => 'number', 'min' => 0, 'step' => 0.01),
        'manufacturer_warranty' => array(
            'callback' => 'if ((int)$x > 0) { return "P" . (int)$x; } elseif (!in_array(trim(mb_strtolower($x)), array("0", "no", "none", "false", "нет"))) { return true; } return false;'
        ),
        'market_category' => array('type' => 'number', 'min' => 0),
        'meal' => array(),
        'media' => array(),
        'model' => array(),
        'name' => array('required' => true, 'default' => 'name'),
        'options' => array(),
        'orderingTime' => array('type' => 'datetime-local'),
        'originalName' => array(),
        'page_extent' => array('type' => 'number', 'min' => 0),
        'part' => array('type' => 'number', 'min' => 0),
        'performance_type' => array(),
        'performed_by' => array(),
        'pickup' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'picture' => array('type' => 'image', 'multiple' => true, 'callback' => 'return ($x instanceof \\RAAS\\Attachment) ? "http://" . $_SERVER["HTTP_HOST"] . "/" . $x->fileURL : $x;'),
        'place' => array('required' => true),
        'price' => array('type' => 'number', 'min' => 0, 'step' => 0.01, 'required' => true, ),
        'price_max' => array('type' => 'number', 'min' => 0, 'step' => 0.01),
        'price_min' => array('type' => 'number', 'min' => 0, 'step' => 0.01),
        'promo' => array(),
        'provider' => array(),
        'publisher' => array(),
        'rec' => array('type' => 'material', 'callback' => 'return implode(",", array_map(function($y) { return (int)$y->id }, (array)$x));'),
        'recording_length' => array(),
        'region' => array(),
        'related_offer' => array('type' => 'material', 'callback' => 'return $x->id;', 'multiple' => true, ),
        'room' => array(),
        'sales_notes' => array(),
        'seller_warranty' => array(
            'callback' => 'if ((int)$x > 0) { return "P" . (int)$x; } elseif (!in_array(trim(mb_strtolower($x)), array("0", "no", "none", "false", "нет"))) { return true; } return false;'
        ),
        'series' => array(),
        'starring' => array(),
        'storage' => array(),
        'store' => array('type' => 'checkbox', 'callback' => 'return (int)$x ? "true" : "false";'),
        'table_of_contents' => array('type' => 'textarea'),
        'tarifplan' => array(),
        'title' => array('required' => true, 'default' => 'name'),
        'transport' => array('required' => true),
        'typePrefix' => array(),
        'vendor' => array(),
        'vendorCode' => array(),
        'volume' => array('type' => 'number', 'min' => 0),
        'weight' => array('type' => 'number', 'min' => 0, 'step' => 0.001),
        'worldRegion' => array(),
        'wprice' => array('type' => 'number', 'min' => 0, 'step' => 0.01),
        'xCategory' => array('type' => 'number', 'min' => 0),
        'year' => array('type' => 'number', 'min' => 1970),
    );

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
            static::$SQL->query("DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_pages_assoc WHERE id = " . (int)$this->id);
            $arr = array_map(function($x) use ($t) { return array('id' => (int)$t->id, 'page_id' => (int)$x); }, $this->meta_cats);
            static::$SQL->add(static::$dbprefix . "cms_shop_blocks_yml_pages_assoc", $arr);
        }
        if ($this->meta_currencies) {
            static::$SQL->query("DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_currencies WHERE id = " . (int)$this->id);
            $arr = array_map(function($x) use ($t) { return array_merge(array('id' => (int)$t->id), (array)$x); }, $this->meta_currencies);
            static::$SQL->add(static::$dbprefix . "cms_shop_blocks_yml_currencies", $arr);
        }
    }


    public function addType(Material_Type $MType, $type = '', array $fields = array(), array $params = array(), array $ignored_fields = array(), $param_exceptions = false, $params_callback = '')
    {
        $this->removeType($MType);
        $arr = array(
            'id' => $this->id, 
            'mtype' => (int)$MType->id, 
            'type' => trim($type), 
            'param_exceptions' => (int)$param_exceptions, 
            'params_callback' => trim($params_callback)
        );
        static::$SQL->add(static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc", $arr);

        $arr = array();
        foreach ($fields as $key => $row) {
            $row2 = array('id' => $this->id, 'mtype' => (int)$MType->id, 'field_name' => $key);
            foreach (array('field_id', 'field_callback', 'field_static_value') as $k) {
                if (isset($row[$k])) {
                    $row2[$k] = trim($row[$k]);
                }
            }
            $arr[] = $row2;
        }
        static::$SQL->add(static::$dbprefix . "cms_shop_blocks_yml_fields", $arr);

        $arr = array();
        foreach ($params as $row) {
            $row2 = array('id' => $this->id, 'mtype' => (int)$MType->id);
            foreach (array('param_name', 'field_id', 'field_callback', 'param_static_value') as $k) {
                if (isset($row[$k])) {
                    $row2[$k] = trim($row[$k]);
                }
            }
            $arr[] = $row2;
        }
        static::$SQL->add(static::$dbprefix . "cms_shop_blocks_yml_params", $arr);

        if ($ignored_fields) {
            $arr = array();
            foreach ($ignored_fields as $val) {
                $row2 = array('id' => $this->id, 'mtype' => (int)$MType->id, 'field_id' => trim($val));
                $arr[] = $row2;
            }
            static::$SQL->add(static::$dbprefix . "cms_shop_blocks_yml_ignored_fields", $arr);
        }
    }


    public function removeType(Material_Type $MType)
    {
        $SQL_query = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc 
                       WHERE id = " . (int)$this->id . " AND mtype = " . (int)$MType->id;
        static::$SQL->query($SQL_query);

        $SQL_query = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_fields WHERE id = " . (int)$this->id . " AND mtype = " . (int)$MType->id;
        static::$SQL->query($SQL_query);

        $SQL_query = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_params WHERE id = " . (int)$this->id . " AND mtype = " . (int)$MType->id;
        static::$SQL->query($SQL_query);

        $SQL_query = "DELETE FROM " . static::$dbprefix . "cms_shop_blocks_yml_ignored_fields WHERE id = " . (int)$this->id . " AND mtype = " . (int)$MType->id;
        static::$SQL->query($SQL_query);
    }


    protected function getAddData()
    {
        return array(
            'id' => (int)$this->id, 
            'shop_name' => (string)$this->shop_name,
            'company' => (string)$this->company,
            'agency' => (string)$this->agency,
            'email' => (string)$this->email,
            'cpa' => (int)(bool)$this->cpa,
            'default_currency' => (string)$this->default_currency,
            'local_delivery_cost' => $this->local_delivery_cost,
        );
    }


    protected function _currencies()
    {
        $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_currencies WHERE id = " . (int)$this->id;
        $SQL_result = (array)static::$SQL->get($SQL_query);
        $Set = array();
        foreach ($SQL_result as $row) {
            $Set[$row['currency_name']] = array('rate' => $row['currency_rate'], 'plus' => $row['currency_plus']);
        }
        return $Set;
    }

    protected function _types()
    {
        $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc WHERE id = " . (int)$this->id;
        $SQL_result = (array)static::$SQL->get($SQL_query);
        $Set = array();
        foreach ($SQL_result as $row) {
            $mtype = new Material_Type((int)$row['mtype']);
            if ((int)$mtype->id) {
                $mtarr = array();
                $mtarr['type'] = $row['type'];
                $mtarr['param_exceptions'] = (bool)(int)$row['param_exceptions'];
                $mtarr['params_callback'] = $row['params_callback'];

                $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_fields WHERE id = " . (int)$this->id . " AND mtype = " . (int)$mtype->id;
                $SQL_result2 = (array)static::$SQL->get($SQL_query);
                foreach ($SQL_result2 as $row2) {
                    $mfarr = array();
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
                    } elseif ($row2['field_static_value']) {
                        $mfarr['value'] = trim($row2['field_static_value']);
                    }
                    if ($row2['field_callback']) {
                        $mfarr['callback'] = trim($row2['field_callback']);
                    }
                    $mtarr['fields'][$row2['field_name']] = $mfarr;
                }
                unset($mfarr);

                $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_params WHERE id = " . (int)$this->id . " AND mtype = " . (int)$mtype->id;
                $SQL_result2 = (array)static::$SQL->get($SQL_query);
                foreach ($SQL_result2 as $row2) {
                    $mfarr = array('name' => trim($row2['param_name']));
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
                    } elseif ($row2['param_static_value']) {
                        $mfarr['value'] = trim($row2['param_static_value']);
                    }
                    if ($row2['field_callback']) {
                        $mfarr['callback'] = trim($row2['field_callback']);
                    }
                    $mtarr['params'][] = $mfarr;
                }
                unset($mfarr);

                $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_ignored_fields WHERE id = " . (int)$this->id . " AND mtype = " . (int)$mtype->id;
                $SQL_result2 = (array)static::$SQL->get($SQL_query);
                foreach ($SQL_result2 as $row2) {
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
}
