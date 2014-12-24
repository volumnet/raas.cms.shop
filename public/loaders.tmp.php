<?php if ($localSuccess) { ?>
    <div class="alert alert-success alert-block">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <p><?php echo htmlspecialchars($localSuccess['description'])?></p>
    </div>
<?php } ?>
<form class="form-horizontal" action="<?php echo $url?>" method="post" enctype="multipart/form-data" data-role="loader-form">
  <div class="control-group">
    <label class="control-label" for="loader"><?php echo CMS\Shop\LOADER?>:</label> 
    <div class="controls">
      <div class="row">
        <div class="span4">
          <select name="loader" id="loader" class="span4" required="required">
            <?php if ($CONTENT['loaders']) { ?>
                <?php 
                foreach ($CONTENT['loaders'] as $row) { 
                    if ($VIEW->sub == 'priceloaders') {
                        $col_names = json_encode(array_map(function($x) use ($row) { 
                            if (is_numeric($x->fid)) {
                                $text = $x->Field->name;
                            } elseif ($x->fid == 'name') {
                                $text = NAME;
                            } elseif ($x->fid == 'urn') {
                                $text = CMS\URN;
                            } elseif ($x->fid == 'description') {
                                $text = DESCRIPTION;
                            } else {
                                $text = '';
                            }
                            $unique = ($x->fid == $row->ufid);
                            return array('text' => $text, 'unique' => $unique);
                        }, (array)$row->columns));
                    } elseif ($VIEW->sub == 'imageloaders') {
                        if (is_numeric($row->ufid)) {
                            $text = $row->Unique_Field->name;
                        } elseif ($row->ufid == 'name') {
                            $text = NAME;
                        } elseif ($row->ufid == 'urn') {
                            $text = CMS\URN;
                        } elseif ($row->ufid == 'description') {
                            $text = DESCRIPTION;
                        } else {
                            $text = '';
                        }
                        $text = ($text ? '[' . $text . ']' . $row->sep_string : '') . '[' . CMS\Shop\FILENAME . '].(jpg|gif|png)';
                        $file_format = $text;
                    }
                    ?>
                    <option value="<?php echo (int)$row->id?>" <?php echo $DATA['loader'] == $row->id ? 'selected="selected"' : ''?> data-rows="<?php echo (int)$row->rows?>" data-cols="<?php echo (int)$row->cols?>" <?php echo isset($col_names) ? 'data-col-names="' . htmlspecialchars($col_names) . '"' : ''?> <?php echo $file_format ? 'data-file-format="' . htmlspecialchars($file_format) . '"' : ''?>>
                      <?php echo htmlspecialchars($row->name)?>
                    </option>
                <?php } ?>
            <?php } else { ?>
                <option value="" selected="selected">--</option>
            <?php } ?>
          </select>
        </div>
        <div class="span2">
          <a href="#" class="btn btn-success" data-role="download-button" data-no-loader-hint="<?php echo CMS\Shop\NO_LOADER_HINT?>">
            <i class="icon-white icon-download-alt"></i> <?php echo ($VIEW->sub == 'priceloaders') ? CMS\Shop\DOWNLOAD_PRICE : CMS\Shop\DOWNLOAD_IMAGES?>
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php if ($VIEW->sub == 'priceloaders') { ?>
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
              <label style="display: inline" for="rows">
                <input type="number" class="span1" name="rows" id="rows" min="0" step="1" value="<?php echo (int)$DATA['rows']?>" /> <?php echo mb_strtolower(CMS\Shop\ROWS_FROM_TOP)?>
              </label>, 
              <label style="display: inline" for="cols">
                <input type="number" class="span1" name="cols" id="cols" min="0" step="1" value="<?php echo (int)$DATA['cols']?>" /> <?php echo mb_strtolower(CMS\Shop\COLS_FROM_LEFT)?>
              </label>
            </div>
          </div>
        </div>
      </div>
  <?php } elseif ($VIEW->sub == 'imageloaders') { ?>
      <div class="control-group" data-role="file-format__container" style="display: none">
        <label class="control-label"><?php echo CMS\Shop\FILENAME_FORMAT?>:</label> 
        <div class="controls"><div class="row"><div class="span7" data-role="file-format"></div></div></div>
      </div>
  <?php } ?>
  <div class="control-group">
    <label class="control-label" for="test"><?php echo CMS\Shop\TEST_MODE?></label> 
    <div class="controls">
      <div class="row">
        <div class="span4"><input type="checkbox" name="test" id="test" value="1" <?php echo $DATA['test'] ? 'checked="checked"' : ''?> /></div>
      </div>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="clear">
      <?php echo ($VIEW->sub == 'priceloaders') ? CMS\Shop\DELETE_PREVIOUS_MATERIALS : CMS\Shop\DELETE_PREVIOUS_IMAGES?>
    </label> 
    <div class="controls">
      <div class="row">
        <div class="span4">
          <input type="checkbox" name="clear" id="clear" value="1" <?php echo $DATA['clear'] ? 'checked="checked"' : ''?> onclick="if ($(this).is(':checked')) { return confirm('<?php echo addslashes(($VIEW->sub == 'priceloaders') ? CMS\Shop\DELETE_PREVIOUS_MATERIALS_CONFIRM : CMS\Shop\DELETE_PREVIOUS_IMAGES_CONFIRM)?>') }" />
        </div>
      </div>
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="file"><?php echo CMS\Shop\FILE_TO_UPLOAD?>:</label> 
    <div class="controls">
      <div class="row">
        <div class="span3">
          <input type="file" name="file" id="file" <?php echo ($VIEW->sub == 'imageloaders') ? 'accept="image/jpeg,image/png,image/gif,application/zip,application/x-compressed,application/x-zip-compressed,multipart/x-zip" multiple="true"' : ''?> class="span3" required="required" />
        </div>
      </div>
    </div>
  </div>
  <?php if ($VIEW->sub == 'priceloaders') { ?>
      <div class="control-group">
        <label class="control-label"><?php echo CMS\Shop\SHOW?>:</label> 
        <div class="controls">
          <div class="row">
            <div class="span4">
              <label style="display: inline" for="show_log">
                <input type="checkbox" style="margin: 0" name="show_log" id="show_log" value="1" <?php echo $DATA['show_log'] ? 'checked="checked"' : ''?> /> <?php echo CMS\Shop\LOG?>
              </label> &nbsp; &nbsp; &nbsp;
              <label style="display: inline" for="show_data">
                <input type="checkbox" style="margin: 0" name="show_data" id="show_data" value="1" <?php echo $DATA['show_data'] ? 'checked="checked"' : ''?> /> <?php echo CMS\Shop\DATA?>
              </label>
            </div>
          </div>
        </div>
      </div>
  <?php } else { ?>
      <div class="control-group">
        <label class="control-label"><?php echo CMS\Shop\SHOW_LOG?>:</label> 
        <div class="controls">
          <div class="row">
            <div class="span4"><input type="checkbox" name="show_log" id="show_log" value="1" <?php echo $DATA['show_log'] ? 'checked="checked"' : ''?> /></div>
          </div>
        </div>
      </div>
  <?php } ?>
  <div class="control-group">
    <label class="control-label"></label>
    <div class="controls">
      <div class="row">
        <div class="span4">
          <button type="submit" class="btn btn-primary"><i class="icon-white icon-upload"></i> <?php echo ($VIEW->sub == 'priceloaders') ? CMS\Shop\UPLOAD_PRICE : CMS\Shop\UPLOAD_IMAGES?></button>
        </div>
      </div>
    </div>
  </div>
  <?php if ($VIEW->sub == 'priceloaders') { ?>

  <?php } ?>
</form>