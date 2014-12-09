<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Block;
use \RAAS\CMS\Material_Type;

class Block_YML extends Block
{
    protected static $tablename2 = 'cms_shop_blocks_yml';

    protected static $links = array(
        'pages' => array('tablename' => 'cms_blocks_pages_assoc', 'field_from' => 'block_id', 'field_to' => 'page_id', 'classname' => 'RAAS\\CMS\\Page'),
        'catalog_cats' => array('tablename' => 'cms_shop_blocks_yml_pages_assoc', 'field_from' => 'id', 'field_to' => 'page_id', 'classname' => 'RAAS\\CMS\\Page')
    );

    protected static $cognizableVars = array('Location', 'currencies', 'types');

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

    /**
     * @todo
     */
    protected function _types()
    {
        $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_material_types_assoc WHERE id = " . (int)$this->id;
        $SQL_result = (array)static::$SQL->get($SQL_query);
        $Set = array();
        foreach ($SQL_result as $row) {
            $mtype = new Material_Type((int)$row['mtype']);
            if ((int)$mtype->id) {
                $mtype->ymlType = $row['type'];
                $SQL_query = "SELECT * FROM " . static::$dbprefix . "cms_shop_blocks_yml_fields WHERE id = " . (int)$this->id . " AND mtype = " . (int)$mtype->id;
                $SQL_result2 = (array)static::$SQL->get($SQL_query);
                $temp = array();
                foreach ($SQL_result2 as $row2) {
                    $row3 = array('field_name' => trim($row2['field_name']));
                    $f = new Material_Field((int)$row['field_id']);
                    if ((int)$f->id) {
                        $row3['field'] = $f;
                    }
                    unset($f);

                    if (isset($temp[$row2['field_name']])) {
                        $temp[$row2['field_name']] = (array)$temp[$row2['field_name']];
                    } else {
                        $temp[$row2['field_name']] = $row3;
                    }
                }
                $Set[(int)$mtype->id] = $mtype;
            }
        }
    }
}
