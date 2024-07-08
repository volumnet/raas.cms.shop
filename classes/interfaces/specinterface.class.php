<?php
/**
 * Файл интерфейса спецпредложений
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\MaterialInterface;
use RAAS\CMS\Block_Material;
use RAAS\CMS\DiagTimer;
use RAAS\CMS\Page;

/**
 * Интерфейс спецпредложений
 */
class SpecInterface extends CatalogInterface
{
    public function getOrderSQL(
        Block_Material $block,
        array $get,
        array &$sqlFrom,
        array &$sqlFromBind,
        &$sqlSort,
        &$sqlOrder,
        array $filterIds = []
    ) {
        $additionalParams = $block->additionalParams;
        if (($additionalParams['type'] ?? null) == 'popular') {
            $sqlSort = "(SELECT COUNT(*) FROM cms_shop_orders_goods WHERE material_id = tM.id)";
            $sqlOrder = "DESC";
        } else {
            parent::getOrderSQL(
                $block,
                $get,
                $sqlFrom,
                $sqlFromBind,
                $sqlSort,
                $sqlOrder,
                $filterIds
            );
        }
    }


    public function getSQLQuery(
        array $sqlFrom,
        array $sqlWhere,
        $sqlSort = '',
        $sqlOrder = '',
        $idsOnly = false
    ) {
        if ($idsOnly) {
            $sqlQuery = "SELECT tM.id";
        } else {
            $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS tM.* ";
        }
        $sqlQuery .=  " FROM " . Material::_tablename() . " AS tM "
                  .  implode(" ", $sqlFrom)
                  .  ($sqlWhere ? " WHERE " . implode(" AND ", $sqlWhere) : "")
                  .  " GROUP BY tM.id
                       ORDER BY ";
        if ($sqlSort) {
            $sqlQuery .= $sqlSort . ($sqlOrder ? " " . $sqlOrder : "") . ", ";
        }
        $sqlQuery .= "NOT tM.priority, tM.priority ASC";
        return $sqlQuery;
    }


    public function setCatalogFilter(Block_Material $block, Page $page, array $get = [])
    {
        if (!$page->originalFilter) {
            $t1 = new DiagTimer(
                __CLASS__ . '::' . __FUNCTION__ . '::getOriginalFilter'
            );
            $withChildrenGoods = true;
            $classname = static::FILTER_CLASS;
            $catalogFilter = $classname::loadOrBuild(
                $block->Material_Type,
                $withChildrenGoods,
                []
            );
            $page->originalFilter = $catalogFilter;
            $t1->stop();
        }
        $t2 = new DiagTimer(
            __CLASS__ . '::' . __FUNCTION__ . '::applyFilter'
        );
        $get = $this->getAllParams($block, $get);
        $page->catalogFilter = clone $page->originalFilter;
        $filterParams = $this->getFilterParams($block, $page->catalogFilter, $get);
        $page->catalogFilter->apply($page, $filterParams);
        $t2->stop();
    }
}
