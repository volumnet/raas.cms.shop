<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Page;

class PriceLoader extends \SOME\SOME
{
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
        parent::commit();
    }


    public function upload(array $file = null, Page $Page = null, $test = false, $clear = false, $rows = 0, $cols = 0)
    {
        $Loader = $this;
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }


    public function download(Page $Page, $rows = 0, $cols = 0, $type = null, $encoding = null)
    {
        $Loader = $this;
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }
}