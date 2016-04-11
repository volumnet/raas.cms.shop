<?php
namespace RAAS\CMS;
use \RAAS\Attachment;

class Catalog_Cache
{
    protected $_data = array();
    protected $_mtype = array();
    protected $_textKeys = array(
        'urn', 'name', 'description', 'meta_title', 'meta_description', 'meta_keywords', 
        'h1', 'menu_name', 'breadcrumbs_name', 'article'
    );

    public function __get($var)
    {
        switch ($var) {
            case 'data': case 'mtype':
                return $this->{'_' . $var};
                break;
        }
    }


    public function __construct(Material_Type $MType)
    {
        $this->_mtype = $MType;
    }


    public function getCache()
    {
        $t = $this;
        $st = microtime(true);
        $this->clear();

        $SQL_what = array();

        // Категория непосредственно с товарами
        $SQL_from = $SQL_where = array();
        $sort = $order = "";
        if (!$this->_mtype->global_type) {
            $SQL_from['tMPA'] = " JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id ";
        }
        $SQL_where[] = " tM.vis ";
        $types = array_merge(array((int)$this->_mtype->id), (array)$this->_mtype->all_children_ids);
        $SQL_where[] = " tM.pid IN (" . implode(", ", $types) . ") ";

        /*** FILTERING ***/
        $SQL_array = array();
        
        // Набор полей
        if (!$this->_mtype->global_type) {
            $SQL_what['pages_ids'] = "GROUP_CONCAT(DISTINCT tMPA.pid SEPARATOR '@@@') AS pages_ids";
        }
        foreach ($this->_mtype->fields as $row) {
            $tmp_field = $this->getField($row->id, 'var' . $row->id, $SQL_from);
            $SQL_what[$row->urn] = "GROUP_CONCAT(" . $tmp_field . " SEPARATOR '@@@') AS `" . Field::_SQL()->real_escape_string($row->urn) . "`";
        }
        
        /*** QUERY ***/
        Material::_SQL()->query("SET SQL_BIG_SELECTS=1");
        $SQL_query = "SELECT SQL_CALC_FOUND_ROWS tM.* " . ($SQL_what ? ", " . implode(", ", $SQL_what) : "") 
                   . "  FROM " . Material::_tablename() . " AS tM " . implode(" ", $SQL_from)
                   . ($SQL_where ? " WHERE " . implode(" AND ", $SQL_where) : "")
                   . " GROUP BY tM.id ORDER BY NOT tM.priority, tM.priority ASC ";
        // echo $SQL_query; exit;
        $SQL_result = Material::_SQL()->get($SQL_query);
        // print_r ($SQL_result); exit;
        $SQL_result = array_map(function($x) use ($t) {
            $y = $x;
            foreach ($y as $key => $val) {
                if (stristr($val, '@@@')) {
                    $y[$key] = explode('@@@', $val);
                    $y[$key] = array_unique($y[$key]);
                    $y[$key] = array_values($y[$key]);
                    if (count($y[$key]) == 1) {
                        $y[$key] = array_shift($y[$key]);
                    }
                }
                $y[$key] = $t->checkDeepNumeric($y[$key], $key);
            }
            return $y;
        }, $SQL_result);

        // print_r ($SQL_result); exit;

        $this->_data = $SQL_result;
        $this->save();
    }


    protected function getField($field, $as, array &$SQL_from) {
        $sort = '';
        if (in_array($field, array('name', 'urn', 'description', 'post_date', 'modify_date'))) {
            $sort = "tM." . $field;
        } elseif (is_numeric($field)) {
            if (!isset($SQL_from[$as]) || !$SQL_from[$as]) {
                $SQL_from[$as] = " LEFT JOIN " . Field::data_table . " AS `" . $as . "` ON `" . $as . "`.pid = tM.id AND `" . $as . "`.fid = " . (int)$field;
            }
            $f = new Material_Field((int)$field);
            if (in_array($f->urn, array('price', 'price_old'))) {
                $sort = "CAST(" . $as . ".value AS UNSIGNED)";
            } else {
                $sort = $as . ".value";
            }
            
        }
        return $sort;
    }


    public function getFilename()
    {
        return Package::i()->cacheDir . '/system/raas_cache_materials' . $this->_mtype->id . '.php';
    }


    public function load()
    {
        if (is_file($this->getFilename())) {
            $this->_data = include $this->getFilename();
            return true;
        }
        return false;
    }


    public function save()
    {
        return (bool)file_put_contents($this->getFilename(), '<' . '?php return ' . var_export((array)$this->_data, true) . ';');
    }


    public function clear()
    {
        $this->_data = array();
    }


    public function checkDeepNumeric($data, $key = null)
    {
        if (is_array($data)) {
            $temp = array();
            foreach ($data as $key => $val) {
                $temp[$key] = $this->checkDeepNumeric($val, $key);
            }
            return $temp;
        } else {
            $data = trim($data);
            if (is_numeric($data) && (!$key || !in_array($key, $this->_textKeys))) {
                return (float)$data;
            }
            return $data;
        }
    }
}