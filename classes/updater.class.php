<?php
/**
 * Менеджер обновлений
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use SOME\SOME;
use RAAS\Application;
use RAAS\IContext;
use RAAS\CMS\Snippet;
use RAAS\CMS\Snippet_Folder;

/**
 * Класс менеджера обновлений
 * @codeCoverageIgnore Поскольку мы не можем хранить все версии
 */
class Updater extends \RAAS\Updater
{
    public function preInstall()
    {
        $v = (string)($this->Context->registryGet('baseVersion') ?? '');
        if (version_compare($v, '4.2.28') < 0) {
            $this->update20150511();
            $this->update20151129();
            $this->update20160119();
            $this->update20180530();
            $this->update20190304();
            $this->update20200316();
        }
        if (version_compare($v, '4.2.32') < 0) {
            $this->update20200503();
        }

        if (version_compare($v, '4.2.41') < 0) {
            $this->update20200702();
        }

        if (version_compare($v, '4.2.66') < 0) {
            $this->update20201209();
        }
        if (version_compare($v, '4.2.75') < 0) {
            $this->update20210224();
        }
        if (version_compare($v, '4.2.80') < 0) {
            $this->update20210401();
        }
        if (version_compare($v, '4.3.7') < 0) {
            $this->update20211026();
        }
        if (version_compare($v, '4.3.20') < 0) {
            $this->update20220112();
        }
        if (version_compare($v, '4.3.44') < 0) {
            $this->update20230516();
        }
        if (version_compare($v, '4.3.49') < 0) {
            $this->update20230814();
        }
        if (version_compare($v, '4.3.65') < 0) {
            $this->update20240320();
        }
        if (version_compare($v, '4.3.71') < 0) {
            $this->update20240611();
        }
        if (version_compare($v, '4.3.97') < 0) {
            $this->update20240731();
        }
        if (version_compare($v, '4.4.6') < 0) {
            $this->update20241125();
        }
    }


    public function postInstall()
    {
        $w = new Webmaster();
        $cartTypes = Cart_Type::getSet();
        $w->checkStdInterfaces();
        if (!$cartTypes) {
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


    /**
     * Добавляет поля для обновления материалов и медиа-полей загрузчиками прайсов
     */
    public function update20200702()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_priceloaders", $this->tables) &&
            !in_array(
                'update_materials',
                $this->columns(SOME::_dbprefix() . "cms_shop_priceloaders")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_priceloaders
                           ADD update_materials TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Allow to update materials' AFTER create_materials,
                           ADD media_action TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Media fields action'";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Добавляет варианты доставки и самовывоза в корневой блок Яндекс-Маркета
     */
    public function update20201209()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_blocks_yml", $this->tables) &&
            !in_array(
                'delivery_options',
                $this->columns(SOME::_dbprefix() . "cms_shop_blocks_yml")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_blocks_yml
                           ADD delivery_options TEXT NULL DEFAULT NULL COMMENT 'Delivery options',
                           ADD pickup_options TEXT NULL DEFAULT NULL COMMENT 'Pickup options'";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Добавляет кэш характеристик к материалам
     */
    public function update20210224()
    {
        if (in_array(SOME::_dbprefix() . "cms_materials", $this->tables) &&
            !in_array(
                'cache_shop_props',
                $this->columns(SOME::_dbprefix() . "cms_materials")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_materials
                           ADD cache_shop_props TEXT NULL DEFAULT NULL COMMENT 'Items props cache'";
            $this->SQL->query($sqlQuery);
        }
    }

    /**
     * Добавляет пункт о недублировании товаров по категориям
     * в загрузчик прайсов
     */
    public function update20210401()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_priceloaders", $this->tables) &&
            !in_array(
                'cats_usage',
                $this->columns(SOME::_dbprefix() . "cms_shop_priceloaders")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_priceloaders
                           ADD cats_usage TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Categories usage'";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Добавляет функции расчета веса и размеров в тип корзины
     */
    public function update20211026()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_cart_types", $this->tables) &&
            !in_array(
                'weight_callback',
                $this->columns(SOME::_dbprefix() . "cms_shop_cart_types")
            )
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_cart_types
                           ADD weight_callback TEXT NULL DEFAULT NULL COMMENT 'Weight callback',
                           ADD sizes_callback TEXT NULL DEFAULT NULL COMMENT 'Sizes callback'";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Расширяем поля name и meta у товаров заказов
     */
    public function update20220112()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_orders_goods", $this->tables)) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders_goods
                          DROP PRIMARY KEY";
            $this->SQL->query($sqlQuery);

            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders_goods
                        CHANGE name name VARCHAR(256) NOT NULL DEFAULT '' COMMENT 'Name'";
            $this->SQL->query($sqlQuery);

            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders_goods
                        CHANGE meta meta VARCHAR(256) NOT NULL DEFAULT '' COMMENT 'Meta data'";
            $this->SQL->query($sqlQuery);

            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders_goods
                           ADD PRIMARY KEY (order_id, material_id, meta(64))";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Убрал первичный ключ из товаров заказов, т.к. может быть несколько виртуальных товрраов
     */
    public function update20230516()
    {
        $sqlQuery = "SELECT COUNT(*)
                       FROM information_schema.statistics
                      WHERE TABLE_SCHEMA = ?
                        AND table_name = 'cms_shop_orders_goods'
                        AND index_name = 'PRIMARY'";
        $sqlBind = [Application::i()->dbname];
        $sqlResult = (int)$this->SQL->getvalue([$sqlQuery, $sqlBind]);
        if ($sqlResult) {
            $sqlQuery = "ALTER TABLE `cms_shop_orders_goods` DROP PRIMARY KEY";
            $this->SQL->query([$sqlQuery]);
        }
    }


    /**
     * Добавляем флаг проверки остатков в корзине
     */
    public function update20230814()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_cart_types", $this->tables) &&
            !in_array('check_amount', $this->columns(SOME::_dbprefix() . "cms_shop_cart_types"))
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_cart_types
                           ADD check_amount TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Check amount' AFTER no_amount";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Обновляет типы полей для callback'ов блока Яндекс-Маркета
     */
    public function update20240320()
    {
        foreach ([
            ['table' => 'cms_shop_blocks_yml_fields', 'field' => 'field_callback', 'comment' => 'Field callback'],
            ['table' => 'cms_shop_blocks_yml_params', 'field' => 'field_callback', 'comment' => 'Field callback'],
            ['table' => 'cms_shop_blocks_yml_material_types_assoc', 'field' => 'params_callback', 'comment' => 'Params callback'],
        ] as $fieldData) {
            if (in_array(SOME::_dbprefix() . $fieldData['table'], $this->tables) &&
                in_array($fieldData['field'], $this->columns(SOME::_dbprefix() . $fieldData['table']))
            ) {
                $sqlQuery = "ALTER TABLE `" . SOME::_dbprefix() . $fieldData['table'] . "`
                            CHANGE `" . $fieldData['field'] . "` `" . $fieldData['field'] . "` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '" . $fieldData['comment'] . "'";
                $this->SQL->query($sqlQuery);
            }
        }
    }


    /**
     * Обновления по версии 4.3.71
     * Исправление значения по умолчанию в cms_shop_orders.user_agent
     * Переход от сниппетов интерфейсов к классам
     */
    public function update20240611()
    {
        // В cms_shop_orders.user_agent по умолчанию пустая строка
        if (in_array(SOME::_dbprefix() . "cms_shop_orders", $this->tables)) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders
                        CHANGE user_agent user_agent VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'User Agent'";
            $this->SQL->query($sqlQuery);
        }

        // В cms_shop_orders поле payment_interface_classname и ключ к нему
        if (in_array(SOME::_dbprefix() . "cms_shop_orders", $this->tables) &&
            !in_array('payment_interface_classname', $this->columns(SOME::_dbprefix() . "cms_shop_orders"))
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_orders
                           ADD payment_interface_classname VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Payment interface classname' AFTER paid,
                           ADD INDEX (payment_interface_classname)";
            $this->SQL->query($sqlQuery);
        }

        // В cms_shop_priceloaders поле interface_classname и ключ к нему
        if (in_array(SOME::_dbprefix() . "cms_shop_priceloaders", $this->tables) &&
            !in_array('interface_classname', $this->columns(SOME::_dbprefix() . "cms_shop_priceloaders"))
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_priceloaders
                           ADD interface_classname VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Interface classname' AFTER urn,
                           ADD INDEX (interface_classname)";
            $this->SQL->query($sqlQuery);
        }

        // В cms_shop_imageloaders поле interface_classname и ключ к нему
        if (in_array(SOME::_dbprefix() . "cms_shop_imageloaders", $this->tables) &&
            !in_array('interface_classname', $this->columns(SOME::_dbprefix() . "cms_shop_imageloaders"))
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_imageloaders
                           ADD interface_classname VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Interface classname' AFTER sep_string,
                           ADD INDEX (interface_classname)";
            $this->SQL->query($sqlQuery);
        }

        // в cms_shop_blocks_cart поле epay_interface_classname и ключ к нему
        if (in_array(SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) &&
            !in_array('epay_interface_classname', $this->columns(SOME::_dbprefix() . "cms_shop_blocks_cart"))
        ) {
            $sqlQuery = "ALTER TABLE " . SOME::_dbprefix() . "cms_shop_blocks_cart
                           ADD epay_interface_classname VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'E-pay interface classname' AFTER cart_type,
                           ADD INDEX (epay_interface_classname)";
            $this->SQL->query($sqlQuery);
        }

        if (in_array(SOME::_dbprefix() . "cms_snippets", $this->tables)) {
            $interfacesMapping = [
                '__raas_shop_cart_interface' => CartInterface::class,
                '__raas_my_orders_interface' => MyOrdersInterface::class,
                '__raas_shop_compare_interface' => CompareInterface::class,
                '__raas_shop_goods_comments_interface' => GoodsCommentsInterface::class,
                '__raas_shop_imageloader_interface' => ImageloaderInterface::class,
                '__raas_shop_priceloader_interface' => PriceloaderInterface::class,
                '__raas_shop_spec_interface' => SpecInterface::class,
                '__raas_shop_yml_interface' => YMLInterface::class,
            ];
            $sqlQuery = "SELECT COUNT(*)
                           FROM " . SOME::_dbprefix() . "cms_snippets
                          WHERE urn IN (" . implode(", ", array_fill(0, count($interfacesMapping), "?")) . ")";
            $sqlBind = array_keys($interfacesMapping);
            $sqlResult = (int)$this->SQL->getvalue([$sqlQuery, $sqlBind]);
            if ($sqlResult > 0) {
                foreach ($interfacesMapping as $snippetURN => $interfaceClassname) {
                    $sqlBind = ['snippetURN' => $snippetURN, 'interfaceClassname' => $interfaceClassname];
                    // Заменим основной интерфейс
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_blocks AS tB
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tB.interface_id = tS.id
                                    SET tB.interface_id = 0,
                                        tB.interface_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    // Заменим интерфейс кэширования
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_blocks AS tB
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tB.cache_interface_id = tS.id
                                    SET tB.cache_interface_id = 0,
                                        tB.cache_interface_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    // Заменим интерфейс процессоров
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_fields AS tF
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tF.preprocessor_id = tS.id
                                    SET tF.preprocessor_id = 0,
                                        tF.preprocessor_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_fields AS tF
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tF.postprocessor_id = tS.id
                                    SET tF.postprocessor_id = 0,
                                        tF.postprocessor_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    // Заменим интерфейс оплаты заказов
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_shop_orders AS tOr
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tOr.payment_interface_id = tS.id
                                    SET tOr.payment_interface_id = 0,
                                        tOr.payment_interface_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    // Заменим платежный интерфейс корзины
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_shop_blocks_cart AS tBC
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tBC.epay_interface_id = tS.id
                                    SET tBC.epay_interface_id = 0,
                                        tBC.epay_interface_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    // Заменим интерфейс загрузчиков
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_shop_priceloaders AS tL
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tL.interface_id = tS.id
                                    SET tL.interface_id = 0,
                                        tL.interface_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    $sqlQuery = "UPDATE " . SOME::_dbprefix() . "cms_shop_imageloaders AS tL
                                   JOIN " . SOME::_dbprefix() . "cms_snippets AS tS ON tL.interface_id = tS.id
                                    SET tL.interface_id = 0,
                                        tL.interface_classname = :interfaceClassname
                                  WHERE tS.urn = :snippetURN";
                    $this->SQL->query([$sqlQuery, $sqlBind]);
                    // Удалим сниппеты
                    $sqlQuery = "DELETE FROM " . SOME::_dbprefix() . "cms_snippets WHERE urn = ?";
                    $this->SQL->query([$sqlQuery, [$snippetURN]]);
                }
            }
        }
    }


    /**
     * Обновление по версии 4.3.97 - увеличение размера пароля платежной системы
     */
    public function update20240731()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_blocks_cart", $this->tables) &&
            in_array('epay_pass1', $this->columns(SOME::_dbprefix() . "cms_shop_blocks_cart"))
        ) {
            $sqlQuery = "ALTER TABLE `cms_shop_blocks_cart`
                              CHANGE `epay_pass1` `epay_pass1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'E-pay pass1'";
            $this->SQL->query($sqlQuery);
            $sqlQuery = "ALTER TABLE `cms_shop_blocks_cart`
                              CHANGE `epay_pass2` `epay_pass2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'E-pay pass2'";
            $this->SQL->query($sqlQuery);
        }
    }


    /**
     * Обновление по версии 4.4.6 - добавление пошагового интерфейса для загрузчиков прайсов
     */
    public function update20241125()
    {
        if (in_array(SOME::_dbprefix() . "cms_shop_priceloaders", $this->tables) &&
            !in_array('step_interface', $this->columns(SOME::_dbprefix() . "cms_shop_priceloaders"))
        ) {
            $sqlQuery = "ALTER TABLE cms_shop_priceloaders
                           ADD step_interface TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Step interface'";
            $this->SQL->query($sqlQuery);
        }
    }
}
