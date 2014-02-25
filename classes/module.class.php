<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Block_Type;
use \RAAS\CMS\Field;
use \RAAS\CMS\Form_Field;

class Module extends \RAAS\Module
{
    protected static $instance;

    public function __get($var)
    {
        switch ($var) {
            case 'formTemplateFile':
                return $this->resourcesDir . '/form_fields.php';
                break;
            case 'stdFormTemplate':
                $text = file_get_contents($this->formTemplateFile);
                return $text;
            case 'stdPriceLoaderInterfaceFile':
                return $this->resourcesDir . '/priceloader_interface.php';
                break;
            case 'stdPriceLoaderInterface':
                $text = file_get_contents($this->stdPriceLoaderInterfaceFile);
                return $text;
                break;
            case 'stdImageLoaderInterfaceFile':
                return $this->resourcesDir . '/imageloader_interface.php';
                break;
            case 'stdImageLoaderInterface':
                $text = file_get_contents($this->stdImageLoaderInterfaceFile);
                return $text;
                break;
            case 'stdCartInterfaceFile':
                return $this->resourcesDir . '/cart_interface.php';
                break;
            case 'stdCartInterface':
                $text = file_get_contents($this->stdCartInterfaceFile);
                return $text;
                break;
            case 'stdCartViewFile':
                return $this->resourcesDir . '/cart.tmp.php';
                break;
            case 'stdCartView':
                $text = file_get_contents($this->stdCartViewFile);
                return $text;
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function registerBlockTypes()
    {
        Block_Type::registerType('RAAS\\CMS\\Shop\\Block_Cart', 'RAAS\\CMS\\Shop\\ViewBlockCart', 'RAAS\\CMS\\Shop\\EditBlockCartForm');
    }


    public function orders()
    {
        $Parent = new Cart_Type(isset($this->controller->nav['id']) ? (int)$this->controller->nav['id'] : 0);
        $col_where = "classname = 'RAAS\\\\CMS\\\\Form' AND show_in_table";        
        $SQL_query = "SELECT SQL_CALC_FOUND_ROWS tOr.*, 
                             (SELECT SUM(tOG.amount) FROM " . Order::_dbprefix() . "cms_shop_orders_goods AS tOG WHERE tOG.order_id = tOr.id) AS c, 
                             (SELECT SUM(tOG.realprice * tOG.amount) FROM " . Order::_dbprefix() . "cms_shop_orders_goods AS tOG WHERE tOG.order_id = tOr.id) AS total_sum
                        FROM " . Order::_tablename() .  " AS tOr
                   LEFT JOIN " . Cart_Type::_tablename() . " AS tCT ON tCT.id = tOr.pid 
                   LEFT JOIN " . Field::_tablename() .  " AS tFi ON tFi.pid = tCT.form_id AND tFi.classname = 'RAAS\\\\CMS\\\\Form'
                   LEFT JOIN " . Order::_dbprefix() . "cms_data AS tD ON tD.pid = tOr.id AND tD.fid = tFi.id
                       WHERE 1 ";
        if ($Parent->id) {
            $SQL_query .= " AND tOr.pid = " . (int)$Parent->id;
            $col_where .= " AND pid = " . (int)$Parent->Form->id;
        }
        if (isset($this->controller->nav['search_string']) && $this->controller->nav['search_string']) {
            $SQL_query .= " AND tD.value LIKE '%" . $this->SQL->escape_like($this->controller->nav['search_string']) . "%' ";
        }
        
        $SQL_query .= " GROUP BY tOr.id ORDER BY tOr.post_date DESC ";
        $Pages = new \SOME\Pages(isset($this->controller->nav['page']) ? $this->controller->nav['page'] : 1, $this->registryGet('rowsPerPage'));
        $Set = Order::getSQLSet($SQL_query, $Pages);
        $columns = Form_Field::getSet(array('where' => $col_where));
        return array('Set' => $Set, 'Pages' => $Pages, 'Parent' => $Parent, 'columns' => $columns);
    }


    
}