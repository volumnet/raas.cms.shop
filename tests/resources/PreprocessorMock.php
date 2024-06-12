<?php
/**
 * Тестовый препроцессор
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\FilesProcessorInterface;

class PreprocessorMock extends FilesProcessorInterface
{
    public function process(array $files = [])
    {
        $GLOBALS["preprocessorData"][] = $files;
    }
}
