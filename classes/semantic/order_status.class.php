<?php
namespace RAAS\CMS\Shop;

use \RAAS\CMS\Package;

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
        Package::i()->getUniqueURN($this);
        parent::commit();
    }
}