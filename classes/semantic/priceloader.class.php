<?php
namespace RAAS\CMS\Shop;

class PriceLoader extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_priceloaders';
    protected static $defaultOrderBy = "name";
    protected static $references = array(
        'Material_Type' => array('FK' => 'mtype', 'classname' => 'RAAS\\CMS\\Material_Type', 'cascade' => true),
        'Unique_Field' => array('FK' => 'ufid', 'classname' => 'RAAS\\CMS\\Material_Field', 'cascade' => false),
        'Interface' => array('FK' => 'interface_id', 'classname' => 'RAAS\\CMS\\Snippet', 'cascade' => true),
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
}