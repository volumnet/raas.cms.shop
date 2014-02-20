<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Block;

class Block_Cart extends Block
{
    protected static $tablename2 = 'cms_shop_blocks_cart';

    protected static $references = array(
        'author' => array('FK' => 'author_id', 'classname' => 'RAAS\\User', 'cascade' => false),
        'editor' => array('FK' => 'editor_id', 'classname' => 'RAAS\\User', 'cascade' => false),
        'Cart_Type' => array('FK' => 'cart_type', 'classname' => 'RAAS\\CMS\\Shop\\Cart_Type', 'cascade' => true),
    );
    
    public function __construct($import_data = null)
    {
        parent::__construct($import_data);
    }
    
    
    public function commit()
    {
        if (!$this->name && $this->Cart_Type->id) {
            $this->name = $this->Cart_Type->name;
        }
        parent::commit();
    }


    protected function getAddData()
    {
        return array(
            'id' => (int)$this->id, 
            'cart_type' => (int)$this->cart_type,
        );
    }
}
