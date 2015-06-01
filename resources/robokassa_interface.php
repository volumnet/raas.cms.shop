<?php
use \RAAS\CMS\Snippet;
use \RAAS\Application;
use \RAAS\CMS\Shop\Order;
use \RAAS\CMS\Shop\Order_History;

if ($_REQUEST['SignatureValue']) {
    // Подписанное значение - либо RESULT URL, либо SUCCESS URL, либо FAIL URL
    $inv_id = $_REQUEST['InvId'];
    $Item = new Order($inv_id);
    $crc = strtoupper($_REQUEST['SignatureValue']);
    if ($_GET['action'] == 'result') {
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
    } elseif (($_GET['action'] == 'success') || ($_GET['action'] == 'fail')) {
        $my_crc = strtoupper(md5($_REQUEST['OutSum'] . ':' . $inv_id . ':' . $Block->epay_pass1));
        if ($Item->id) {
            $OUT['epayWidget'] = Snippet::importByURN('robokassa');
            $OUT['Item'] = $Item;
            if ($crc == $my_crc) {
                if ($_GET['action'] == 'success') {
                    $OUT['success'][(int)$Block->id] = sprintf(ORDER_SUCCESSFULLY_PAID, $Item->id);
                } elseif ($_GET['action'] == 'fail') {
                    $OUT['localError'] = array('order' => sprintf(ORDER_HAS_NOT_BEEN_PAID, $Item->id))
                }
            } else {
                $OUT['localError'] = array('crc' => INVALID_CRC);
            }
        }
    }
} elseif ($Item->id) {
    $OUT['epayWidget'] = Snippet::importByURN('robokassa');
    $OUT['paymentURL'] = $Block->epay_test ? 'http://test.robokassa.ru/Index.aspx' : 'https://auth.robokassa.ru/Merchant/Index.aspx';
    $OUT['requestForPayment'] = true;
    $OUT['crc'] = $Block->epay_login . ':' . number_format($Item->sum, 2, '.', '') . ':' . (int)$Item->id . ':' . $Block->epay_currency . ':' . $Block->epay_pass1;
}