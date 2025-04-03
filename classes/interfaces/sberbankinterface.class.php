<?php
/**
 * Файл класса интерфейса электронной оплаты через Сбербанк России
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

/**
 * Класс интерфейса электронной оплаты через Сбербанк России
 */
class SberbankInterface extends SberbankAlfaInterface
{
    const EPAY_URN = 'sberbank';

    const BANK_NAME = 'Сбербанк';

    public function getURL(bool $isTest = false): string
    {
        if ($isTest) {
            $url = 'https://3dsec.sberbank.ru/payment/rest/';
        } else {
            $url = 'https://securepayments.sberbank.ru/payment/rest/';
        }
        return $url;
    }
}
