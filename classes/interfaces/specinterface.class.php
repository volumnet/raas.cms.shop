<?php
/**
 * Файл интерфейса спецпредложений
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\MaterialInterface;
use RAAS\CMS\Block_Material;
use RAAS\CMS\Page;

/**
 * Интерфейс спецпредложений
 */
class SpecInterface extends MaterialInterface
{
    public $type = '';

    public function process()
    {
        $get = $this->getAllParams($this->block, $this->get);
        parse_str(trim($this->block->params), $params);
        $this->type = $params['type'];
        $result = $this->processList($this->block, $this->page, $get);
        return $result;
    }


    public function getMaterialsSQL(
        Block_Material $block,
        Page $page,
        array &$sqlFrom,
        array &$sqlWhere,
        array &$sqlWhereBind
    ) {
        $sqlWhere[] = " tM.vis";
        $sqlWhere[] = " (NOT tM.show_from OR tM.show_from <= NOW())";
        $sqlWhere[] = " (NOT tM.show_to OR tM.show_to >= NOW())";
        $sqlMaterialTypeSelfAndChildrenIds = implode(
            ", ",
            array_fill(0, count($block->Material_Type->selfAndChildrenIds), "?")
        );
        $sqlWhere[] = " tM.pid IN (" . $sqlMaterialTypeSelfAndChildrenIds . ") ";
        $sqlWhereBind = array_merge(
            $sqlWhereBind,
            $block->Material_Type->selfAndChildrenIds
        );
    }


    public function getOrderSQL(
        Block_Material $block,
        array $get,
        array &$sqlFrom,
        array &$sqlFromBind,
        &$sqlSort,
        &$sqlOrder
    ) {
        if ($this->type == 'popular') {
            $sqlSort = "(SELECT COUNT(*) FROM cms_shop_orders_goods WHERE material_id = tM.id)";
            $sqlOrder = "DESC";
        } else {
            parent::getOrderSQL(
                $block,
                $get,
                $sqlFrom,
                $sqlFromBind,
                $sqlSort,
                $sqlOrder
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
                  .  " GROUP BY tM.id ";
        if ($sqlSort == "RAND()") {
            $sqlQuery .= " ORDER BY RAND()";
        } else {
            $sqlQuery .= " ORDER BY ";
            if ($sqlSort) {
                $sqlQuery .= $sqlSort . ($sqlOrder ? " " . $sqlOrder : "") . ", ";
            }
            $sqlQuery .= "NOT tM.priority, tM.priority ASC";
        }
        return $sqlQuery;
    }
}
