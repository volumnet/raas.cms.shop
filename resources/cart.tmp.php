<?php
function formatPrice($price)
{
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
}
?>
<?php if ($Cart->items) { ?>
    <table class="table table-striped" data-role="cart-table">
      <thead>
        <tr>
          <th><?php echo NAME?></th>
          <th><?php echo ADDITIONAL_INFO?></th>
          <th><?php echo PRICE?></th>
          <th><?php echo AMOUNT?></th>
          <th><?php echo SUM?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($Item->items as $row) { $sum = 0; ?>
          <tr data-role="cart-item">
            <td><?php echo htmlspecialchars($row->name)?></td>
            <td><?php echo htmlspecialchars($row->meta)?></td>
            <td data-role="price" data-price="<?php echo number_format($row->realprice, 2, '.', '')?>"><?php echo formatPrice($row->realprice)?></td>
            <td><input type="text" class="span1" data-role="amount" name="amount[<?php echo htmlspecialchars((int)$row->id . '_' . $row->meta)?>]" value="<?php echo (int)$row->amount?>" /></td>
            <td data-role="sum"><?php echo formatPrice($row->amount * $row->realprice)?></td>
            <td>
              <a href="?action=delete&id=<?php echo (int)$row->id?>&meta=<?php echo htmlspecialchars($row->meta)?>&back=1">
                <i class="icon icon-remove" title="<?php echo DELETE?>"></i>
              </a>
            </td>
          </tr>
        <?php $sum += $row->amount * $row->realprice; } ?>
        <tr>
          <th colspan="4" style="text-align: right"><?php echo TOTAL_SUM?></th>
          <th data-role="total-sum"><?php echo formatPrice($sum)?></th>
          <th></th>
        </tr>
      </tbody>
    </table>
    <a name="feedback"></a>
    <article class="article">
      <div class="feedback">
        <form class="form-horizontal" action="#feedback" method="post" enctype="multipart/form-data">
          <h3 class="form-title text-normal"><?php echo CHECKOUT?></h3>
          <?php if ($success[(int)$Block->id]) { ?>
              <div class="notifications">
                <div class="alert alert-success"><?php echo ORDER_SUCCESSFULLY_SENT?></div>
              </div>
          <?php } else { ?>
              <?php include \RAAS\CMS\Package::i()->resourcesDir . '/form.inc.php'?>
              <?php if ($localError) { ?>
                  <div class="notifications">
                    <?php foreach ((array)$localError as $key => $val) { ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($val)?></div>
                    <?php } ?>
                  </div>
              <?php } ?>
              
              <?php if ($Form->signature) { ?>
                    <input type="hidden" name="form_signature" value="<?php echo md5('form' . (int)$Form->id . (int)$Block->id)?>" />
              <?php } ?>
              <?php if ($Form->antispam == 'hidden' && $Form->antispam_field_name) { ?>
                    <input type="text" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" value="<?php echo htmlspecialchars($DATA[$Form->antispam_field_name])?>" style="position: absolute; left: -9999px" />
              <?php } ?>
              <?php foreach ($Form->fields as $row) { ?>
                  <div class="control-group">
                    <label for="<?php echo htmlspecialchars($row->urn)?>" class="control-label"><?php echo htmlspecialchars($row->name . ($row->required ? '*' : ''))?></label>
                    <div class="controls"><?php $getField($row, $DATA)?></div>
                  </div>
              <?php } ?>
              <?php if ($Form->antispam == 'captcha' && $Form->antispam_field_name) { ?>
                  <div class="control-group">
                    <label for="name" class="control-label"><?php echo CAPTCHA?></label>
                    <div class="<?php echo htmlspecialchars($Form->antispam_field_name)?>">
                      <img src="/assets/kcaptcha/?<?php echo session_name() . '=' . session_id()?>" /><br />
                      <input type="text" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" />
                    </div>
                  </div>
              <?php } ?>
              <div class="control-group">
                <div class="controls"><button class="btn" type="submit"><?php echo SEND?></button></div>
              </div>
          <?php } ?>
        </form>
      </div>
    </article>
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
            });
            $('[data-role="cart-table"] [data-role="total-sum"]').text(formatPrice(total_sum));
        }
        $('input[data-role="amount"]').change(calculate);
    });
    </script>
<?php 
} else { 
    echo YOUR_CART_IS_EMPTY;
}
?>