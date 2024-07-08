<?php
/**
 * Интерфейс Робокассы
 * @deprecated 2024-07-04
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Snippet;
use RAAS\Application;

if (in_array($_GET['action'], array('result', 'success', 'fail')) && $_REQUEST['InvId']) {
    // Подписанное значение - либо RESULT URL, либо SUCCESS URL, либо FAIL URL
    $inv_id = $_REQUEST['InvId'];
    $Item = new Order($inv_id);
    $crc = isset($_REQUEST['SignatureValue']) ? strtoupper($_REQUEST['SignatureValue']) : null;
    switch ($_GET['action']) {
        case 'result':
            while (ob_get_level()) {
                ob_end_clean();
            }
            $my_crc = strtoupper(md5($_REQUEST['OutSum'] . ':' . $inv_id . ':' . $Block->epay_pass2));
            if ($my_crc != $crc) {
                echo 'Invalid signature';
            } elseif (!$Item->id) {
                echo 'Invalid order ID#';
            } else {
                // Все ок
                $history = new Order_History();
                $history->uid = Application::i()->user->id;
                $history->order_id = (int)$Item->id;
                $history->status_id = (int)$Item->status_id;
                $history->paid = 1;
                $history->post_date = date('Y-m-d H:i:s');
                $history->description = PAID_VIA_ROBOKASSA;
                $history->commit();

                $Item->paid = 1;
                $Item->commit();
                echo 'OK' . (int)$Item->id;
            }
            exit;
            break;
        case 'success':
            $my_crc = strtoupper(md5($_REQUEST['OutSum'] . ':' . $inv_id . ':' . $Block->epay_pass1));
            if ($Item->id) {
                $OUT['epayWidget'] = Snippet::importByURN('robokassa');
                $OUT['Item'] = $Item;
                if ($crc == $my_crc) {
                    $OUT['success'][(int)$Block->id] = sprintf(ORDER_SUCCESSFULLY_PAID, $Item->id);
                } else {
                    $OUT['localError'] = array('crc' => INVALID_CRC);
                }
            }
            break;
        case 'fail':
            if ($Item->id) {
                $OUT['epayWidget'] = Snippet::importByURN('robokassa');
                $OUT['Item'] = $Item;
                $OUT['localError'] = array('order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $Item->id));
            }
            break;
    }
} elseif ($Item->id && $_POST['epay']) {
    $OUT['epayWidget'] = Snippet::importByURN('robokassa');
    $OUT['paymentURL'] = $Block->epay_test ?
        'https://auth.robokassa.ru/Merchant/Index.aspx?IsTest=1' :
        'https://auth.robokassa.ru/Merchant/Index.aspx';
    $OUT['requestForPayment'] = true;
    $crc = $Block->epay_login . ':' . number_format($Item->sum, 2, '.', '') . ':' . (int)$Item->id;
    if (!$Block->epay_test && $Block->epay_currency && ($Block->epay_currency != 'RUR')) {
        $crc .= ':' . $Block->epay_currency;
    }
    $crc .= ':' . $Block->epay_pass1;
    $crc = md5($crc);
    $OUT['crc'] = $crc;
}
