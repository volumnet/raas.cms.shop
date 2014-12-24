<?php
namespace RAAS\CMS\Shop;

class ViewSub_Imageloaders extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    public function main(array $IN = array())
    {
        $this->assignVars($IN);
        $this->title = $this->_('IMAGELOADERS');
        $this->js[] = $this->publicURL . '/loaders.js';
        $this->template = 'loaders.tmp.php';
    }
}