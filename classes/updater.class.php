<?php
namespace RAAS\CMS\Shop;

use \RAAS\IContext;
use \RAAS\CMS\Snippet;
use \RAAS\CMS\Snippet_Folder;

class Updater extends \RAAS\Updater
{
    public function preInstall()
    {
        $this->update20150511();
        $this->update20151129();
        $this->update20160119();
    }


    public function postInstall()
    {
        $w = new Webmaster();
        $s = Snippet::importByURN('__raas_shop_cart_interface');
        $w->checkStdInterfaces();
        if (!$s || !$s->id) {
            $w->createIShop();
        }
    }


    public function update20150511()
    {
        if (in_array(\SOME\SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) && !in_array('epay_login', $this->columns(\SOME\SOME::_dbprefix() . "cms_shop_blocks_cart"))) {
            $SQL_query = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_blocks_cart
                            ADD epay_login VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay login',
                            ADD epay_pass1 VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay pass1',
                            ADD epay_pass2 VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay pass2'";
            $this->SQL->query($SQL_query);
        }
        if (in_array(\SOME\SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) && !in_array('epay_test', $this->columns(\SOME\SOME::_dbprefix() . "cms_shop_blocks_cart"))) {
            $SQL_query = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_blocks_cart ADD epay_test TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'E-pay test mode'";
            $this->SQL->query($SQL_query);
        }
        if (in_array(\SOME\SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) && !in_array('epay_currency', $this->columns(\SOME\SOME::_dbprefix() . "cms_shop_blocks_cart"))) {
            $SQL_query = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_blocks_cart ADD epay_currency VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Currency'";
            $this->SQL->query($SQL_query);
        }
    }


    public function update20151129()
    {
        if (in_array(\SOME\SOME::_dbprefix() . "cms_forms", $this->tables) && in_array('urn', $this->columns(\SOME\SOME::_dbprefix() . "cms_forms"))) {
            $SQL_query = "UPDATE " . \SOME\SOME::_dbprefix() . "cms_forms SET urn = 'order' WHERE (urn = '') AND (name = 'Форма заказа' OR name = 'Order form')";
            $this->SQL->query($SQL_query);
        }
        if (in_array(\SOME\SOME::_dbprefix() . "cms_shop_priceloaders", $this->tables) && !in_array('urn', $this->columns(\SOME\SOME::_dbprefix() . "cms_shop_priceloaders"))) {
            $SQL_query = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_priceloaders
                            ADD urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN' AFTER name,
                            ADD INDEX (urn)";
            $this->SQL->query($SQL_query);
            $SQL_query = "UPDATE " . \SOME\SOME::_dbprefix() . "cms_shop_priceloaders SET urn = 'default' WHERE (urn = '') AND (name = 'Стандартный загрузчик прайсов' OR name = 'Default price loader')";
            $this->SQL->query($SQL_query);
        }
        if (in_array(\SOME\SOME::_dbprefix() . "cms_shop_imageloaders", $this->tables) && !in_array('urn', $this->columns(\SOME\SOME::_dbprefix() . "cms_shop_imageloaders"))) {
            $SQL_query = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_imageloaders
                            ADD urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN' AFTER name,
                            ADD INDEX (urn)";
            $this->SQL->query($SQL_query);
            $SQL_query = "UPDATE " . \SOME\SOME::_dbprefix() . "cms_shop_imageloaders SET urn = 'default' WHERE (urn = '') AND (name = 'Стандартный загрузчик изображений' OR name = 'Default image loader')";
            $this->SQL->query($SQL_query);
        }
    }


    public function update20160119()
    {
        if (in_array(\SOME\SOME::_dbprefix() . "cms_shop_orders_goods", $this->tables) && !in_array('priority', $this->columns(\SOME\SOME::_dbprefix() . "cms_shop_orders_goods"))) {
            $SQL_query = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_orders_goods
                            ADD priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'priority',
                            ADD INDEX (priority)";
            $this->SQL->query($SQL_query);
        }
    }
}
