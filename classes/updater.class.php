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
        // 2025 год - 8
        // 2024 год - 7/8
        // 2023 год - 7
        // 2022 год - 5/7
        // 2021 год - 5 -- убираем его и ранее
        $v = (string)($this->Context->registryGet('baseVersion') ?? '');
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
        // ПО ВОЗМОЖНОСТИ НЕ ПИШЕМ СЮДА, А ПИШЕМ В postInstall
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
                            CHANGE `" . $fieldData['field'] . "` `" . $fieldData['field'] . "` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '" . $fieldData['comment'] . "'";
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
                        CHANGE user_agent user_agent VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'User Agent'";
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
                              CHANGE `epay_pass1` `epay_pass1` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'E-pay pass1'";
            $this->SQL->query($sqlQuery);
            $sqlQuery = "ALTER TABLE `cms_shop_blocks_cart`
                              CHANGE `epay_pass2` `epay_pass2` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'E-pay pass2'";
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
