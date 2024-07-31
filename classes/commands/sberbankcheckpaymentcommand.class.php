<?php
/**
 * Команда проверки оплаты Сбербанка
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

/**
 * Команда проверки оплаты Сбербанка
 */
class SberbankCheckPaymentCommand extends EPayCheckPaymentCommand
{
    const PAYMENT_URL_TO_FIND = '%securecardpayment%';

    public function getInterface(Block_Cart $block)
    {
        return new SberbankInterface($block);
    }
}
