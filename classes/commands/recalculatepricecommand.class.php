<?php
/**
 * Пересчет цен
 */
namespace RAAS\CMS\Shop;

use RAAS\Command;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Page_Field;

/**
 * Команда пересчета цен
 */
class RecalculatePriceCommand extends Command
{
    /**
     * Наценки по категориям
     * @var array array<string[] ID# категории => float Наценка>
     */
    public $priceRatioMap = [];

    /**
     * Выполняет команду
     * @param string materialTypeURN URN типа материалов каталога продукции
     * @param string $pageDiscountFieldURN URN поля страниц "Скидка"
     * @param string $priceFieldURN URN поля товаров "Цена"
     * @param string $basePriceFieldURN URN поля товаров "Базовая (старая) цена"
     * @param string $fixPriceFieldURN URN поля товаров "Зафиксировать цену"
     * @param bool $forceUpdate Обновить даже если это не требуется
     */
    public function process(
        $materialTypeURN = 'catalog',
        $pageDiscountFieldURN = 'discount',
        $priceFieldURN = 'price',
        $basePriceFieldURN = 'price_old',
        $fixPriceFieldURN = 'fix_price',
        $forceUpdate = false
    ) {
        if (!$forceUpdate && !$this->updateNeeded()) {
            $this->controller->doLog('Data is actual');
            return;
        }
        $materialType = Material_Type::importByURN($materialTypeURN);
        if (!$materialType->id) {
            $logMessage = 'Material type "' . $materialTypeURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $page = new Page();
        $pageDiscountField = $page->fields[$pageDiscountFieldURN];
        if (!$pageDiscountField->id) {
            $logMessage = 'Page field "' . $pageDiscountFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $priceField = $materialType->fields[$priceFieldURN];
        if (!$priceField->id) {
            $logMessage = 'Catalog field "' . $priceFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $basePriceField = $materialType->fields[$basePriceFieldURN];
        if (!$basePriceField->id) {
            $logMessage = 'Catalog field "' . $basePriceFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $fixPriceField = $materialType->fields[$fixPriceFieldURN];
        if (!$fixPriceField->id) {
            $logMessage = 'Catalog field "' . $fixPriceFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }

        $this->priceRatioMap = $this->getRatios();

        $sqlQuery = "SELECT tM.id,
                            IFNULL(tBasePrice.value, 0) AS baseprice,
                            (
                                SELECT GROUP_CONCAT(pid SEPARATOR ',')
                                  FROM cms_materials_pages_assoc
                                 WHERE id = tM.id
                            ) AS pages_ids
                       FROM " . Material::_tablename() . " AS tM
                       JOIN cms_data AS tBasePrice ON tBasePrice.pid = tM.id AND tBasePrice.fid = :basePriceFieldId AND tBasePrice.fii = 0
                  LEFT JOIN cms_data AS tFixPrice ON tFixPrice.pid = tM.id AND tFixPrice.fid = :fixPriceFieldId AND tFixPrice.fii = 0
                      WHERE NOT IFNULL(tFixPrice.value, 0)
                   GROUP BY tM.id
                   ORDER BY tM.id";
        // echo $sqlQuery; exit;
        $sqlBind = [
            'basePriceFieldId' => (int)$basePriceField->id,
            'fixPriceFieldId' => (int)$fixPriceField->id,
        ];
        $sqlResult = Material::_SQL()->get([$sqlQuery, $sqlBind]);
        // var_dump($sqlResult); exit;
        $sqlArr = [];
        foreach ($sqlResult as $sqlRow) {
            // if ($sqlRow['id'] == 300) {
            //     var_dump($sqlRow); exit;
            // }
            $price = $this->calculateNewPrice(
                (float)$sqlRow['baseprice'],
                explode(',', $sqlRow['pages_ids'])
            );
            $sqlArr[] = [
                'pid' => (int)$sqlRow['id'],
                'fid' => (int)$priceField->id,
                'fii' => 0,
                'value' => ceil($price),
            ];
        }
        // foreach ($sqlArr as $sqlRow) {
        //     if ($sqlRow['pid'] == 300) {
        //         var_dump($sqlRow); exit;
        //     }
        // }
        // var_dump($sqlArr); exit;
        if ($sqlArr) {
            Material::_SQL()->add('cms_data', $sqlArr);
        }
    }


    /**
     * Получает данные по наценкам
     * @param array <pre><code>array<
     *     string[] ID# страницы => float Скидка в %%
     * ></code></pre>
     */
    public function getRatios(Page_Field $pageDiscountField)
    {
        $priceRatioMap = [];
        // 1. Получим базу по коэффициентам цены
        $sqlQuery = "SELECT *
                       FROM cms_data
                      WHERE fid = ?
                        AND value
                   ORDER BY pid";
        $sqlBind = [(int)$pageDiscountField->id];
        $sqlResult = Material::_SQL()->get([$sqlQuery, $sqlBind]);
        foreach ($sqlResult as $sqlRow) {
            if ($sqlRow['value']) {
                $priceRatioMap[trim($sqlRow['pid'])] = (int)$sqlRow['value'];
            }
        }

        // 2. Найдем маппинг к точкам задания коэффициентов цены
        $ch = PageRecursiveCache::i()->getChildrenIds(0);
        do {
            foreach ($ch as $childId) {
                $childData = PageRecursiveCache::i()->cache[$childId];
                if (!isset($priceRatioMap[$childId]) &&
                    isset($priceRatioMap[$childData['pid']])
                ) {
                    $priceRatioMap[$childId] = $priceRatioMap[$childData['pid']];
                }
            }
        } while ($ch = PageRecursiveCache::i()->getChildrenIds($ch));

        return $priceRatioMap;
    }


    /**
     * Получает наценку по собственному коэфиициенту цены и набору страниц
     * (минимальную или по умолчанию), либо по умолчанию
     * @param float $basePrice Базовая цена
     * @param int[] $pagesIds Набор страниц
     * @return float
     */
    public function calculateNewPrice($basePrice, array $pagesIds = [])
    {
        $prices = [];
        foreach ((array)$pagesIds as $pageId) {
            $price = $basePrice;
            if ($discount = (float)$this->priceRatioMap[$pageId]) {
                $price *= (100. - (float)$discount) / 100.;
                $price = max(0, ceil($price));
                $prices[] = $price;
            }
        }
        if ($prices) {
            return min($prices);
        }
        return $basePrice;
    }


    /**
     * Определяет, требуется ли обновление
     * @return bool
     */
    public function updateNeeded()
    {
        $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                       FROM " . Material::_tablename()
                  . " WHERE 1";
        $lastModifiedMaterialTimestamp = Material::_SQL()->getvalue($sqlQuery);
        $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                       FROM " . Page::_tablename()
                  . " WHERE 1";
        $lastModifiedPageTimestamp = Material::_SQL()->getvalue($sqlQuery);
        $lastModified = max(
            $lastModifiedMaterialTimestamp,
            $lastModifiedPageTimestamp
        );

        $cacheUpdated = strtotime(Module::i()->registryGet('prices_recalculated'));
        return $lastModified > $cacheUpdated;
    }
}
