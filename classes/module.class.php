<?php
namespace RAAS\CMS\Shop;

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
            default:
                return parent::__get($var);
                break;
        }
    }

}