<?php
/**
 * Мои заказы
 * @param Block_PHP $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\AssetManager;
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
        <my-orders-article
          :block-id="<?php echo (int)$Block->id?>"
          :item="<?php echo htmlspecialchars(json_encode($orderData))?>"
        ></my-orders-article>
      </div>
      <?php
      AssetManager::requestCSS('/css/my-orders-article.css');
      AssetManager::requestJS('/js/my-orders-article.js');
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
      <my-orders-list
        class="my-orders__list"
        :block-id="<?php echo (int)$Block->id?>"
        :initial-items="<?php echo htmlspecialchars(json_encode($ordersData))?>"
      ></my-orders-list>
      <?php
      AssetManager::requestCSS('/css/my-orders-list.css');
      AssetManager::requestJS('/js/my-orders-list.js');
  } ?>
</div>
