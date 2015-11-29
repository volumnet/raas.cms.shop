<?php
namespace RAAS\CMS\Shop;

use \RAAS\CMS\Page;
use \RAAS\CMS\Package;

class PriceLoader extends \SOME\SOME
{
    const DELETE_PREVIOUS_MATERIALS_NONE = 0;
    const DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY = 1;
    const DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES = 2;

    protected static $tablename = 'cms_shop_priceloaders';
    protected static $defaultOrderBy = "name";
    protected static $references = array(
        'Material_Type' => array('FK' => 'mtype', 'classname' => 'RAAS\\CMS\\Material_Type', 'cascade' => true),
        'Unique_Field' => array('FK' => 'ufid', 'classname' => 'RAAS\\CMS\\Material_Field', 'cascade' => false),
        'Interface' => array('FK' => 'interface_id', 'classname' => 'RAAS\\CMS\\Snippet', 'cascade' => true),
        'Page' => array('FK' => 'cat_id', 'classname' => 'RAAS\\CMS\\Page', 'cascade' => false),
    );
    protected static $children = array(
        'columns' => array('classname' => 'RAAS\\CMS\\Shop\\PriceLoader_Column', 'FK' => 'pid')
    );
    
    public function commit()
    {
        if (!trim($this->name) && trim($this->Material_Type->name)) {
            $this->name = $this->Material_Type->name;
        }
        if (!$this->urn && $this->name) {
            $this->urn = $this->name;
        }
        Package::i()->getUniqueURN($this);
        parent::commit();
    }


    public function upload(array $file = null, Page $Page = null, $test = false, $clear = false, $rows = null, $cols = null)
    {
        $Loader = $this;
        if ($Page === null) {
            $Page = $Loader->Page;
        }
        if ($rows === null) {
            $rows = $Loader->rows;
        }
        if ($cols === null) {
            $cols = $Loader->cols;
        }
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }


    public function download(Page $Page, $rows = 0, $cols = 0, $type = null, $encoding = null)
    {
        $Loader = $this;
        if ($Page === null) {
            $Page = $Loader->Page;
        }
        if ($rows === null) {
            $rows = $Loader->rows;
        }
        if ($cols === null) {
            $cols = $Loader->cols;
        }
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }

    
    public static function importByURN($urn = '')
    {
        $SQL_query = "SELECT * FROM " . self::_tablename() . " WHERE urn = ?";
        if ($SQL_result = self::$SQL->getline(array($SQL_query, $urn))) {
            return new self($SQL_result);
        }
        return null;
    }

}