<form class="form-search" action="" method="get">
  <?php foreach ($VIEW->nav as $key => $val) { ?>
      <?php if (!in_array($key, array('page', 'search_string', 'from', 'to', 'status_id'))) { ?>
          <input type="hidden" name="<?php echo htmlspecialchars($key)?>" value="<?php echo htmlspecialchars($val)?>" />
      <?php } ?>
  <?php } ?>
  <input type="datetime" name="from" class="span2" placeholder="<?php echo CMS\SHOW_FROM?>" value="<?php echo $VIEW->nav['from']?>" />
  <input type="datetime" name="to" class="span2" placeholder="<?php echo CMS\SHOW_TO?>" value="<?php echo $VIEW->nav['to']?>" />
  <select name="status_id" class="span2">
    <option value=""<?php echo ((string)$VIEW->nav['status_id'] === '') ? ' selected="selected"' : ''?>><?php echo CMS\Shop\STATUS?></option>
    <option value="0"<?php echo ((string)$VIEW->nav['status_id'] === '0') ? ' selected="selected"' : ''?>><?php echo CMS\Shop\ORDER_STATUS_NEW?></option>
    <?php foreach ($statuses as $status) { ?>
        <option value="<?php echo (int)$status->id?>"<?php echo ($VIEW->nav['status_id'] == $status->id) ? ' selected="selected"' : ''?>>
          <?php echo htmlspecialchars($status->name)?>
        </option>
    <?php } ?>
  </select>
  <div class="input-append">
    <input type="search" class="span2 search-query" name="search_string" value="<?php echo htmlspecialchars($VIEW->nav['search_string'])?>" />
    <button type="submit" class="btn"><i class="icon-search"></i></button>
  </div>
</form>
<?php include \RAAS\CMS\Package::i()->view->tmp('multitable.tmp.php'); ?>
