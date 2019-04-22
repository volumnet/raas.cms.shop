<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\CMS\Package;

if ($success[(int)$Block->id] || $localError) {
    ?>
    <div class="notifications">
      <?php if ($success[(int)$Block->id]) { ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success[(int)$Block->id])?></div>
      <?php } elseif ($localError) { ?>
          <div class="alert alert-danger">
            <ul>
              <?php foreach ((array)$localError as $key => $val) { ?>
                  <li><?php echo htmlspecialchars($val)?></li>
              <?php } ?>
            </ul>
          </div>
      <?php } ?>
    </div>
<?php } ?>
