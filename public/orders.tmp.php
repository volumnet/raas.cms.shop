<form class="form-search" action="" method="get">
  <?php foreach ($VIEW->nav as $key => $val) { ?>
      <?php if (!in_array($key, array('page', 'search_string', 'from', 'to', 'status_id'))) { ?>
          <?php if (is_array($val)) { ?>
              <?php foreach ($val as $k => $v) { ?>
                  <input type="hidden" name="<?php echo htmlspecialchars($key . '[' . $k . ']')?>" value="<?php echo htmlspecialchars((string)$v)?>" />
              <?php } ?>
          <?php } else { ?>
              <input type="hidden" name="<?php echo htmlspecialchars($key)?>" value="<?php echo htmlspecialchars($val)?>" />
          <?php } ?>
      <?php } ?>
  <?php } ?>
  <input type="datetime" name="from" style="width: 8em;" placeholder="<?php echo CMS\SHOW_FROM?>" value="<?php echo ($VIEW->nav['from'] ?? '')?>" />
  <input type="datetime" name="to" style="width: 8em;" placeholder="<?php echo CMS\SHOW_TO?>" value="<?php echo ($VIEW->nav['to'] ?? '')?>" />
  <select name="status_id" style="width: auto;">
    <option value=""<?php echo ((string)($VIEW->nav['status_id'] ?? '') === '') ? ' selected="selected"' : ''?>>
      <?php echo CMS\Shop\STATUS?>
    </option>
    <option value="0"<?php echo ((string)($VIEW->nav['status_id'] ?? '') === '0') ? ' selected="selected"' : ''?>>
      <?php echo CMS\Shop\ORDER_STATUS_NEW?>
    </option>
    <?php foreach ($statuses as $status) { ?>
        <option value="<?php echo (int)$status->id?>"<?php echo (($VIEW->nav['status_id'] ?? '') == $status->id) ? ' selected="selected"' : ''?>>
          <?php echo htmlspecialchars($status->name)?>
        </option>
    <?php } ?>
  </select>
  <select name="paid" style="width: auto;">
    <option value=""<?php echo (!($VIEW->nav['paid'] ?? 0)) ? ' selected="selected"' : ''?>>
      <?php echo CMS\Shop\PAYMENT?>
    </option>
    <option value="1"<?php echo (($VIEW->nav['paid'] ?? 0) == 1) ? ' selected="selected"' : ''?>>
      <?php echo CMS\Shop\PAYMENT_PAID?>
    </option>
    <option value="-1"<?php echo (($VIEW->nav['paid'] ?? 0) == -1) ? ' selected="selected"' : ''?>>
      <?php echo CMS\Shop\PAYMENT_NOT_PAID?>
    </option>
  </select>
  <div class="input-append">
    <input type="search" class="span2 search-query" name="search_string" value="<?php echo htmlspecialchars($VIEW->nav['search_string'] ?? '')?>" />
    <button type="submit" class="btn"><i class="icon-search"></i></button>
  </div>
</form>
<?php include \RAAS\CMS\Package::i()->view->tmp('multitable.tmp.php'); ?>
