<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Block_Type;

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
}