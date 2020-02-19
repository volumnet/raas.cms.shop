<?php
/**
 * Виджет оплаты для Уральского банка реконструкции и развития
 * @param array<string[] ID# блока => bool> $success Успешное завершение
 * @param array<string[] URN поля => string Описание ошибки> $localError Ошибки
 * @param Block_Cart $Block Текущий блок
 * @param Page $Page Текущая страница
 * @param Order $Item Текущий заказ
 * @param bool $requestForPayment Запрос на оплату
 * @param string $paymentURL URL для оплаты
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\Package;

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
<?php } ?>
<?php if ($Item->id) { ?>
    <h2 class="h2">
      <?php echo htmlspecialchars(sprintf(
          ORDER_NUM,
          (int)$Item->id,
          $_SERVER['HTTP_HOST']
      ))?>
    </h2>
    <?php Snippet::importByURN('order')->process(['order' => $Item]); ?>
    <?php if ($requestForPayment) { ?>
        <div class="form-horizontal">
          <div class="form-group">
            <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2">
              <a class="cart-form__submit btn btn-primary" href="<?php echo htmlspecialchars($paymentURL)?>">
                <?php echo PAY?>
              </a>
            </div>
          </div>
        </div>
    <?php } ?>
<?php } ?>
