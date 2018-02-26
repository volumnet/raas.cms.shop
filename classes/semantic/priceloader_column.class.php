<?php
namespace RAAS\CMS\Shop;

class PriceLoader_Column extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_priceloaders_columns';
    protected static $defaultOrderBy = "priority";
    protected static $cognizableVars = array('Callback', 'CallbackDownload');
    protected static $references = array(
        'Parent' => array('FK' => 'pid', 'classname' => 'RAAS\\CMS\\Shop\\PriceLoader', 'cascade' => true),
        'Field' => array('FK' => 'fid', 'classname' => 'RAAS\\CMS\\Material_Field', 'cascade' => true),
    );

    public function _Callback()
    {
        $t = $column = $this;
        if (trim($this->callback)) {
            $f = $this->callback;
            return function($x) use ($column, $f) { return eval($f); };
        }
    }


    public function _CallbackDownload()
    {
        $t = $column = $this;
        if (trim($this->callback_download)) {
            $f = $this->callback_download;
            return function($x, $row) use ($f, $column) { return eval($f); };
        }
    }
}
