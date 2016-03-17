<?php 
namespace RAAS\CMS;

eval('?' . '>' . Snippet::importByURN('item_inc')->description);
$formatPrice = function($price) {
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
};


if ($Item) { 
    ?>
    <div class="catalog">
      <article class="article_opened">
        <div class="article__article">
          <?php echo ARTICLE_SHORT?> <span><?php echo htmlspecialchars($Item->article)?></span>
        </div>
        <?php if ($Item->visImages) { ?>
            <div class="article__images__container">
              <div class="article__image">
                <?php for ($i = 0; $i < count($Item->visImages); $i++) { ?>
                    <a href="/<?php echo $Item->visImages[$i]->fileURL?>" <?php echo $i ? 'style="display: none"' : ''?> data-image-num="<?php echo (int)$i?>" rel="prettyPhoto[g]">
                      <img src="/<?php echo htmlspecialchars($Item->visImages[$i]->tnURL)?>" alt="<?php echo htmlspecialchars($Item->visImages[$i]->name ?: $row->name)?>" /></a>
                <?php } ?> 
              </div>
              <?php if (count($Item->visImages) > 1) { ?>
                  <div class="article__images hidden-xs">
                    <?php for ($i = 0; $i < count($Item->visImages); $i++) { $row = $Item->visImages[$i]; ?>
                        <div data-href="/<?php echo htmlspecialchars(addslashes($row->fileURL))?>" class="article__images__image" data-image-num="<?php echo (int)$i?>">
                          <img src="/<?php echo htmlspecialchars($row->tnURL)?>" alt="<?php echo htmlspecialchars($row->name)?>" /></div>
                    <?php } ?>
                  </div>
              <?php } ?>
            </div>
        <?php } ?>
        <div class="article__text">
          <div class="article__price<?php echo ($Item->price_old && ($Item->price_old != $Item->price)) ? ' article__price_new' : ''?>" data-price="<?php echo (float)$Item->price?>">
            <?php if ($Item->price_old && ($Item->price_old != $Item->price)) { ?>
                <span class="article__price__old"><?php echo $formatPrice((float)$Item->price_old)?></span>
            <?php } ?>
            <span data-role="price-container">
              <?php echo $formatPrice((float)$Item->price)?>
            </span> 
            <i class="fa fa-rub"></i>
          </div>
          <div class="article__available"><?php echo $Item->available ? '<span class="text-success">' . AVAILABLE . '</span>' : '<span class="text-danger">' . AVAILABLE_CUSTOM . '</span>'?></div>
          <form action="/cart/" class="article__controls" data-role="add-to-cart-form">
            <?php if ($Item->available) { ?>
                <input type="hidden" name="action" value="add" />
                <input type="hidden" name="id" value="<?php echo (int)$Item->id?>" />
                <input type="hidden" name="back" value="1" />
                <input type="hidden" name="amount" value="1" />
                <button type="submit" class="btn btn-danger" data-role="add-to-cart-trigger" data-price="<?php echo (int)$Item->price?>"><?php echo TO_CART?></button>
            <?php } else { ?>
                <a class="btn btn-danger" data-target="#requestItemModal" data-request-item-id="<?php echo (int)$Item->id?>" data-request-item-name="<?php echo htmlspecialchars($Item->name)?>" data-request-item-url="#" data-toggle="modal" href="#"><?php echo CHECKOUT?></a>
            <?php } ?>
            <a class="btn btn-success" data-target="#orderCallModal" data-toggle="modal" href="#"><?php echo ORDER_CALL?></a>
          </form>
          <div class="share">
            <script type="text/javascript" src="//yastatic.net/share/share.js" charset="utf-8"></script>
            <?php echo SHARE?>: <div class="yashare-auto-init" style="display: inline-block; vertical-align: middle" data-yashareL10n="ru" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki,moimir" data-yashareTheme="counter"></div>
          </div>
          <?php 
          $propsText = ''; 
          $brands = $models = array();
          foreach ((array)$Item->model as $val) {
              $brands[$val->brand->id] = $val->brand->name;
              $models[$val->id] = $val->name;
          }
          unset($temp);
          foreach ($Item->fields as $key => $val) { 
              if (!in_array($val->urn, array('images', 'brief', 'videos', 'files', 'onmain', 'article', 'price', 'price_old', 'available')) && !in_array($val->datatype, array('image', 'file', 'material', 'checkbox'))) {
                  if ($val->doRich()) {
                      $propsText .= '<tr><th>' . htmlspecialchars($val->name) . ': </th><td>' . implode(', ', array_map(function($x) use ($val) { return $val->doRich($x); }, $val->getValues(true))) . '</td></tr>';
                  }
              }
          }
          if ($propsText) {
              echo '<div class="article__props"><table class="table table-striped"><tbody>' . $propsText . '</tbody></table></div><div class="clearfix"></div>';
          }
          ?>
        </div>
        <div class="clearfix"></div>
        <div><?php echo $Item->description?></div> 
      </article>
      <?php if ($Item->related) { ?>
          <p>&nbsp;</p>
          <div class="h2"><?php echo WITH_THIS_ITEM_BUYS?></div>
          <div class="catalog__inner">
            <div class="row">
              <?php foreach ($Item->related as $row) { ?>
                  <div class="col-sm-4">
                    <?php $showItem($row)?>
                  </div>
              <?php } ?>
            </div>
          </div>
      <?php } ?>
    </div>
<?php } elseif ($showCatalog) { eval('?' . '>' . Snippet::importByURN('category_inc')->description); ?>
    <?php eval('?' . '>' . Snippet::importByURN('catalog_filter')->description); ?>
    <div class="catalog__inner">
        <div class="categories_main">
          <?php if ($Set) { ?>
              <div class="row">
                <?php foreach ($Set as $row) { ?>
                    <div class="col-xs-4 col-sm-3 col-md-2">
                      <?php $showCategory($row);?>
                    </div>
                <?php } ?>
              </div>
          <?php } else { ?>
              <p><?php echo NO_RESULTS_FOUND?></p>
          <?php } ?>
        </div>
    </div>
<?php } elseif ($showItems) { ?>
    <div class="catalog">
      <?php eval('?' . '>' . Snippet::importByURN('catalog_filter')->description); ?>
      <?php if ($Set) { ?>
          <div class="catalog__inner">
            <div class="row">
              <?php foreach ($Set as $row) { ?>
                  <div class="col-sm-4">
                    <?php $showItem($row)?>
                  </div>
              <?php } ?>
            </div>
          </div>
          <?php include \RAAS\CMS\Package::i()->resourcesDir . '/pages.inc.php'?>
          <?php if ($Pages->pages > 1) { ?>
              <div data-pages="<?php echo $Pages->pages?>">
                <ul class="pagination pull-right">
                  <?php 
                  echo $outputNav(
                      $Pages, 
                      array(
                          'pattern' => '<li><a href="' . \SOME\HTTP::queryString('page={link}') . '">{text}</a></li>', 
                          'pattern_active' => '<li class="active"><a>{text}</a></li>',
                          'ellipse' => '<li class="disabled"><a>...</a></li>'
                      )
                  );
                  ?>
                </ul>
              </div>
              <div class="clearfix"></div>
          <?php } ?>
              <?php if ($Pages->page == 1) { ?>
                  <script>
                  jQuery(document).ready(function($) {
                      $.RAAS_Catalog = new $.RAAS.Shop.AjaxCatalog();
                  });
                  </script>
              <?php } ?>
      <?php } else { ?>
          <div class="catalog__inner">
            <p><?php echo NO_RESULTS_FOUND?></p>
          </div>
      <?php } ?>
    </div>
<?php } ?>