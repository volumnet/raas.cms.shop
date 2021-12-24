<?php
/**
 * Файл класса интерфейса Яндекс-Маркета
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use SOME\EventProcessor;

/**
 * Класс интерфейса Яндекс-Маркета
 */
class YMLInterface extends AbstractInterface
{
    /**
     * Ограничение по товарам (для отладки)
     */
    protected $limit = 0;

    /**
     * Конструктор класса
     * @param Block_YML $block Блок, для которого применяется интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     * @param int $limit Ограничение по товарам (для отладки)
     */
    public function __construct(
        Block_YML $block,
        Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        $limit = 0
    ) {
        parent::__construct(
            $block,
            $page,
            $get,
            $post,
            $cookie,
            $session,
            $server
        );
        $this->limit = $limit;
    }


    /**
     * Выводит данные в формате Яндекс.Маркет XML
     * @param bool $appendHeader Добавить заголовок типа страницы
     * @param int|null $maxExecutionTime Установить время выполнения интерфейса
     *                                   в секундах
     * @param bool $return Вернуть результат как строку
     *                     (если false, выводит в stdOut)
     * @return string|null Возвращает текст, если установлен $return
     */
    public function process(
        $appendHeader = false,
        $maxExecutionTime = null,
        $return = false
    ) {

        if ($maxExecutionTime) {
            ini_set('max_execution_time', (int)$maxExecutionTime);
        }
        if ($appendHeader) {
            header('Content-Type: application/xml');
        }
        $headerText = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n"
                    . '<yml_catalog date="' . date('Y-m-d H:i') . '">';
        $footerText = '</yml_catalog>';
        $memoryText = $this->getMemoryUsageBlock();
        if ($return) {
            $text = $headerText
                  . $this->outputShopBlock($this->block, true)
                  . $footerText
                  . $memoryText;
            return $text;
        } else {
            echo $headerText;
            $this->outputShopBlock($this->block, false);
            echo $footerText . $memoryText;
        }
    }


    /**
     * Выводит блок <shop>
     * @param Block_YML $block Блок для обработки
     * @param bool $return Вернуть результат как строку
     *                     (если false, выводит в stdOut)
     */
    public function outputShopBlock(Block_YML $block, $return = false)
    {
        $headerText = '<shop>';
        $footerText = '</shop>';
        $shopMetaText = $this->getShopBlockMeta($block);
        if ($return) {
            $text = $headerText . $shopMetaText . $this->outputOffersBlock(
                $block->catalog_cats_ids,
                $block->types,
                true
            ) . $footerText;
            return $text;
        } else {
            echo $headerText . $shopMetaText;
            $this->outputOffersBlock(
                $block->catalog_cats_ids,
                $block->types,
                false
            );
            echo $footerText;
        }
    }


    /**
     * Получает блок использования памяти
     * @return string
     */
    public function getMemoryUsageBlock()
    {
        $text = '<!-- Memory used: '
              . number_format(memory_get_peak_usage(), 0, '.', ' ')
              . ' -->';
        return $text;
    }


    /**
     * Получает блок параметров магазина в формате Яндекс.Маркета
     * @param Block_YML $block Блок Яндекс.Маркета для обработки
     * @return string
     */
    public function getShopBlockMeta(Block_YML $block)
    {
        $text = $this->getShopNameBlock($block)
              . $this->getShopCustomFieldBlock('company', $block)
              . $this->getShopURLBlock()
              . $this->getShopPlatformBlock()
              . $this->getShopVersionBlock()
              . $this->getShopCustomFieldBlock('agency', $block)
              . $this->getShopCustomFieldBlock('email', $block)
              . $this->getShopCurrenciesBlock($block)
              . $this->getShopCategoriesBlock($block)
              . $this->getShopLocalDeliveryCostBlock($block)
              . $this->getShopCustomFieldBlock('cpa', $block)
              . $this->getShopCustomFieldBlock('delivery_options', $block)
              . $this->getShopCustomFieldBlock('pickup_options', $block);
        return $text;
    }


    /**
     * Получает блок наименования магазина (если есть)
     * @param Block_YML $block Блок Яндекс.Маркета для получения
     * @return string
     */
    public function getShopNameBlock(Block_YML $block)
    {
        $text = '';
        if (isset($block->config['shop_name'])) {
            $text .= '<name>'
                  .     htmlspecialchars(trim($block->config['shop_name']))
                  .  '</name>';
        }
        return $text;
    }


    /**
     * Получает блок URL магазина (если есть)
     * @return string
     */
    public function getShopURLBlock()
    {
        $text = '<url>' . $this->getCurrentHostURL() . '</url>';
        return $text;
    }


    /**
     * Получает блок платформы магазина (если есть)
     * @return string
     */
    public function getShopPlatformBlock()
    {
        $text = '<platform>RAAS.CMS</platform>';
        return $text;
    }


    /**
     * Получает блок версии платформы магазина (если есть)
     * @return string
     */
    public function getShopVersionBlock()
    {
        $text = '<version>4.2</version>';
        return $text;
    }


    /**
     * Получает блок валют
     * @param Block_YML $block Блок Яндекс.Маркета для получения
     * @return string
     */
    public function getShopCurrenciesBlock(Block_YML $block)
    {
        $text .= '<currencies>'
              .    '<currency id="' . htmlspecialchars($block->config['default_currency']) . '" rate="1" />';
        foreach ((array)$block->currencies as $key => $row) {
            $text .= $this->getShopCurrencyBlock(
                $key,
                $row['rate'],
                isset($row['plus']) ? $row['plus'] : null
            );
        }
        $text .= '</currencies>';
        return $text;
    }


    /**
     * Получает блок валюты
     * @param string $currencyId Код валюты
     * @param float $rate Курс валюты, единиц основной валюты за единицу
     * @param float $plus|null Добавочная стоимость, единиц основной валюты
     * @return string
     */
    public function getShopCurrencyBlock($currencyId, $rate, $plus = null)
    {
        $attrs = [
            'id' => $currencyId,
            'rate' => $this->canonizeFloat($rate),
        ];
        if ($plus !== null) {
            $attrs['plus'] = trim(number_format((float)$plus, 2, '.', ''));
        }
        $temp = [];
        foreach ($attrs as $key => $val) {
            $temp[] = $key . '="' . htmlspecialchars($val) . '"';
        }
        $text .= '<currency ' . implode(' ', $temp) . ' />';
        return $text;
    }


    /**
     * Получает блок категорий
     * @param Block_YML $block Блок Яндекс.Маркета для получения
     * @return string
     */
    public function getShopCategoriesBlock(Block_YML $block)
    {
        $text = '<categories>';
        $catalogCatsIds = $block->catalog_cats_ids;
        foreach ($catalogCatsIds as $catalogId) {
            $page = new Page($catalogId);
            $text .= $this->getShopCategoryBlock($page, $catalogCatsIds);
        }
        $text .= '</categories>';
        return $text;
    }


    /**
     * Получает блок категории
     * @param Page $page Категория
     * @param array<int> $catalogCatsIds ID# всех категорий YML-блока
     */
    public function getShopCategoryBlock(Page $page, array $catalogCatsIds = [])
    {
        $attrs = [
            'id' => $page->id,
        ];
        if ($page->pid && in_array($page->pid, $catalogCatsIds)) {
            $attrs['parentId'] = (int)$page->pid;
        }
        $temp = [];
        foreach ($attrs as $key => $val) {
            $temp[] = $key . '="' . htmlspecialchars($val) . '"';
        }
        $text = '<category ' . implode(' ', $temp) . '>'
              .    htmlspecialchars(trim($page->name))
              . '</category>';
        return $text;
    }


    /**
     * Получает блок стоимости локальной доставки
     * @param Block_YML $block Блок Яндекс.Маркета для получения
     * @return string
     */
    public function getShopLocalDeliveryCostBlock(Block_YML $block)
    {
        $text = '';
        if (isset($block->config['local_delivery_cost'])) {
            $val = $this->canonizeFloat($block->config['local_delivery_cost']);
            $text .= '<local_delivery_cost>'
                  .     htmlspecialchars($val)
                  .  '</local_delivery_cost>';
        }
        return $text;
    }




    /**
     * Получает блок произвольного поля магазина (если есть)
     * @param string $key Наименование поля в формате Яндекс.Маркета
     * @param Block_YML $block Блок Яндекс.Маркета для получения
     * @return string
     */
    public function getShopCustomFieldBlock($key, Block_YML $block)
    {
        $text = '';
        if (isset($block->config[$key])) {
            if (in_array($key, ['delivery_options', 'pickup_options'])) {
                $json = (array)json_decode($block->config[$key], true);
                $content = $this->getDeliveryOptions($json);
                $key = str_replace('_', '-', $key);
                if (!$content) {
                    return '';
                }
            } else {
                $content = htmlspecialchars(trim($block->config[$key]));
            }
            $text .= '<' . $key . '>' . $content . '</' . $key . '>';
        }
        return $text;
    }


    /**
     * Выводит блок предложений <offers>
     * @param array<int> $catalogCatsIds ID# страниц, на которые
     *                                   распространяется действие блока
     * @param array<Material_Type> $types Массив типов материалов, покрываемых
     *                                    блоком, с доп. полями от блока
     * @param bool $return Вернуть результат как строку
     *                     (если false, выводит в stdOut)
     */
    public function outputOffersBlock(
        array $catalogCatsIds = [],
        array $types = [],
        $return = false
    ) {
        $headerText = '<offers>';
        $footerText = '</offers>';
        $text = '';
        if ($return) {
            $text .= $headerText;
        } else {
            echo $headerText;
        }
        foreach ($types as $mtype) {
            $ignoredFields = $this->getMTypeIgnoredFields($mtype);
            $stdFieldsNames = $this->getMTypeStdFields($mtype);
            $sqlResult = $this->getMaterialsCursor($mtype, $catalogCatsIds);
            EventProcessor::emit('starttype', $mtype, [
                'size' => $sqlResult->rowCount(),
            ]);
            $i = 0;
            foreach ($sqlResult as $sqlRow) {
                $material = new Material($sqlRow);
                $offerBlock = $this->getOfferBlock(
                    $material,
                    $mtype,
                    $catalogCatsIds,
                    $stdFieldsNames,
                    $ignoredFields
                );
                if ($return) {
                    $text .= $offerBlock;
                } else {
                    echo $offerBlock;
                }
                $material->rollback();
                unset($material);
                $i++;
                if ($this->limit && ($i >= $this->limit)) {
                    break;
                }
            }
        }
        if ($return) {
            $text .= $footerText;
            return $text;
        } else {
            echo $footerText;
        }
    }


    /**
     * Получает список игнорируемых полей
     * @param Material_Type $mtype Тип материала
     * @return array<string ID# поля или URN системного поля>
     */
    public function getMTypeIgnoredFields(Material_Type $mtype)
    {
        $ignoredFields = [];
        foreach ($mtype->settings['fields'] as $fieldArr) {
            $ignoredFields[] = $fieldArr['field']->id ?: $fieldArr['field_id'];
        }
        if ($mtype->settings['params'] ||
            $mtype->settings['param_exceptions']
        ) {
            foreach ($mtype->settings['params'] as $paramArr) {
                $ignoredFields[] =  $paramArr['field']->id
                                 ?: $paramArr['field_id'];
            }
            if ($mtype->settings['param_exceptions']) {
                foreach ((array)$mtype->settings['ignored'] as $ignored) {
                    if ($ignored instanceof Material_Field) {
                        $ignoredFields[] = $ignored->id;
                    } else {
                        $ignoredFields[] = $ignored;
                    }
                }
            }
        }
        $ignoredFields = array_unique($ignoredFields);
        $ignoredFields = array_filter($ignoredFields);
        $ignoredFields = array_values($ignoredFields);
        return $ignoredFields;
    }


    /**
     * Получает список стандартных полей по типу материалов
     * @param Material_Type $mtype Тип материала
     * @return array<string>
     */
    public function getMTypeStdFields(Material_Type $mtype)
    {
        $stdFields = array_merge(
            Block_YML::$defaultFields[0],
            (array)Block_YML::$ymlTypes[$mtype->settings['type']],
            Block_YML::$defaultFields[1]
        );
        return $stdFields;
    }


    /**
     * Получает указатель на список товаров по типу материалов
     * @param Material_Type $mtype Тип материала
     * @param array<int> $catalogCatsIds ID# страниц, на которые
     *                                   распространяется действие блока
     * @return PDOStatement
     */
    public function getMaterialsCursor(
        Material_Type $mtype,
        array $catalogCatsIds = []
    ) {
        $sqlQuery = "SELECT tM.*
                       FROM " . Material::_tablename() . " AS tM ";
        if (!$mtype->global_type) {
            $sqlQuery .= " JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc
                             AS tMPA
                             ON tMPA.id = tM.id";
        }
        $sqlQuery .= " WHERE tM.vis
                         AND tM.pid IN (" . implode(", ", $mtype->selfAndChildrenIds) . ") ";
        if (!$mtype->global_type && $catalogCatsIds) {
            $sqlQuery .= " AND tMPA.pid IN (" . implode(", ", $catalogCatsIds) . ")";
        }
        $sqlQuery .= " GROUP BY tM.id";
        $sqlResult = Material::_SQL()->query($sqlQuery);
        return $sqlResult;
    }


    /**
     * Получает блок предложения <offer>
     * @param Material $material Материал, для которого строится предложение
     * @param Material_Type $mtype Тип материала с дополнительными полями
     *                             от YML-блока
     * @param array<int> $catalogCatsIds ID# страниц, на которые
     *                                   распространяется действие блока
     * @param array<string> $stdFieldsNames Набор стандартных полей
     *                                      для Яндекс.Маркета
     * @param array<string> $ignoredFields Игнорируемые поля
     * @return string
     */
    public function getOfferBlock(
        Material $material,
        Material_Type $mtype,
        array $catalogCatsIds = [],
        array $stdFieldsNames = [],
        array $ignoredFields = []
    ) {
        $text = '';
        $attrsStdFields = array_intersect(
            $stdFieldsNames,
            ['available', 'bid', 'cbid']
        );
        $offerAttrs = $this->getOfferAttrs($material, $mtype, $attrsStdFields);
        $blocksStdFields = array_diff(
            $stdFieldsNames,
            ['available', 'bid', 'cbid']
        );
        $offerTxt = $this->getOfferParamsBlocks(
            $material,
            $mtype,
            $blocksStdFields,
            $ignoredFields,
            $catalogCatsIds
        );
        $text = '<offer' . $offerAttrs . '>' . $offerTxt . '</offer>';
        EventProcessor::emit('', $material);
        return $text;
    }


    /**
     * Получить атрибуты для блока предложения
     * @param Material $material Материал, для которого строится предложение
     * @param Material_Type $mtype Тип материала с дополнительными полями
     *                             от YML-блока
     * @param array<string> $attrsStdFields Набор стандартных полей
     *                                      для отображения атрибутами
     */
    public function getOfferAttrs(
        Material $material,
        Material_Type $mtype,
        array $attrsStdFields = []
    ) {
        $offerAttrs = ' id="' . (int)$material->id . '"';
        if ($mtype->settings['type']) {
            $offerAttrs .= ' type="'
                        .      htmlspecialchars($mtype->settings['type'])
                        .  '"';
        }
        foreach ($attrsStdFields as $key) {
            $offerAttrs .= $this->getOfferCustomFieldBlock(
                $key,
                $material,
                (array)$mtype->settings['fields'][$key],
                false,
                true
            );
        }
        return $offerAttrs;
    }


    /**
     * Получить блочные характеристики для блока предложения
     * @param Material $material Материал, для которого строится предложение
     * @param Material_Type $mtype Тип материала с дополнительными полями
     *                             от YML-блока
     * @param array<string> $ignoredFields Игнорируемые поля
     * @param array<string> $blocksStdFields Набор стандартных полей
     *                                       для отображения блоками
     * @param array<int> $catalogCatsIds ID# всех категорий YML-блока
     */
    public function getOfferParamsBlocks(
        Material $material,
        Material_Type $mtype,
        array $blocksStdFields = [],
        array $ignoredFields = [],
        array $catalogCatsIds = []
    ) {
        $offerTxt = '';
        foreach ($blocksStdFields as $key) {
            switch ($key) {
                case 'url':
                    $offerTxt .= $this->getOfferURLBlock($material);
                    break;
                case 'categoryId':
                    $offerTxt .= $this->getOfferCategoriesBlock(
                        $material,
                        $mtype->global_type,
                        $catalogCatsIds
                    );
                    break;
                case 'oldprice':
                    $offerTxt .= $this->getOfferOldPriceBlock(
                        $material,
                        (array)$mtype->settings['fields'][$key]
                    );
                    break;
                default:
                    $offerTxt .= $this->getOfferCustomFieldBlock(
                        $key,
                        $material,
                        (array)$mtype->settings['fields'][$key],
                        ($key == 'description'),
                        false
                    );
                    break;
            }
        }

        if ($mtype->settings['params'] ||
            $mtype->settings['param_exceptions']
        ) {
            $params = $this->getMTypeParams($mtype, $ignoredFields);
            foreach ($params as $param) {
                $offerTxt .= $this->getOfferParamBlock($key, $material, $param);
            }
        }
        return $offerTxt;
    }


    /**
     * Получает блок URL предложения
     * @param Material $material Материал предложения
     * @return string
     */
    public function getOfferURLBlock(Material $material)
    {
        $text = '<url>'
              .    $this->getCurrentHostURL() . $material->url
              . '</url>';
        return $text;
    }


    /**
     * Получает блок категорий предложения
     * @param Material $material Материал предложения
     * @param bool $isGlobal Материал глобальный
     * @param array<int> $catalogCatsIds ID# всех категорий YML-блока
     * @return string
     */
    public function getOfferCategoriesBlock(
        Material $material,
        $isGlobal,
        array $catalogCatsIds = []
    ) {
        if ($isGlobal) {
            $cats = array_intersect($material->parents_ids, $catalogCatsIds);
            $cats = array_values($cats);
            $cats = array_slice($cats, 0, 1);
        } else {
            $cats = array_intersect($material->pages_ids, $catalogCatsIds);
        }
        $text = '';
        foreach ($cats as $val) {
            $text .= $this->getOfferCategoryBlock($val);
        }
        return $text;
    }


    /**
     * Получает блок категории предложения
     * @param int $categoryId ID# категории
     * @return string
     */
    public function getOfferCategoryBlock($categoryId)
    {
        $text = '<categoryId>' . (int)$categoryId . '</categoryId>';
        return $text;
    }


    /**
     * Получает блок старой цены предложения
     * @param Material $material Материал предложения
     * @param [
     *            'field' => Material_Field Привязанное поле типа материала,
     *            'field_id' => string URN системного поля (используется,
     *                                 когда не задано поле типа материала),
     *            'value' => string Значение по умолчанию,
     *            'callback' => string Текст обработчика поля с текущими
     *                                 переменными,
     *                                 также $x - результирующее значение поля,
     *                                 $Field - то же что $settings['field']
     *        ] $settings Настройки поля
     * @return string
     */
    public function getOfferOldPriceBlock(
        Material $material,
        array $settings = []
    ) {
        if (!$settings) {
            return '';
        }
        $v = (float)$this->getValue($material, $key, $settings);
        if (!$v) {
            return '';
        }
        $text = '<oldprice>' . htmlspecialchars((float)$v) . '</oldprice>';
        return $text;
    }


    /**
     * Получает блок произвольного поля предложения
     * @param string $key Наименование поля в системе Яндекс.Маркета
     * @param Material $material Материал предложения
     * @param [
     *            'field' => Material_Field Привязанное поле типа материала,
     *            'field_id' => string URN системного поля (используется, когда
     *                                 не задано поле типа материала),
     *            'value' => string Значение по умолчанию,
     *            'callback' => string Текст обработчика поля с текущими
     *                                 переменными,
     *                                 также $x - результирующее значение поля,
     *                                 $Field - то же что $settings['field']
     *        ] $settings Настройки поля
     * @param bool $asDescription Обработать как описание предложения
     * @param bool $asAttr Вернуть как атрибут (если false, то как блок)
     * @return string
     */
    public function getOfferCustomFieldBlock(
        $key,
        Material $material,
        array $settings = [],
        $asDescription = false,
        $asAttr = false
    ) {
        if (!$settings) {
            return '';
        }
        $v = $this->getValue($material, $key, $settings);
        if ($asDescription) {
            $v = strip_tags($v);
            $v = html_entity_decode($v, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $v = preg_replace('/(\\r|\\n)+/umi', ' ', $v);
            $v = \SOME\Text::cuttext($v, 512, '...');
        }
        if (in_array($key, ['delivery_options', 'pickup_options'])) {
            // 2021-12-23, AVS: принудительно приводим к массиву, т.к.
            // объявление функции требует массив
            $content = $this->getDeliveryOptions((array)$v);
            if (!$content || $asAttr) {
                return '';
            }
            $key = str_replace('_', '-', $key);
        } else {
            $v = trim($v);
            if ($v === '') {
                return '';
            }
            $content = htmlspecialchars($v);
        }
        if ($asAttr) {
            $text = ' ' . $key . '="' . $content . '"';
        } else {
            $text = '<' . $key . '>' . $content . '</' . $key . '>';
        }
        return $text;
    }


    /**
     * Получает блок опций (доставки или самовывоза)
     * @param array $options <pre>array<[
     *     'cost' => int Стоимость,
     *     'days' => string Дней,
     *     'order_before' => string Заказать до
     * ]></pre> опции
     * @return string
     */
    public function getDeliveryOptions(array $options)
    {
        $result = '';
        foreach ($options as $option) {
            $result .= '<option cost="' . (int)$option['cost'] . '" days="'
                . htmlspecialchars(trim($option['days']) ?: 0) . '"';
            if ($orderBefore = trim($option['order_before'])) {
                $result .= ' order-before="'
                    . htmlspecialchars($orderBefore) . '"';
            }
            $result .= ' />';
        }
        return $result;
    }


    /**
     * Получает массив настроек параметров по типу материалов
     * @param Material_Type $mtype Тип материала
     * @param array<string> $ignoredFields Игнорируемые поля
     * @return array<string[] Наименование параметра => [
     *             'name' => string Заголовок параметра
     *             'field' => Material_Field Привязанное поле типа материала,
     *             'field_id' => string URN системного поля (используется, когда
     *                                  не задано поле типа материала),
     *             'value' => string Значение по умолчанию,
     *             'callback' => string Текст обработчика поля с текущими
     *                                  переменными,
     *                                  также $x - результирующее значение поля,
     *                                  $Field - то же что $settings['field']
     *         ]>
     */
    public function getMTypeParams(
        Material_Type $mtype,
        array $ignoredFields = []
    ) {
        $params = $mtype->settings['params'];
        if ($mtype->settings['param_exceptions']) {
            foreach (['name', 'description'] as $key) {
                if (!in_array($key, $ignoredFields)) {
                    $param = ['field_id' => $key, 'auto' => true];
                    if ($mtype->settings['params_callback']) {
                        $param['params_callback'] = $mtype->settings['params_callback'];
                    }
                    $params[] = $param;
                }
            }
            foreach ($mtype->fields as $f) {
                if (!in_array($f->id, $ignoredFields)) {
                    $param = ['field' => $f, 'auto' => true];
                    if ($mtype->settings['params_callback']) {
                        $param['params_callback'] = $mtype->settings['params_callback'];
                    }
                    $params[] = $param;
                }
            }
        }
        return $params;
    }


    /**
     * Получает блок произвольного дополнительного параметра предложения
     * @param string $key Наименование параметра
     * @param Material $material Материал предложения
     * @param [
     *            'name' => string Заголовок параметра
     *            'field' => Material_Field Привязанное поле типа материала,
     *            'field_id' => string URN системного поля (используется, когда
     *                                 не задано поле типа материала),
     *            'value' => string Значение по умолчанию,
     *            'callback' => string Текст обработчика поля с текущими
     *                                 переменными,
     *                                 также $x - результирующее значение поля,
     *                                 $Field - то же что $settings['field']
     *        ] $settings Настройки параметра
     * @return string
     */
    public function getOfferParamBlock(
        $key,
        Material $material,
        array $settings = []
    ) {
        if (!$settings) {
            return '';
        }
        $v = $this->getValue($material, $key, $settings);
        $v = trim($v);
        if ($v === '') {
            return '';
        }
        $paramAttrs = '';
        if ($settings['name']) {
            $paramAttrs .= ' name="'
                        .      htmlspecialchars($settings['name'])
                        .  '"';
        } elseif ($settings['field']->id) {
            $paramAttrs .= ' name="'
                        .      htmlspecialchars($settings['field']->name)
                        .  '"';
        } elseif ($settings['field_id'] == 'name') {
            $paramAttrs .= ' name="' . htmlspecialchars(NAME) . '"';
        } elseif ($settings['field_id'] == 'description') {
            $paramAttrs .= ' name="' . htmlspecialchars(DESCRIPTION) . '"';
        } else {
            $paramAttrs .= ' name="'
                        .      htmlspecialchars($settings['field_id'])
                        .  '"';
        }
        if ($settings['unit']) {
            $paramAttrs .= ' unit="'
                        .      htmlspecialchars($settings['unit'])
                        .  '"';
        }
        $text = '<param' . $paramAttrs . '>'
              .    htmlspecialchars($v)
              . '</param>';
        return $text;
    }


    /**
     * Получает значение поля по его URN в системе Яндекс.Маркета
     * @param Material $Item Материал для обработки
     * @param string $key URN поля в системе Яндекс.Маркета
     * @param array <pre>[
     *     'field' => Material_Field Привязанное поле типа материала,
     *     'field_id' => string URN системного поля (используется, когда
     *                          не задано поле типа материала),
     *     'value' => string Значение по умолчанию,
     *     'callback' => string Текст обработчика поля с текущими
     *                          переменными,
     *                          также $x - результирующее значение поля,
     *                          $Field - то же что $settings['field'],
     * ]</pre> $settings Настройки поля
     * @return string|mixed
     */
    public function getValue(Material $item, $key, array $settings = [])
    {
        $Item = $item;
        $x = $this->getRawValue($item, $settings);
        $f = $this->getCallbackCode($key, $settings);
        if ($f) {
            $Field = $settings['field'];
            $x = eval($f);
        }
        if ($f && is_string($x)) {
            $x = preg_replace('/\\t+/umi', ' ', $x);
            $x = preg_replace('/ +/umi', ' ', $x);
            $x = trim($x);
        }
        return $x;
    }


    /**
     * Получает сырое (не обработанное обработчиком) значение поля
     * @param Material $Item Материал для обработки
     * @param [
     *            'field' => Material_Field Привязанное поле типа материала,
     *            'field_id' => string URN системного поля (используется, когда
     *                                 не задано поле типа материала),
     *            'value' => string Значение по умолчанию,
     *            'callback' => string Текст обработчика поля с текущими
     *                                 переменными,
     *                                 также $x - результирующее значение поля,
     *                                 $Field - то же что $settings['field']
     *        ] $settings Настройки поля
     * @return string|null;
     */
    public function getRawValue(Material $item, array $settings = [])
    {
        $x = null;
        if ($settings['field']->id) {
            if ($settings['field']->datatype == 'image') {
                $att = $item->{$settings['field']->urn};
                if ($settings['field']->multiple) {
                    $att = $att[0];
                }
                $x = $this->getCurrentHostURL() . '/' . $att->fileURL;
            } else {
                $x = $item->fields[$settings['field']->urn]->doRich();
            }
        } elseif ($settings['field_id']) {
            $x = $item->{$settings['field_id']};
        }
        if (($x === null) && $settings['value']) {
            $x = $settings['value'];
        }
        return $x;
    }


    /**
     * Получает код обработчика
     * @param string $key URN поля в системе Яндекс.Маркета
     * @param [
     *            'field' => Material_Field Привязанное поле типа материала,
     *            'field_id' => string URN системного поля (используется, когда
     *                                 не задано поле типа материала),
     *            'value' => string Значение по умолчанию,
     *            'callback' => string Текст обработчика поля с текущими
     *                                 переменными,
     *                                 также $x - результирующее значение поля,
     *                                 $Field - то же что $settings['field']
     *        ] $settings Настройки поля
     * @return string|null
     */
    public function getCallbackCode($key, array $settings = [])
    {
        $f = null;
        if (isset($settings['callback']) && $settings['callback']) {
            $f = $settings['callback'];
        } elseif (isset(Block_YML::$ymlFields[$key]['callback']) &&
            Block_YML::$ymlFields[$key]['callback']
        ) {
            $f = Block_YML::$ymlFields[$key]['callback'];
        } elseif (isset(Block_YML::$ymlFields[$key]['type']) &&
            (Block_YML::$ymlFields[$key]['type'] == 'number')
        ) {
            $f = 'return $this->canonizeFloat($x);';
        }
        return $f;
    }


    /**
     * Заменяет в дробных числах запятую на точку и триммирует их
     * @param string $number Число для обработки
     * @return string
     */
    public static function canonizeFloat($number)
    {
        $text = trim(str_replace(',', '.', $number));
        return $text;
    }
}
