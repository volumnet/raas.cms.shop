<?php
/**
 * Виджет незадействованных материалов/страниц пошаговой загрузки прайса
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\ViewSub_Main as PackageView;

if ($localError) {
    return;
}
?>
<div class="tabbable">
  <ul class="nav nav-tabs">
    <li class="active">
      <a href="#materials" data-toggle="tab">
        <?php echo ViewSub_Priceloaders::i()->_('MATERIALS')?>
      </a>
    </li>
    <li>
      <a href="#pages" data-toggle="tab">
        <?php echo ViewSub_Priceloaders::i()->_('PAGES')?>
      </a>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane active" id="materials">
      <form class="form-search" action="#materials" method="get">
        <?php foreach (PackageView::i()->nav as $key => $val) {
            if (!in_array($key, ['search_string'])) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key)?>" value="<?php echo htmlspecialchars($val)?>" />
            <?php }
        } ?>
        <div class="input-append">
          <input type="search" class="span2 search-query" name="search_string" value="<?php echo htmlspecialchars(ViewSub_Priceloaders::i()->nav['search_string'] ?? '')?>" />
          <button type="submit" class="btn">
            <i class="icon-search"></i>
          </button>
        </div>
      </form>
      <?php
      if ($Set) {
          include PackageView::i()->tmp('multitable.tmp.php');
      } ?>
    </div>

    <div class="tab-pane" id="pages">
      <raas-field-checkbox
        type="checkbox"
        name="page_id[]"
        :multiple="true"
        :source="<?php echo htmlspecialchars(json_encode($pagesSource))?>"
        :value=[]
      ></raas-field-checkbox>
    </div>
  </div>
</div>
