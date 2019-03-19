<?php
namespace RAAS\CMS\Shop;

use \RAAS\CMS\Material;
use \RAAS\CMS\Package;

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
    <section class="cart">
      <h3><?php echo htmlspecialchars(sprintf(ORDER_NUM, (int)$Item->id, $_SERVER['HTTP_HOST']))?></h3>
      <table class="table table-striped cart-table" data-role="cart-table">
        <thead>
          <tr>
            <th class="cart-table__image-col"><?php echo IMAGE?></th>
            <th class="cart-table__name-col"><?php echo NAME?></th>
            <th class="cart-table__price-col"><?php echo PRICE?></th>
            <th class="cart-table__amount-col"><?php echo AMOUNT?></th>
            <th class="cart-table__sum-col"><?php echo SUM?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($Item->items as $row) { $row2 = new Material((int)$row->id); ?>
            <tr data-role="cart-item">
              <td class="text-center cart-table__image-col">
                <?php if ($row2->visImages) { ?>
                    <a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '" target="_blank"' : ''?>>
                      <img src="/<?php echo htmlspecialchars(addslashes($row2->visImages[0]->tnURL))?>" style="max-width: 48px" alt="<?php echo htmlspecialchars($row2->visImages[0]->name ?: $row->name)?>" target="_blank" /></a>
                <?php } ?>
              </td>
              <td class="cart-table__name-col">
                <a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '" target="_blank"' : ''?>><?php echo htmlspecialchars($row->name)?></a>
              </td>
              <td data-role="price" class="cart-table__price-col">
                <?php echo formatPrice($row->realprice)?> <span class="fa fa-rub"></span>
              </td>
              <td class="cart-table__amount-col"><?php echo (int)$row->amount?></td>
              <td class="cart-table__sum-col"><span data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></span> <span class="fa fa-rub"></span></td>
            </tr>
          <?php } ?>
          <?php if (($Item->delivery == 1) && ($Item->sum >= $delivery['min_sum'])) { ?>
              <tr data-role="cart-item">
                <td class="text-center cart-table__image-col">
                </td>
                <td class="cart-table__name-col" colspan="3">
                  Доставка от <?php echo (int)$delivery['min_sum']?> руб.
                </td>
                <td class="cart-table__sum-col">бесплатно</td>
              </tr>
          <?php } ?>
          <tr>
            <th colspan="3"><?php echo TOTAL_SUM?>:</th>
            <th class="cart-table__amount-col"><span data-role="total-amount"><?php echo (int)$Item->count; ?></span></td>
            <th class="cart-table__sum-col"><span data-role="total-sum"><?php echo formatPrice($Item->sum)?></span>&nbsp;<span class="fa fa-rub"></span></th>
          </tr>
        </tbody>
      </table>
      <div class="form-horizontal">
        <div data-role="feedback-form">
          <div class="form-group">
            <label class="control-label col-sm-3 col-md-2" style="padding-top: 0px;"><?php echo YOUR_ORDER_ID?>:</label>
            <div class="col-sm-9 col-md-4"><strong><?php echo (int)$Item->id?></strong></div>
          </div>
          <?php foreach ($Item->fields as $row) { ?>
              <?php if (!in_array($row->urn, array('payment_type', 'agree', 'invoice'))) { ?>
                  <div class="form-group">
                    <label class="control-label col-sm-3 col-md-2" style="padding-top: 0px;"><?php echo htmlspecialchars($row->name)?>:</label>
                    <div class="col-sm-9 col-md-4"><?php echo htmlspecialchars($Item->fields[$row->urn]->doRich())?></div>
                  </div>
              <?php } ?>
          <?php } ?>
          <?php if ($requestForPayment) { ?>
              <div class="form-group">
                <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2">
                  <a class="btn btn-primary" href="<?php echo htmlspecialchars($paymentURL)?>"><?php echo PAY?></a>
                </div>
              </div>
          <?php } ?>
        </div>
      </div>
    </section>
<?php } ?>
