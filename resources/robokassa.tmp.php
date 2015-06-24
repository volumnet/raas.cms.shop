<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\CMS\Package;

if ($success[(int)$Block->id] || $localError) { 
    ?>
    <div class="notifications">
      <?php if ($success[(int)$Block->id]) { ?>
          <div class="alert alert-success"><?php echo ORDER_SUCCESSFULLY_SENT?></div>
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
      <h2><?php echo htmlspecialchars(sprintf(ORDER_NUM, (int)$Item->id))?></h2>
      <table class="table table-striped cart-table" data-role="cart-table">
        <thead>
          <tr>
            <th class="span1 cart-table__image-col"><?php echo IMAGE?></th>
            <th class="span7 cart-table__name-col"><?php echo NAME?></th>
            <th class="span1 cart-table__price-col"><?php echo PRICE?></th>
            <th class="span1 cart-table__amount-col"><?php echo AMOUNT?></th>
            <th class="span1 cart-table__sum-col"><?php echo SUM?></th>
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
              <td data-role="price" class="span1 cart-table__price-col" style="white-space: nowrap">
                <?php echo formatPrice($row->realprice)?> <span class="fa fa-rub"></span>
              </td>
              <td class="span1 cart-table__amount-col"><?php echo (int)$row->amount?></td>
              <td class="span1 cart-table__sum-col" style="white-space: nowrap"><span data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></span> <span class="fa fa-rub"></span></td>
            </tr>
          <?php } ?>
          <tr>
            <th colspan="3"><?php echo TOTAL_SUM?>:</th>
            <th class="cart-table__amount-col"><span data-role="total-amount"><?php echo (int)$Item->count; ?></span></td>
            <th class="cart-table__sum-col" style="white-space: nowrap"><span data-role="total-sum"><?php echo formatPrice($Item->sum)?></span>&nbsp;<span class="fa fa-rub"></span></th>
          </tr>
        </tbody>
      </table>
      <div class="form-horizontal">
        <div data-role="feedback-form">
          <div class="form-group">
            <label class="control-label col-sm-3 col-md-2"><?php echo YOUR_ORDER_ID?></label>
            <div class="col-sm-9 col-md-4"><strong><?php echo (int)$Item->id?></strong></div>
          </div>
          <?php foreach ($Item->fields as $row) { ?>
              <div class="form-group">
                <label class="control-label col-sm-3 col-md-2"><?php echo htmlspecialchars($row->name)?></label>
                <div class="col-sm-9 col-md-4"><?php echo htmlspecialchars($Item->fields[$row->urn]->doRich())?></div>
              </div>
          <?php } ?>
          <?php if ($requestForPayment) { ?>
              <div class="form-group">
                <form action="<?php echo htmlspecialchars($paymentURL)?>" method="post" enctype="multipart/form-data">
                  <input type="hidden" name="MrchLogin" value="<?php echo htmlspecialchars($Block->epay_login)?>" />
                  <input type="hidden" name="OutSum" value="<?php echo number_format((float)$Item->sum, 2, '.', '')?>" />
                  <input type="hidden" name="InvId" value="<?php echo (int)$Item->id?>" />
                  <input type="hidden" name="Desc" value="<?php echo sprintf(ORDER_NUM, (int)$Item->id, $_SERVER['HTTP_SERVER'])?>" />
                  <input type="hidden" name="SignatureValue" value="<?php echo htmlspecialchars($crc)?>" />
                  <?php if (!$Block->epay_test && $Block->epay_currency && ($Block->epay_currency != 'RUR')) { ?>
                      <input type="hidden" name="OutSumCurrency" value="<?php echo htmlspecialchars($Block->epay_currency)?>" />
                  <?php } ?>
                  <input type="hidden" name="Culture" value="<?php echo htmlspecialchars($Page->lang)?>" />
                  <input type="hidden" name="Encoding" value="UTF-8" />
                  <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2"><button class="btn btn-default" type="submit"><?php echo PAY?></button></div>
                </form>
              </div>
          <?php } ?>
        </div>
      </div>
    </section>
<?php } ?>