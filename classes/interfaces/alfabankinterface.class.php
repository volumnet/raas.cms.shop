<?php
/**
 * Файл класса интерфейса электронной оплаты через Альфа-Банк
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

/**
 * Класс интерфейса электронной оплаты через Альфа-Банк
 */
class AlfaBankInterface extends SberbankAlfaInterface
{
    const EPAY_URN = 'alfabank';

    const BANK_NAME = 'Альфа-Банк';

    public function getURL(bool $isTest = false): string
    {
        if ($isTest) {
            $url = 'https://alfa.rbsuat.com/payment/rest/';
        } else {
            $url = 'https://payment.alfabank.ru/payment/rest/';
        }
        return $url;
    }
}
