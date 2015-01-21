<?php
namespace RAAS\CMS\Shop;

class ViewSub_Priceloaders extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    public function main(array $IN = array())
    {
        $IN['downloadMenu'] = $this->getDownloadContextMenu();
        $this->assignVars($IN);
        $this->title = $this->_('PRICELOADERS');
        $this->js[] = $this->publicURL . '/loaders.js';
        $this->template = 'loaders.tmp.php';
    }

    public function getDownloadContextMenu()
    {
        $arr = array();
        $arr[] = array('data-href' => $this->url . '&action=download&type=xlsx', 'name' => $this->_('EXCEL2007'));
        $arr[] = array('data-href' => $this->url . '&action=download&type=xls', 'name' => $this->_('EXCEL'));
        $arr[] = array('data-href' => $this->url . '&action=download&type=csv&encoding=Windows-1251', 'name' => $this->_('CSV_WIN1251'));
        $arr[] = array('data-href' => $this->url . '&action=download&type=csv', 'name' => $this->_('CSV_UTF8'));
        return $arr;
    }
}