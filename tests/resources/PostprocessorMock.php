<?php
/**
 * Тестовый постпроцессор
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\FilesProcessorInterface;

class PostprocessorMock extends FilesProcessorInterface
{
    public function process(array $files = [])
    {
        $GLOBALS["postprocessorData"][] = $files;
    }
}
