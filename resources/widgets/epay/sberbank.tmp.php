<?php
/**
 * Сбербанк
 * @deprecated 2024-07-05 в пользу epay.tmp.php
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Package;

if ($success[(int)$Block->id] || $localError) {
    ?>
    <div class="notifications">
      <?php if ($success[(int)$Block->id]) { ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($success[(int)$Block->id])?></div>
          <?php
          $catalogMaterialType = Material_Type::importByURN('catalog');
          $eCommerceData = ['action' => 'purchase', 'orderId' => (int)$Item->id, 'products' => []];
          foreach ((array)$Item->items as $i => $item) {
              if (in_array(
                  $item->pid,
                  MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($catalogMaterialType->id)
              )) { // Каталог продукции
                  $eCommerceData['products'][] = ECommerce::getProduct($item, $i);
              }
          }
          ?>
          <script>
          jQuery(document).ready(function($) {
              window.setTimeout(() => {
                  window.app.cart.getECommerce().trigger(<?php echo json_encode($eCommerceData)?>);
              }, 10); // Чтобы успел отработать Vue. При 0 не отрабатывает currencyCode
          });
          </script>
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
