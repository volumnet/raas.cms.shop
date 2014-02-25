<?php
namespace RAAS\CMS\Shop;

class Cart_Type extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_cart_types';
    protected static $defaultOrderBy = "name";
    protected static $cognizableVars = array('unreadOrders');
    protected static $references = array(
        'Form' => array('FK' => 'form_id', 'classname' => 'RAAS\\CMS\\Form', 'cascade' => false),
    );
    protected static $links = array(
        'material_types' => array('tablename' => 'cms_shop_cart_types_material_types_assoc', 'field_from' => 'ctype', 'field_to' => 'mtype', 'classname' => 'RAAS\\CMS\\Material_Type')
    );
    
    public function commit()
    {
        if (!trim($this->name) && trim($this->Form->name)) {
            $this->name = $this->Form->name;
        }
        if (!$this->urn && $this->name) {
            $this->urn = \SOME\Text::beautify($this->name);
        }
        while ((int)self::$SQL->getvalue(array("SELECT COUNT(*) FROM " . self::_tablename() . " WHERE urn = ? AND id != ?", $this->urn, (int)$this->id))) {
            $this->urn = '_' . $this->urn . '_';
        }
        parent::commit();
        $SQL_query = "DELETE FROM " . self::_dbprefix() . self::$links['material_types']['tablename'] 
                   . " WHERE " . self::$links['material_types']['field_from'] . " = " . (int)$this->id;
        self::$SQL->query($SQL_query);
        $arr = array();
        foreach ($this->mtypes as $row) {
            $arr[] = array(
                self::$links['material_types']['field_from'] => (int)$this->id,
                self::$links['material_types']['field_to'] => (int)$row['id'],
                'price_id' => (int)$row['price_id'],
                'price_callback' => !(int)$row['price_id'] ? $row['price_callback'] : ''
            );
        }
        if ($arr) {
            self::$SQL->add(self::$links['material_types']['tablename'], $arr);
        }
    }


    protected function _unreadOrders()
    {
        $SQL_query = "SELECT COUNT(*) FROM " . Order::_tablename() . " WHERE pid = " . (int)$this->id . " AND NOT vis";
        return self::$SQL->getvalue($SQL_query);
    }
}