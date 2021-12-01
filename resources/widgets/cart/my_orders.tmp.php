<?php
/**
 * Виджет "Мои заказы"
 * @param Block_PHP $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Package;

?>
<div class="my-orders">
  <?php if ($Item) {
      $formatter = new OrderArrayFormatter($Item);
      $orderData = $formatter->format();
      if ($_GET['AJAX'] == $Block->id) {
          $result = ['item' => $orderData];
          while (ob_get_level()) {
              ob_end_clean();
          }
          header('Content-Type: application/json');
          echo json_encode($result);
          exit;
      }
      ?>
      <div class="my-orders__article">
        <my-orders-article :block-id="<?php echo (int)$Block->id?>" :item="<?php echo htmlspecialchars(json_encode($orderData))?>"></my-orders-article>
      </div>
      <?php
      Package::i()->requestCSS('/css/my-orders-article.css');
      Package::i()->requestJS('/js/my-orders-article.js');
  } else {
      $ordersData = array_map(function ($x) {
          $formatter = new OrderArrayFormatter($x);
          $result = $formatter->format();
          return $result;
      }, $Set);
      if ($_GET['AJAX'] == $Block->id) {
          $result = ['items' => $ordersData];
          while (ob_get_level()) {
              ob_end_clean();
          }
          header('Content-Type: application/json');
          echo json_encode($result);
          exit;
      }
      ?>
      <div class="my-orders__list">
        <my-orders-list :block-id="<?php echo (int)$Block->id?>" :initial-items="<?php echo htmlspecialchars(json_encode($ordersData))?>"></my-orders-list>
      </div>
      <?php
      Package::i()->requestCSS('/css/my-orders-list.css');
      Package::i()->requestJS('/js/my-orders-list.js');
  } ?>
</div>
