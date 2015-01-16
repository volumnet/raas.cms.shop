<?php if ($localSuccess) { ?>
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
          <a href="#" class="btn btn-success" data-role="download-button" data-no-loader-hint="<?php echo CMS\Shop\NO_LOADER_HINT?>">
            <i class="icon-white icon-download-alt"></i> <?php echo ($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) ? CMS\Shop\DOWNLOAD_PRICE : CMS\Shop\DOWNLOAD_IMAGES?>
          </a>
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
            <div class="span7"><table class="table cms-shop-headers-table" data-role="headers-table"><thead><tr></tr></thead></table></div>
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
    <div class="controls"><div class="row"><div class="span4"><?php echo $_RAASForm_Control($f)?></div></div></div>
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
  <?php if ($Form instanceof \RAAS\CMS\Shop\ProcessPriceLoaderForm) { ?>

  <?php } ?>
</form>