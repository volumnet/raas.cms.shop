<?php if ($localSuccess ?? null) { ?>
    <div class="alert alert-success alert-block">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <p><?php echo htmlspecialchars($localSuccess['description'])?></p>
    </div>
<?php } ?>
<?php include $VIEW->tmp('/form.inc.php')?>
<?php include $VIEW->tmp('/field.inc.php')?>
<form<?php echo $_RAASForm_Attrs($Form)?>>
  <div class="control-group">
    <label class="control-label" for="loader"><?php echo $Form->children['loader']->caption?>:</label>
    <div class="controls">
      <div class="row">
        <div class="span4"><?php echo $_RAASForm_Control($Form->children['loader'])?></div>
        <div class="span2">
          <?php if ($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) { ?>
              <div class="btn-group" data-role="download-button" data-no-loader-hint="<?php echo CMS\Shop\NO_LOADER_HINT?>">
                <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                  <i class="icon-white icon-download-alt"></i> <?php echo CMS\Shop\DOWNLOAD_PRICE?> <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                  <?php $downloadMenu = getMenu((array)($downloadMenu ?? []));
                  foreach ($downloadMenu as $menuItem) { ?>
                      <li<?php echo (($menuItem['active'] ?? false) ? ' class="active"' : '')?>>
                        <a data-href="<?php echo htmlspecialchars($menuItem['data-href'])?>">
                          <?php echo htmlspecialchars($menuItem['name'])?>
                        </a>
                      </li>
                  <?php } ?>
                </ul>
              </div>
          <?php } else { ?>
              <a data-href="<?php echo $url?>&action=download" data-role="download-button" class="btn btn-success"><i class="icon-white icon-download-alt"></i> <?php echo CMS\Shop\DOWNLOAD_IMAGES?></a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
  <?php if (isset($Form->children['cat_id']) && ($f = $Form->children['cat_id'])) { ?>
      <div class="control-group">
        <label class="control-label" for="<?php echo htmlspecialchars($f->name)?>"><?php echo htmlspecialchars($f->caption)?>:</label>
        <div class="controls"><div class="row"><div class="span4"><?php echo $_RAASForm_Control($f)?></div></div></div>
      </div>
  <?php } ?>
  <div class="control-group">
    <label class="control-label"><?php echo CMS\MATERIAL_TYPE?>:</label>
    <div class="controls"><div class="row"><div class="span7"><span data-role="material-type-container"></span></div></div></div>
  </div>
  <?php if ($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) { ?>
      <div class="control-group" data-role="headers-table__container" style="display: none">
        <label class="control-label"><?php echo CMS\Shop\COLUMNS?>:</label>
        <div class="controls">
          <div class="row">
            <div class="span7">
              <table class="table cms-shop-headers-table">
                <thead>
                  <tr data-role="headers-letters"></tr>
                  <tr data-role="headers-table"></tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="control-group">
        <label class="control-label"><?php echo CMS\Shop\OFFSET?>:</label>
        <div class="controls">
          <div class="row">
            <div class="span4">
              <?php $f = $Form->children['rows']?>
              <label style="display: inline" for="<?php echo htmlspecialchars($f->name)?>">
                <?php echo $_RAASForm_Control($f) . ' ' . htmlspecialchars($f->caption)?>
              </label>,
              <?php $f = $Form->children['cols']?>
              <label style="display: inline" for="<?php echo htmlspecialchars($f->name)?>">
                <?php echo $_RAASForm_Control($f) . ' ' . htmlspecialchars($f->caption)?>
              </label>
            </div>
          </div>
        </div>
      </div>
  <?php } elseif ($Form instanceof \RAAS\CMS\Shop\ProcessImageLoaderForm) { ?>
      <div class="control-group" data-role="image-field__container" style="display: none">
        <label class="control-label"><?php echo CMS\Shop\IMAGE_FIELD?>:</label>
        <div class="controls"><div class="row"><div class="span7" data-role="image-field"></div></div></div>
      </div>
      <div class="control-group" data-role="file-format__container" style="display: none">
        <label class="control-label"><?php echo CMS\Shop\FILENAME_FORMAT?>:</label>
        <div class="controls"><div class="row"><div class="span7" data-role="file-format"></div></div></div>
      </div>
  <?php } ?>
  <?php $f = $Form->children['test']?>
  <div class="control-group">
    <label class="control-label" for="<?php echo htmlspecialchars($f->name)?>"><?php echo htmlspecialchars($f->caption)?>:</label>
    <div class="controls"><div class="row"><div class="span4"><?php echo $_RAASForm_Control($f)?></div></div></div>
  </div>
  <?php $f = $Form->children['clear']?>
  <div class="control-group">
    <label class="control-label" for="<?php echo htmlspecialchars($f->name)?>"><?php echo htmlspecialchars($f->caption)?>:</label>
    <div class="controls"><div class="row"><div class="span4"><?php echo $_RAASForm_Control($f)?></div></div></div>
  </div>
  <?php $f = $Form->children['file']?>
  <div class="control-group">
    <label class="control-label" for="<?php echo htmlspecialchars($f->name)?>"><?php echo htmlspecialchars($f->caption)?>:</label>
    <div class="controls"><div class="row"><div class="span4"><input<?php echo $_RAASForm_Attrs($f, array())?> /></div></div></div>
  </div>
  <?php if ($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) { ?>
      <div class="control-group">
        <label class="control-label"><?php echo CMS\Shop\SHOW?>:</label>
        <div class="controls">
          <div class="row">
            <div class="span4">
              <?php $f = $Form->children['show_log']?>
              <label style="display: inline" for="<?php echo htmlspecialchars($f->name)?>">
                <?php echo $_RAASForm_Control($f) . ' ' . htmlspecialchars($f->caption)?>
              </label>
              &nbsp; &nbsp; &nbsp;
              <?php $f = $Form->children['show_data']?>
              <label style="display: inline" for="<?php echo htmlspecialchars($f->name)?>">
                <?php echo $_RAASForm_Control($f) . ' ' . htmlspecialchars($f->caption)?>
              </label>
            </div>
          </div>
        </div>
      </div>
  <?php } else { ?>
      <?php $f = $Form->children['show_log']?>
      <div class="control-group">
        <label class="control-label" for="<?php echo htmlspecialchars($f->name)?>"><?php echo htmlspecialchars($f->caption)?>:</label>
        <div class="controls"><div class="row"><div class="span4"><?php echo $_RAASForm_Control($f)?></div></div></div>
      </div>
  <?php } ?>
  <div class="control-group">
    <label class="control-label"></label>
    <div class="controls">
      <div class="row">
        <div class="span4">
          <button type="submit" class="btn btn-primary"><i class="icon-white icon-upload"></i> <?php echo ($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) ? CMS\Shop\UPLOAD_PRICE : CMS\Shop\UPLOAD_IMAGES?></button>
        </div>
      </div>
    </div>
  </div>
  <?php
  ?>

  <script type="application/javascript">
  <?php if (($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) && ($raw_data ?? null)) { ?>
      var raw_data = <?php echo json_encode($raw_data, JSON_UNESCAPED_UNICODE)?>;
      <?php //echo "\n" . '// ' . json_last_error() . ' ' . json_last_error_msg() . "\n";
  }
  if ($log ?? null) {
      $log = array_map(function($x) { $y = $x; $y['time'] = number_format($y['time'], 3, '.', ' '); return $y; }, $log);
      ?>
      var log = <?php echo json_encode($log, JSON_UNESCAPED_UNICODE)?>;
  <?php } ?>
  var timeName = '<?php echo addslashes(CMS\Shop\TIME_SEC)?>';
  </script>
  <?php if (($raw_data ?? null) || ($log ?? null)) { ?>
      <h2><?php echo CMS\Shop\LOADER_REPORT?></h2>
      <?php if (($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) && $raw_data && $log) { ?>
          <div role="tabpanel">
            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active"><a href="#tab_log" role="tab" data-toggle="tab"><?php echo CMS\Shop\LOG?></a></li>
              <li role="presentation"><a href="#tab_data" role="tab" data-toggle="tab"><?php echo CMS\Shop\DATA?></a></li>
            </ul>
            <div class="tab-content">
              <div role="tabpanel" class="tab-pane active cms-shop-log-container" id="tab_log"></div>
              <div role="tabpanel" class="tab-pane cms-shop-log-container" id="tab_data"></div>
            </div>
          </div>
      <?php } elseif ($raw_data ?? null) { ?>
          <div id="tab_data" class="cms-shop-log-container"></div>
      <?php } elseif ($log) { ?>
          <div id="tab_log" class="cms-shop-log-container"></div>
      <?php } ?>
  <?php } ?>
</form>
