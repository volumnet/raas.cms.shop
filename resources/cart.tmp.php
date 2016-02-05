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
            'url' => $row2->url
        );
    }
    echo json_encode($temp); 
    exit;
} elseif ($epayWidget && ($epayWidget instanceof \RAAS\CMS\Snippet)) {
    eval('?' . '>' . $epayWidget->description);
} elseif ($success[(int)$Block->id]) { 
    ?>
    <div class="notifications">
      <div class="alert alert-success"><?php echo ORDER_SUCCESSFULLY_SENT?></div>
    </div>
<?php } elseif ($Cart->items) { ?>
    <section class="cart">
      <form action="#feedback" method="post" enctype="multipart/form-data">
        <table class="table table-striped cart-table" data-role="cart-table">
          <thead>
            <tr>
              <th class="span1 cart-table__image-col"><?php echo IMAGE?></th>
              <th class="span7 cart-table__name-col"><?php echo NAME?></th>
              <th class="span1 cart-table__price-col"><?php echo PRICE?></th>
              <?php if (!$Cart->cartType->no_amount) { ?>
                  <th class="span1 cart-table__amount-col"><?php echo AMOUNT?></th>
                  <th class="span1 cart-table__sum-col"><?php echo SUM?></th>
              <?php } ?>
              <th class="span1 cart-table__actions-col"></th>
            </tr>
          </thead>
          <tbody>
            <?php $sum = $am = 0; foreach ($Cart->items as $row) { $row2 = new Material((int)$row->id); ?>
              <tr data-role="cart-item">
                <td class="text-center cart-table__image-col">
                  <?php if ($row2->visImages) { ?>
                      <a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '"' : ''?>>
                        <img src="/<?php echo htmlspecialchars(addslashes($row2->visImages[0]->tnURL))?>" style="max-width: 48px" alt="<?php echo htmlspecialchars($row2->visImages[0]->name ?: $row->name)?>" /></a>
                  <?php } ?>
                </td>
                <td class="cart-table__name-col"><a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '"' : ''?>><?php echo htmlspecialchars($row->name)?></a></td>
                <td data-role="price" data-price="<?php echo number_format($row->realprice, 2, '.', '')?>" class="span1 cart-table__price-col" style="white-space: nowrap">
                  <?php echo formatPrice($row->realprice)?> <span class="fa fa-rub"></span>
                  <?php if ($Cart->cartType->no_amount) { ?>
                      <input type="hidden" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" />
                  <?php } ?>
                </td>
                <?php if (!$Cart->cartType->no_amount) { ?>
                    <td class="span1 cart-table__amount-col"><input type="number" class="form-control" style="max-width: 8em" data-role="amount" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" /></td>
                    <td class="span1 cart-table__sum-col" style="white-space: nowrap"><span data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></span> <span class="fa fa-rub"></span></td>
                <?php } ?>
                <td class="span1 cart-table__actions-col">
                  <a href="#" data-id="<?php echo (int)$row->id?>" data-meta="" data-toggle="modal" data-target="#confirmDeleteItemModal">
                    <i class="fa fa-remove" title="<?php echo DELETE?>"></i>
                  </a>
                </td>
              </tr>
            <?php $sum += $row->amount * $row->realprice; $am += $row->amount; } ?>
            <tr>
              <th colspan="<?php echo !$Cart->cartType->no_amount ? '3' : '2'?>"><?php echo TOTAL_SUM?>:</th>
              <?php if (!$Cart->cartType->no_amount) { ?>
                  <th class="cart-table__amount-col"><span data-role="total-amount"><?php echo (int)$am; ?></span></td>
              <?php } ?>
              <th class="cart-table__sum-col" style="white-space: nowrap"><span data-role="total-sum"><?php echo formatPrice($sum)?></span>&nbsp;<span class="fa fa-rub"></span></th>
              <th class="cart-table__actions-col"></th>
            </tr>
          </tbody>
        </table>
        <?php if ($Form->id) { ?>
            <div class="form-horizontal">

              <?php include Package::i()->resourcesDir . '/form.inc.php'?>
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
                    <div class="form-group">
                      <label for="<?php echo htmlspecialchars($row->urn)?>" class="control-label col-sm-3 col-md-2"><?php echo htmlspecialchars($row->name . ($row->required ? '*' : ''))?></label>
                      <div class="col-sm-9 col-md-4"><?php $getField($row, $DATA)?></div>
                    </div>
                <?php } ?>
                <?php if ($Form->antispam == 'captcha' && $Form->antispam_field_name) { ?>
                    <div class="form-group">
                      <label for="name" class="control-label col-sm-3 col-md-2"><?php echo CAPTCHA?></label>
                      <div class="col-sm-9 col-md-4 <?php echo htmlspecialchars($Form->antispam_field_name)?>">
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
                  <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2"><button class="btn btn-default" type="submit"><?php echo SEND?></button></div>
                </div>
              </div>
            </div>
        <?php } ?>

      </form>
      <div class="modal fade" id="confirmDeleteItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header" style="border-bottom: none">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title"><?php echo addslashes(CART_DELETE_CONFIRM)?></h4>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo CANCEL?></button>
              <a href="#" class="btn btn-primary"><?php echo DELETE?></a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var formatPrice = function(price)
        {
            var pR = Math.round((parseFloat(price) - parseInt(price)) * 100);
            var pS = parseInt(price).toString();
            var pT = '';
            
            for (var i = 0; i < pS.length; i++) {
                var j = pS.length - i - 1;
                pT = ((i % 3 == 2) && (j > 0) ? ' ' : '') + pS.substr(j, 1) + pT;
            }
            if (pR > 0) {
                pR = pR.toString();
                if (pR.length < 2) {
                    pR = '0' + pR;
                }
                pT += ',' + pR;
            }
            return pT;
        }
        var calculate = function() {
            var total_sum = 0;
            var total_amount = 0;
            $('[data-role="cart-table"] [data-role="cart-item"]').each(function() {
                var price = parseFloat($('[data-role="price"]', this).attr('data-price'));
                var amount = parseInt($('[data-role="amount"]', this).val());
                if (isNaN(price)) {
                    price = 0;
                }
                if (isNaN(amount)) {
                    amount = 0;
                }
                var sum = price * amount;
                $('[data-role="sum"]', this).text(formatPrice(sum));
                total_sum += sum;
                total_amount += amount;
            });
            $('[data-role="cart-table"] [data-role="total-sum"]').text(formatPrice(total_sum));
            $('[data-role="cart-table"] [data-role="total-amount"]').text(total_amount);
        }
        $('input[data-role="amount"]').change(calculate);

        var $confirmDeleteItemModal = $('#confirmDeleteItemModal');
        $('body').append($confirmDeleteItemModal);
        $('a[data-target="#confirmDeleteItemModal"][data-toggle="modal"]').on('click', function() {
            $('.modal-footer a', $confirmDeleteItemModal).attr('href', '?action=delete&id=' + parseInt($(this).attr('data-id')) + ($(this).attr('data-meta') ? '&meta=' + $(this).attr('data-meta') : ''));
        });
    });
    </script>
<?php 
} else { 
    echo YOUR_CART_IS_EMPTY;
}
?>