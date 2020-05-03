<?php
namespace RAAS\CMS\Shop;

use SOME\EventProcessor;
use SOME\Pages;
use SOME\SOME;
use RAAS\Application;
use RAAS\Module as RAASModule;
use RAAS\CMS\Block_Type;
use RAAS\CMS\Field;
use RAAS\CMS\Form;
use RAAS\CMS\Form_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

class Module extends RAASModule
{
    protected static $instance;

    public function __get($var)
    {
        switch ($var) {
            default:
                return parent::__get($var);
                break;
        }
    }


    public function init()
    {
        EventProcessor::on(
            SOME::class . ':commit:commit',
            Page::class,
            [Block_YML::class, 'pageCommitEventListener']
        );
        EventProcessor::on(
            Package::class . ':clearCache:clearCache',
            Package::class,
            [CatalogFilter::class, 'clearCaches']
        );
        parent::init();
    }


    public function registerBlockTypes()
    {
        Block_Type::registerType(
            Block_Cart::class,
            ViewBlockCart::class,
            EditBlockCartForm::class
        );
        Block_Type::registerType(
            Block_YML::class,
            ViewBlockYML::class,
            EditBlockYMLForm::class
        );
    }


    public function orders()
    {
        $cartType = new Cart_Type(
            isset($this->controller->nav['id']) ?
            (int)$this->controller->nav['id'] :
            0
        );
        $sqlQuery = "SELECT SQL_CALC_FOUND_ROWS tOr.*,
                            (
                                SELECT SUM(tOG.amount)
                                  FROM " . Order::_dbprefix() . "cms_shop_orders_goods AS tOG
                                 WHERE tOG.order_id = tOr.id
                            ) AS c,
                            (
                                SELECT SUM(tOG.realprice * tOG.amount)
                                  FROM " . Order::_dbprefix() . "cms_shop_orders_goods AS tOG
                                 WHERE tOG.order_id = tOr.id
                            ) AS total_sum
                       FROM " . Order::_tablename() .  " AS tOr
                  LEFT JOIN " . Cart_Type::_tablename() . " AS tCT ON tCT.id = tOr.pid
                  LEFT JOIN " . Field::_tablename() .  " AS tFi ON tFi.pid = tCT.form_id AND tFi.classname = ?
                  LEFT JOIN " . Order::_dbprefix() . "cms_data AS tD ON tD.pid = tOr.id AND tD.fid = tFi.id
                      WHERE 1 ";
        $sqlBind = [Form::class];
        $columns = [];
        if ($cartType->id) {
            $sqlQuery .= " AND tOr.pid = ?";
            $sqlBind[] = (int)$cartType->id;

            $columns = Form_Field::getSet(['where' => [
                "classname = 'RAAS\\\\CMS\\\\Form'",
                "show_in_table",
                "pid = " . (int)$cartType->Form->id
            ]]);
        }
        if (isset($this->controller->nav['search_string']) &&
            $this->controller->nav['search_string']
        ) {
            $searchString = $this->controller->nav['search_string'];
            $likeSearchString = '%' . $searchString . '%';
            $sqlQuery .= " AND (
                                    (tOr.id = ?)
                                 OR (tOr.ip LIKE ?)
                                 OR (tD.value LIKE ?)
                            ) ";
            $sqlBind[] = $searchString;
            $sqlBind[] = $likeSearchString;
            $sqlBind[] = $likeSearchString;
        }
        if (isset($this->controller->nav['status_id']) &&
            ((string)$this->controller->nav['status_id'] !== '')
        ) {
            $sqlQuery .= " AND tOr.status_id = ?";
            $sqlBind[] = (int)$this->controller->nav['status_id'];
        }
        if (isset($this->controller->nav['paid']) &&
            ($paid = $this->controller->nav['paid'])
        ) {
            $sqlQuery .= " AND " . ($paid > 0 ? "" : "NOT") . " tOr.paid";
        }
        if (isset($this->controller->nav['from']) &&
            $this->controller->nav['from']
        ) {
            $t = strtotime($this->controller->nav['from']);
            if ($t > 0) {
                $sqlQuery .= " AND tOr.post_date >= ?";
                $sqlBind[] = date('Y-m-d H:i:s', $t);
            }
        }
        if (isset($this->controller->nav['to']) &&
            $this->controller->nav['to']
        ) {
            $t = strtotime($this->controller->nav['to']);
            if ($t > 0) {
                $sqlQuery .= " AND tOr.post_date <= ?";
                $sqlBind[] = date('Y-m-d H:i:s', $t);
            }
        }

        $sqlQuery .= " GROUP BY tOr.id
                       ORDER BY tOr.post_date DESC ";

        $pageNum = isset($this->controller->nav['page'])
                 ? $this->controller->nav['page']
                 : 1;
        $pages = new Pages(
            $pageNum,
            Application::i()->registryGet('rowsPerPage')
        );
        $set = Order::getSQLSet([$sqlQuery, $sqlBind], $pages);
        return [
            'Set' => $set,
            'Pages' => $pages,
            'Parent' => $cartType,
            'columns' => $columns
        ];
    }
}
