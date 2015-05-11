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
    }


    public function postInstall()
    {
        $w = new Webmaster();
        $s = Snippet::importByURN('__raas_shop_cart_interface');
        $w->checkStdSnippets();
        if (!$s || !$s->id) {
            $w->createWidgets();
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
}