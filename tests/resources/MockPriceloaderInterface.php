<?php
/**
 * Тестовый загрузчик прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

class MockPriceloaderInterface extends PriceloaderInterface
{
    public function upload(
        string $file,
        string $type,
        Page $page,
        bool $test = true,
        int $clear = 0,
        int $rows = 0,
        int $cols = 0
    ): array {
        return [
            "Loader" => $this->loader,
            "file" => $file,
            "Page" => $page,
            "test" => $test,
            "clear" => $clear,
            "rows" => $rows,
            "cols" => $cols,
        ];
    }


    public function download(
        Page $page = null,
        int $rows = 0,
        int $cols = 0,
        string $type = 'xls',
        string $encoding = 'UTF-8',
        bool $debug = false
    ) {
        return [
            "Loader" => $this->loader,
            "Page" => $page,
            "rows" => $rows,
            "cols" => $cols,
            "type" => $type,
            "encoding" => $encoding,
        ];
    }
}
