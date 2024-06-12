<?php
/**
 * Тестовый загрузчик изображений
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

class MockImageloaderInterface extends ImageloaderInterface
{
    public function upload(array $files, bool $test = true, bool $clear = false): array
    {
        return [
            "Loader" => $this->loader,
            "files" => $files,
            "test" => $test,
            "clear" => $clear,
        ];
    }


    public function download() {
        return [
            "Loader" => $this->loader,
        ];
    }
}
