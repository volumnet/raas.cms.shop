<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\CMS\Package;

function formatPrice($price)
{
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
}
?>
<?php if ($success[(int)$Block->id]) { ?>
    <div class="notifications">
      <div class="alert alert-success"><?php echo ORDER_SUCCESSFULLY_SENT?></div>
    </div>
<?php } elseif ($Cart->items) { ?>
    <form action="#feedback" method="post" enctype="multipart/form-data">
      <table class="table table-striped" data-role="cart-table">
        <thead>
          <tr>
            <th class="span1"><?php echo IMAGE?></th>
            <th class="span7"><?php echo NAME?></th>
            <th class="span1"><?php echo PRICE?></th>
            <?php if (!$Cart->cartType->no_amount) { ?>
                <th class="span1"><?php echo AMOUNT?></th>
                <th class="span1"><?php echo SUM?></th>
            <?php } ?>
            <th class="span1"></th>
          </tr>
        </thead>
        <tbody>
          <?php $sum = $am = 0; foreach ($Cart->items as $row) { $row2 = new Material((int)$row->id); ?>
            <tr data-role="cart-item">
              <td class="text-center">
                <?php if ($row2->visImages) { ?>
                    <a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '"' : ''?>>
                      <img src="/<?php echo htmlspecialchars(addslashes($row2->visImages[0]->tnURL))?>" style="max-width: 48px" alt="<?php echo htmlspecialchars($row2->visImages[0]->name ?: $row->name)?>" /></a>
                <?php } ?>
              </td>
              <td><a <?php echo $row2->url ? 'href="' . htmlspecialchars($row2->url) . '"' : ''?>><?php echo htmlspecialchars($row->name)?></a></td>
              <td data-role="price" data-price="<?php echo number_format($row->realprice, 2, '.', '')?>" class="span1">
                <?php echo formatPrice($row->realprice)?> р.
                <?php if ($Cart->cartType->no_amount) { ?>
                    <input type="hidden" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" />
                <?php } ?>
              </td>
              <?php if (!$Cart->cartType->no_amount) { ?>
                  <td class="span1"><input type="number" class="form-control" style="max-width: 8em" data-role="amount" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" /></td>
                  <td class="span1" style="white-space: nowrap"><span data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></span> р.</td>
              <?php } ?>
              <td class="span1">
                <a href="?action=delete&id=<?php echo (int)$row->id?>&meta=<?php echo htmlspecialchars($row->meta)?>&back=1" onclick="return confirm('<?php echo addslashes(CART_DELETE_CONFIRM)?>')">
                  <i class="icon icon-remove" title="<?php echo DELETE?>"></i>
                </a>
              </td>
            </tr>
          <?php $sum += $row->amount * $row->realprice; $am += $row->amount; } ?>
          <tr>
            <th colspan="<?php echo !$Cart->cartType->no_amount ? '3' : '2'?>"><?php echo TOTAL_SUM?>:</th>
            <?php if (!$Cart->cartType->no_amount) { ?>
                <th><span data-role="total-amount"><?php echo (int)$am; ?></span></td>
            <?php } ?>
            <th style="white-space: nowrap"><span data-role="total-sum"><?php echo formatPrice($sum)?></span>&nbsp;р.</th>
            <th></th>
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
              <div class="form-group">
                <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2"><button class="btn btn-default" type="submit"><?php echo SEND?></button></div>
              </div>
            </div>
          </div>
      <?php } ?>

    </form>
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
    });
    </script>
<?php 
} else { 
    echo YOUR_CART_IS_EMPTY;
}
?>