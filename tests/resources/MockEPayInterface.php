<?php
/**
 * Тестовый платежный интерфейс
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

class MockEPayInterface extends EPayInterface
{
    public function process(?Order $order = null): array
    {
        $result = [];
        $result['success'][(string)$this->block->id] = true;
        return $result;
    }

    public function getURL(bool $isTest = false): string
    {
        return '';
    }


    public function getRegisterOrderData(Order $order, Block_Cart $block, Page $page): array
    {
        return [];
    }


    public function registerOrderWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        return [];
    }


    public function parseResponseCommonErrors(array $response): array
    {
        return [];
    }


    public function parseInitResponse(array $response): array
    {
        return [];
    }


    public function getOrderStatusWithData(Order $order, Block_Cart $block, Page $page, array $data): array
    {
        return [];
    }


    public function parseOrderStatusResponse(array $response): array
    {
        return [];
    }


    public function exec(string $method, array $requestData = [], bool $isTest = false): array
    {
        return [];
    }
}
