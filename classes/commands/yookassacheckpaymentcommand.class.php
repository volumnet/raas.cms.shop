<?php
/**
 * Команда проверки оплаты ЮКаssа
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

/**
 * Команда проверки оплаты ЮКаssа
 */
class YooKassaCheckPaymentCommand extends EPayCheckPaymentCommand
{
    const PAYMENT_URL_TO_FIND = '%yookassa%';

    public function getInterface(Block_Cart $block)
    {
        return new YooKassaInterface($block);
    }
}
