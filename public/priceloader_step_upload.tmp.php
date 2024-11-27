<?php
/**
 * Виджет пошаговой загрузки прайса
 */
namespace RAAS\CMS\Shop;

?>
<cms-shop-priceloader
  :step="<?php echo (int)$step?>"
  :loader-data="<?php echo htmlspecialchars(json_encode((object)$data))?>"
  :loader="<?php echo htmlspecialchars(json_encode((object)$loaderArr, JSON_UNESCAPED_UNICODE))?>"
></cms-shop-priceloader>
