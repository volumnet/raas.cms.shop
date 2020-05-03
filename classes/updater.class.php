<?php
namespace RAAS\CMS\Shop;

use SOME\SOME;
use RAAS\IContext;
use RAAS\CMS\Snippet;
use RAAS\CMS\Snippet_Folder;

class Updater extends \RAAS\Updater
{
    public function preInstall()
    {
        if (version_compare(
            $this->Context->registryGet('baseVersion'),
            '4.2.28'
        ) < 0) {
            $this->update20150511();
            $this->update20151129();
            $this->update20160119();
            $this->update20180530();
            $this->update20190304();
            $this->update20200316();
        }
        if (version_compare(
            $this->Context->registryGet('baseVersion'),
            '4.2.32'
        ) < 0) {
            $this->update20200503();
        }
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
        if (in_array(SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) && !in_array('epay_login', $this->columns(SOME::_dbprefix() . "cms_shop_blocks_cart"))) {
            $SQL_query = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_blocks_cart
                            ADD epay_login VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay login',
                            ADD epay_pass1 VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay pass1',
                            ADD epay_pass2 VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay pass2'";
            $this->SQL->query($SQL_query);
        }
        if (in_array(SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) && !in_array('epay_test', $this->columns(SOME::_dbprefix() . "cms_shop_blocks_cart"))) {
            $SQL_query = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_blocks_cart ADD epay_test TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'E-pay test mode'";
            $this->SQL->query($SQL_query);
        }
        if (in_array(SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) && !in_array('epay_currency', $this->columns(SOME::_dbprefix() . "cms_shop_blocks_cart"))) {
            $SQL_query = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_blocks_cart ADD epay_currency VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Currency'";
            $this->SQL->query($SQL_query);
        }
    }


    public function update20151129()
    {
        if (in_array(SOME::_dbprefix() . "cms_forms", $this->tables) && in_array('urn', $this->columns(SOME::_dbprefix() . "cms_forms"))) {
            $SQL_query = "UPDATE " . SOME::_dbprefix() . "cms_forms SET urn = 'order' WHERE (urn = '') AND (name = 'Форма заказа' OR name = 'Order form')";
            $this->SQL->query($SQL_query);
        }
        if (in_array(SOME::_dbprefix() . "cms_shop_priceloaders", $this->tables) && !in_array('urn', $this->columns(SOME::_dbprefix() . "cms_shop_priceloaders"))) {
            $SQL_query = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_priceloaders
                            ADD urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN' AFTER name,
                            ADD INDEX (urn)";
            $this->SQL->query($SQL_query);
            $SQL_query = "UPDATE " . SOME::_dbprefix() . "cms_shop_priceloaders SET urn = 'default' WHERE (urn = '') AND (name = 'Стандартный загрузчик прайсов' OR name = 'Default price loader')";
            $this->SQL->query($SQL_query);
        }
        if (in_array(SOME::_dbprefix() . "cms_shop_imageloaders", $this->tables) && !in_array('urn', $this->columns(SOME::_dbprefix() . "cms_shop_imageloaders"))) {
            $SQL_query = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_imageloaders
                            ADD urn VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'URN' AFTER name,
                            ADD INDEX (urn)";
            $this->SQL->query($SQL_query);
            $SQL_query = "UPDATE " . SOME::_dbprefix() . "cms_shop_imageloaders SET urn = 'default' WHERE (urn = '') AND (name = 'Стандартный загрузчик изображений' OR name = 'Default image loader')";
            $this->SQL->query($SQL_query);
        }
    }


    public function update20160119()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_orders_goods", $this->tables) && !in_array('priority', $this->columns(SOME::_dbprefix() . "cms_shop_orders_goods"))) {
            $SQL_query = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders_goods
                            ADD priority INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'priority',
                            ADD INDEX (priority)";
            $this->SQL->query($SQL_query);
        }
    }


    public function update20180530()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_orders_goods", $this->tables)) {
            $sqlQuery = "ALTER TABLE " . \SOME\SOME::_dbprefix() . "cms_shop_orders_goods
                        CHANGE `meta` `meta` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Meta data'; ";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Добавляем уведомления о смене статусов заказов
     */
    public function update20190304()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_orders_statuses", $this->tables) &&
            !in_array(
                'do_notify',
                $this->columns(SOME::_dbprefix() . "cms_shop_orders_statuses")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders_statuses
                           ADD do_notify TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Notify user' AFTER name,
                           ADD notification_title TEXT NULL DEFAULT NULL COMMENT 'User notification title' AFTER do_notify,
                           ADD notification TEXT NULL DEFAULT NULL COMMENT 'User notification' AFTER notification_title";
            $this->SQL->query($sqlQuery);

            $messageHeader = '<p>' . View_Web::i()->_('GREETINGS') . '</p>' . "\n\n<p>";
            $messageFooter = "</p>\n\n"
                           . "<p>--</p>\n\n"
                           . "<p>\n  "
                           . View_Web::i()->_('WITH_RESPECT') . ",<br />\n  "
                           . View_Web::i()->_('ADMINISTRATION_OF_SITE')
                           . ' <a href="http' . ($_SERVER['HTTPS'] == 'on' ? 's' : '') . '://' . htmlspecialchars($_SERVER['HTTP_HOST']) . '">'
                           . htmlspecialchars($_SERVER['HTTP_HOST']) . "</a>\n"
                           . "</p>";

            $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_shop_orders_statuses
                            SET notification_title = ?,
                                notification = ?
                          WHERE urn = ?";
            $this->SQL->query([
                $sqlQuery,
                [
                    View_Web::i()->_('NOTIFY_ORDER_IN_PROGRESS'),
                    $messageHeader .
                    htmlspecialchars(View_Web::i()->_('NOTIFY_ORDER_IN_PROGRESS')) .
                    $messageFooter,
                    'progress',
                ],
            ]);
            $this->SQL->query([
                $sqlQuery,
                [
                    View_Web::i()->_('NOTIFY_ORDER_COMPLETED'),
                    $messageHeader .
                    htmlspecialchars(View_Web::i()->_('NOTIFY_ORDER_COMPLETED')) .
                    $messageFooter,
                    'completed',
                ],
            ]);
            $this->SQL->query([
                $sqlQuery,
                [
                    View_Web::i()->_('NOTIFY_ORDER_CANCELED'),
                    $messageHeader .
                    htmlspecialchars(View_Web::i()->_('NOTIFY_ORDER_CANCELED')) .
                    $messageFooter,
                    'canceled',
                ],
            ]);
        }
    }


    /**
     * Добавляем в заказ сведения о присвоенном платежном номере банка
     */
    public function update20200316()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_orders", $this->tables) &&
            !in_array(
                'payment_id',
                $this->columns(SOME::_dbprefix() . "cms_shop_orders")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders
                           ADD payment_interface_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Payment interface ID#',
                           ADD payment_id VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Payment ID#',
                           ADD KEY (payment_interface_id),
                           ADD INDEX (payment_id)";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Добавляет поле для сохранения платежного URL
     */
    public function update20200503()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_orders", $this->tables) &&
            !in_array(
                'payment_url',
                $this->columns(SOME::_dbprefix() . "cms_shop_orders")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders
                           ADD payment_url VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Payment URL'";
            $this->SQL->query($sqlQuery);
        }
    }
}
