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
        'EPay_Interface' => array('FK' => 'epay_interface_id', 'classname' => 'RAAS\\CMS\\Snippet', 'cascade' => false),
    );

    public function __construct($import_data = null)
    {
        parent::__construct($import_data);
    }


    public function commit()
    {
        if (!$this->name) {
            $this->name = Module::i()->view->_('CART');
        }
        parent::commit();
    }


    public function getAddData()
    {
        return array(
            'id' => (int)$this->id,
            'cart_type' => (int)$this->cart_type,
            'epay_interface_id' => (int)$this->epay_interface_id,
            'epay_login' => trim($this->epay_login),
            'epay_pass1' => trim($this->epay_pass1),
            'epay_pass2' => trim($this->epay_pass2),
            'epay_test' => (int)$this->epay_test,
            'epay_currency' => trim($this->epay_currency),
        );
    }
}
