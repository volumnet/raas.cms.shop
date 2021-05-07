<?php namespace RAAS\CMS?>
<?php
if ($_POST['AJAX'] && ($Item instanceof Feedback)) {
    $result = array();
    if ($success[(int)$Block->id]) {
        $result['success'] = 1;
    }
    if ($localError) {
        $result['localError'] = $localError;
    }
    while (ob_get_level()) {
      ob_end_clean();
    }
    echo json_encode($result);
    exit;
} else { ?>
    <div class="feedback" id="feedback">
      <p>Вы можете оставить отзыв о данном товаре, заполнив форму ниже</p>
      <p class="feedback__required-fields">Поля, помеченные звездочкой (*), обязательны для заполнения</p>
      <form class="form-horizontal" data-role="raas-ajaxform" action="/ajax/goods_comments/" method="post" enctype="multipart/form-data">
        <?php include \RAAS\CMS\Package::i()->resourcesDir . '/form2.inc.php'?>
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
          <input type="hidden" name="material" value="<?php echo (int)$Page->Material->id?>" />
          <?php if ($Form->signature) { ?>
                <input type="hidden" name="form_signature" value="<?php echo htmlspecialchars($Form->getSignature($Block))?>" />
          <?php } ?>
          <?php if ($Form->antispam == 'hidden' && $Form->antispam_field_name) { ?>
                <input type="text" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" value="<?php echo htmlspecialchars($DATA[$Form->antispam_field_name])?>" style="position: absolute; left: -9999px" />
          <?php } ?>
          <?php foreach ($Form->fields as $row) { ?>
              <?php if ($row->urn != 'material') { ?>
                  <div class="form-group">
                    <label for="<?php echo htmlspecialchars($row->urn)?>" class="control-label col-sm-3 col-md-2"><?php echo htmlspecialchars($row->name . ($row->required ? '*' : ''))?></label>
                    <div class="col-sm-9 col-md-4"><?php $getField($row, $DATA)?></div>
                  </div>
              <?php } ?>
          <?php } ?>
          <?php if ($Form->antispam == 'captcha' && $Form->antispam_field_name) { ?>
              <div class="form-group">
                <label for="<?php echo htmlspecialchars($Form->antispam_field_name)?>" class="control-label col-sm-3 col-md-2"><?php echo CAPTCHA?></label>
                <div class="col-sm-9 col-md-4">
                  <img loading="lazy" src="/assets/kcaptcha/?<?php echo session_name() . '=' . session_id()?>" /><br />
                  <input type="text" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" />
                </div>
              </div>
          <?php } ?>
          <div class="form-group">
            <div class="col-sm-9 col-md-4 col-sm-offset-3 col-md-offset-2"><button class="btn btn-secondary" type="submit"><?php echo SEND?></button></div>
          </div>
        </div>
      </form>
    </div>
<?php } ?>
