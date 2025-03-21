<?php
use RAAS\Application;
use RAAS\FormTab;
use RAAS\CMS\Shop\Module;
use RAAS\CMS\Shop\Sub_Orders;
use RAAS\Form;

?>
<form<?php echo $Form->getAttrsString()?>>
  <?php echo $Form->children->render(); ?>
  <div class="form-horizontal">
    <div class="control-group">
      <div class="controls">
        <button type="submit" class="btn btn-primary"><?php echo MOVE?></button>
        <a href="<?php echo Sub_Orders::i()->url?>&action=edit&id=<?php echo (int)$Item->id?>" class="btn"><?php echo Module::i()->view->_('BACK')?></a>
        <?php echo _AND?>
        <select name="@oncommit">
          <?php
          $_RAASForm_Actions = array();
          $_RAASForm_Actions[\RAAS\Form::ONCOMMIT_RETURN] = CMS\Shop\ONCOMMIT_RETURN_TO_OLD;
          $_RAASForm_Actions[\RAAS\Form::ONCOMMIT_EDIT] = CMS\Shop\ONCOMMIT_REDIRECT_TO_NEW;
          foreach ($_RAASForm_Actions as $key => $val) {
              ?>
              <option value="<?php echo (int)$key?>" <?php echo (isset($Form->DATA['@oncommit']) && $Form->DATA['@oncommit'] == $key) ? 'selected="selected"' : ''?>>
                <?php echo htmlspecialchars($val)?>
              </option>
          <?php } ?>
        </select>
      </div>
    </div>
  </div>
</form>
