<?php
/**
 * Виджет подтверждения оплаты при использовании платежной системы "Робокасса"
 * @param array<string[] ID# блока => bool> $success Успешное завершение
 * @param array<string[] URN поля => string Описание ошибки> $localError Ошибки
 * @param Block_Cart $Block Текущий блок
 * @param Page $Page Текущая страница
 * @param Order $Item Текущий заказ
 * @param bool $requestForPayment Запрос на оплату
 * @param string $paymentURL URL для оплаты
 * @param string $crc Подпись
 *
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

if ($success[(int)$Block->id] || $localError) {
    ?>
    <div class="notifications">
      <?php if ($success[(int)$Block->id]) { ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success[(int)$Block->id])?></div>
      <?php } elseif ($localError) { ?>
          <div class="alert alert-danger">
            <ul>
              <?php foreach ((array)$localError as $key => $val) { ?>
                  <li><?php echo htmlspecialchars($val)?></li>
              <?php } ?>
            </ul>
          </div>
      <?php } ?>
    </div>
<?php }
if ($Item->id) { ?>
    <h2 class="h2">
      <?php echo htmlspecialchars(sprintf(
          ORDER_NUM,
          (int)$Item->id,
          $_SERVER['HTTP_HOST']
      ))?>
    </h2>
    <?php Snippet::importByURN('order')->process(['order' => $Item]); ?>
    <?php if ($requestForPayment) { ?>
        <form action="<?php echo htmlspecialchars($paymentURL)?>" method="post" enctype="multipart/form-data" class="cart">
          <div class="form-horizontal">
            <div class="form-group">
              <?php if ($Block->epay_test) { ?>
                <input type="hidden" name="IsTest" value="1" />
              <?php } ?>
              <input type="hidden" name="MrchLogin" value="<?php echo htmlspecialchars($Block->epay_login)?>" />
              <input type="hidden" name="OutSum" value="<?php echo number_format((float)$Item->sum, 2, '.', '')?>" />
              <input type="hidden" name="InvId" value="<?php echo (int)$Item->id?>" />
              <input type="hidden" name="Desc" value="<?php echo sprintf(ORDER_NUM, (int)$Item->id, $_SERVER['HTTP_HOST'])?>" />
              <input type="hidden" name="SignatureValue" value="<?php echo htmlspecialchars($crc)?>" />
              <?php if (!$Block->epay_test && $Block->epay_currency && ($Block->epay_currency != 'RUR')) { ?>
                  <input type="hidden" name="OutSumCurrency" value="<?php echo htmlspecialchars($Block->epay_currency)?>" />
              <?php } ?>
              <input type="hidden" name="Culture" value="<?php echo htmlspecialchars($Page->lang)?>" />
              <input type="hidden" name="Encoding" value="UTF-8" />
              <div class="cart-form__controls col-sm-offset-3 col-md-offset-2">
                <button class="cart-form__submit btn btn-primary" type="submit">
                  <?php echo PAY?>
                </button>
              </div>
            </div>
          </div>
        </form>
    <?php } ?>
<?php } ?>
