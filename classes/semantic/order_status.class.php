<?php
namespace RAAS\CMS\Shop;

class Order_Status extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_orders_statuses';
    protected static $defaultOrderBy = "priority";
    protected static $aiPriority = true;

    public function commit()
    {
        if (!$this->urn && $this->name) {
            $this->urn = \SOME\Text::beautify($this->name);
        }
        while ((int)self::$SQL->getvalue(array("SELECT COUNT(*) FROM " . self::_tablename() . " WHERE urn = ? AND id != ?", $this->urn, (int)$this->id))) {
            $this->urn = '_' . $this->urn . '_';
        }
        parent::commit();
    }
}