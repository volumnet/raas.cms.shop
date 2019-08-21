<?php
namespace RAAS\CMS\Shop;

use \RAAS\CMS\Material;
use \RAAS\CMS\Package;

function formatPrice($price)
{
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
}

if ($_GET['AJAX']) {
    $temp = array();
    $temp['count'] = (int)$Cart->count;
    $temp['sum'] = (float)$Cart->sum;
    $temp['no_amount'] = (int)$Cart->no_amount;
    foreach ($Cart->items as $row) {
        $row2 = new Material($row->id);
        $temp['items'][] = array(
            'id' => $row->id,
            'meta' => $row->meta,
            'amount' => $row->amount,
            'price' => $row->realprice,
            'name' => $row->name,
            'url' => $row2->url,
            'image' => '/' . $row2->visImages[0]->smallURL,
            'min' => $row2->min,
        );
    }
    echo json_encode($temp);
    exit;
} elseif ($epayWidget && ($epayWidget instanceof \RAAS\CMS\Snippet)) {
    eval('?' . '>' . $epayWidget->description);
} elseif ($success[(int)$Block->id]) {
    ?>
    <div class="notifications">
      <div class="alert alert-success"><?php echo sprintf(ORDER_SUCCESSFULLY_SENT, $Item->id)?></div>
    </div>
<?php } elseif ($Cart->items) { ?>
    <div class="cart">
      <form action="#feedback" method="post" enctype="multipart/form-data">
        <div class="cart__inner">
          <table class="table table-striped cart-table" data-role="cart-table">
            <thead>
              <tr>
                <th class="cart-table__image-col"><?php echo IMAGE?></th>
                <th class="cart-table__name-col"><?php echo NAME?></th>
                <th class="cart-table__price-col"><?php echo PRICE?></th>
                <?php if (!$Cart->cartType->no_amount) { ?>
                    <th class="cart-table__amount-col"><?php echo AMOUNT?></th>
                    <th class="cart-table__sum-col"><?php echo SUM?></th>
                <?php } ?>
                <th class="cart-table__actions-col"></th>
              </tr>
            </thead>
            <tbody data-role="cart__body_main">
              <?php $sum = $am = 0; foreach ($Cart->items as $row) { $row2 = new Material((int)$row->id); ?>
                <tr data-role="cart-item" data-id="<?php echo (int)$row->id?>" data-price="<?php echo number_format($row->realprice, 2, '.', '')?>">
                  <td class="cart-table__image-col">
                    <?php if ($row2->visImages) { ?>
                        <a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '"' : ''?>>
                          <img src="/<?php echo htmlspecialchars(addslashes($row2->visImages[0]->smallURL))?>" style="max-width: 48px" alt="<?php echo htmlspecialchars($row2->visImages[0]->name ?: $row->name)?>" /></a>
                    <?php } ?>
                  </td>
                  <td class="cart-table__name-col"><a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '"' : ''?>><?php echo htmlspecialchars($row->name)?></a></td>
                  <td data-role="price" class="cart-table__price-col">
                    <?php echo formatPrice($row->realprice)?> <span class="fa fa-rub"></span>
                    <?php if ($Cart->cartType->no_amount) { ?>
                        <input type="hidden" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" />
                    <?php } ?>
                  </td>
                  <?php if (!$Cart->cartType->no_amount) { ?>
                      <td class="cart-table__amount-col"><input type="number" class="form-control" style="max-width: 8em" data-role="amount" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" min="<?php echo (int)$row2->min ?: 1?>" value="<?php echo (int)$row->amount?>" /></td>
                      <td class="cart-table__sum-col"><span data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></span> <span class="fa fa-rub"></span></td>
                  <?php } ?>
                  <td class="cart-table__actions-col">
                    <a href="?action=delete&id=<?php echo (int)$row->id . ($row->meta ? '&meta=' . htmlspecialchars($row->meta) : '')?>" data-role="delete-item">
                      <i class="fa fa-remove" title="<?php echo DELETE?>"></i>
                    </a>
                  </td>
                </tr>
              <?php $sum += $row->amount * $row->realprice; $am += $row->amount; } ?>
            </tbody>
            <tbody>
              <?php if ($Form->id) { ?>
                  <tr>
                    <th colspan="<?php echo !$Cart->cartType->no_amount ? '3' : '2'?>"><?php echo TOTAL_SUM?>:</th>
                    <?php if (!$Cart->cartType->no_amount) { ?>
                        <th class="cart-table__amount-col"><span data-role="total-amount"><?php echo (int)$am; ?></span></td>
                    <?php } ?>
                    <th class="cart-table__sum-col"><span data-role="total-sum"><?php echo formatPrice($sum)?></span>&nbsp;<span class="fa fa-rub"></span></th>
                    <th class="cart-table__actions-col"></th>
                  </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <?php if ($Form->id) {
            foreach ($Form->fields as $fieldUrn => $field) {
                if (!$DATA[$fieldUrn] && ($userVal = Controller_Frontend::i()->user->{$fieldUrn})) {
                    $DATA[$fieldUrn] = $userVal;
                }
            }
            ?>
            <div class="form-horizontal">
              <?php include Package::i()->resourcesDir . '/form2.inc.php'?>
              <div data-role="notifications" <?php echo ($success[(int)$Block->id] || $localError) ? '' : 'style="display: none"'?>>
                <div class="alert alert-success" <?php echo ($success[(int)$Block->id]) ? '' : 'style="display: none"'?>><?php echo FEEDBACK_SUCCESSFULLY_SENT?></div>
                <div class="alert alert-danger" <?php echo ($localError) ? '' : 'style="display: none"'?>>
                  <ul>
                    <?php foreach ((array)$localError as $key => $val) { ?>
                        <li><?php echo htmlspecialchars($val)?></li>
                    <?php } ?>
                  </ul>
                </div>
              </div>

              <div data-role="feedback-form" <?php echo $success[(int)$Block->id] ? 'style="display: none"' : ''?>>
                <p><?php echo ASTERISK_MARKED_FIELDS_ARE_REQUIRED?></p>
                <?php if ($Form->signature) { ?>
                      <input type="hidden" name="form_signature" value="<?php echo md5('form' . (int)$Form->id . (int)$Block->id)?>" />
                <?php } ?>
                <?php if ($Form->antispam == 'hidden' && $Form->antispam_field_name) { ?>
                      <input type="text" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" value="<?php echo htmlspecialchars($DATA[$Form->antispam_field_name])?>" style="position: absolute; left: -9999px" />
                <?php } ?>
                <?php foreach ($Form->fields as $row) { ?>
                    <?php if ($row->urn == 'agree') { ?>
                        <div class="form-group">
                          <div class="col-sm-9 col-sm-offset-3 col-md-offset-2">
                            <label>
                              <?php $getField($row, $DATA);?>
                              <a href="/privacy/" target="_blank">
                                <?php echo htmlspecialchars($row->name)?>
                              </a>
                            </label>
                          </div>
                        </div>
                    <?php } elseif ($row->datatype == 'checkbox') { ?>
                        <div class="form-group">
                          <div class="col-sm-9 col-sm-offset-3">
                            <label>
                              <?php $getField($row, $DATA);?>
                              <?php echo htmlspecialchars($row->name . ($row->required ? '*' : ''))?>
                            </label>
                          </div>
                        </div>
                    <?php } else { ?>
                        <div class="form-group">
                          <label<?php echo !$row->multiple ? ' for="' . htmlspecialchars($row->urn . $row->id . '_' . $Block->id) . '"' : ''?> class="control-label col-sm-3 col-md-2">
                            <?php echo htmlspecialchars($row->name . ($row->required ? '*' : ''))?>
                          </label>
                          <div class="col-sm-9 col-md-4">
                            <?php $getField($row, $DATA);?>
                          </div>
                        </div>
                    <?php } ?>
                <?php } ?>
                <?php if ($Form->antispam == 'captcha' && $Form->antispam_field_name) { ?>
                    <div class="form-group">
                      <label for="<?php echo htmlspecialchars($Form->antispam_field_name)?>" class="control-label col-sm-3 col-md-2"><?php echo CAPTCHA?></label>
                      <div class="col-sm-9 col-md-4">
                        <img src="/assets/kcaptcha/?<?php echo session_name() . '=' . session_id()?>" /><br />
                        <input type="text" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" />
                      </div>
                    </div>
                <?php } ?>
                <?php if ($Block->EPay_Interface->id && !$Form->fields['epay']) { ?>
                    <div class="form-group">
                      <label for="name" class="control-label col-sm-3 col-md-2"><?php echo PAYMENT_METHOD?></label>
                      <div class="col-sm-9 col-md-4">
                        <label><input type="radio" name="epay" value="0" <?php echo !$DATA['epay'] ? 'checked="checked"' : ''?> /> <?php echo PAY_ON_DELIVERY?></label>
                        <label><input type="radio" name="epay" value="1" <?php echo $DATA['epay'] ? 'checked="checked"' : ''?> /> <?php echo PAY_BY_EPAY?></label>
                      </div>
                    </div>
                <?php } ?>
                <div class="form-group">
                  <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2"><button class="btn btn-primary" type="submit"><?php echo SEND?></button></div>
                </div>
              </div>
            </div>
        <?php } else { ?>
          <p><a href="?action=clear" data-role="clear-cart-trigger"><?php echo CLEAR_FAVORITES?></a></p>
        <?php } ?>
      </form>
    </div>
    <script src="/js/cart.js"></script>
<?php } elseif ($localError) { ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ((array)$localError as $key => $val) { ?>
          <li><?php echo htmlspecialchars($val)?></li>
      <?php } ?>
    </ul>
  </div>
<?php
} else {
    if ($Form->id) {
        echo YOUR_CART_IS_EMPTY;
    } else {
        echo YOUR_FAVORITES_IS_EMPTY;
    }
}
?>
