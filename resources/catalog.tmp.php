<?php
/**
 * Виджет каталога
 * @param Page $Page Текущая страница
 * @param Block_Material $Block Текущий блок
 * @param array<Material>|null $Set Набор материалов для отображения
 * @param Material $Item Текущий материал для отображения
 */
namespace RAAS\CMS;

use RAAS\Attachment;

if ($Item) {
    ?>
    <div class="catalog">
      <div class="catalog-article" itemscope itemtype="http://schema.org/Product">
        <meta itemprop="name" content="<?php echo htmlspecialchars($Item->name)?>" />
        <div class="catalog-article__article">
          <?php echo ARTICLE_SHORT?>
          <span itemprop="productID">
            <?php echo htmlspecialchars($Item->article)?>
          </span>
        </div>
        <div class="row">
          <?php if ($Item->visImages) { ?>
              <div class="col-sm-6 col-lg-5">
                <div class="catalog-article__images-container">
                  <div class="catalog-article__image">
                    <?php for ($i = 0; $i < count($Item->visImages); $i++) { ?>
                        <a itemprop="image" href="/<?php echo $Item->visImages[$i]->fileURL?>" <?php echo $i ? 'style="display: none"' : ''?> data-image-num="<?php echo (int)$i?>" data-lightbox-gallery="g">
                          <img src="/<?php echo htmlspecialchars($Item->visImages[$i]->tnURL)?>" alt="<?php echo htmlspecialchars($Item->visImages[$i]->name ?: $row->name)?>" /></a>
                    <?php } ?>
                  </div>
                  <?php if (count($Item->visImages) > 1) { ?>
                      <div class="catalog-article__images hidden-xs">
                        <?php for ($i = 0; $i < count($Item->visImages); $i++) { $row = $Item->visImages[$i]; ?>
                            <div data-href="/<?php echo htmlspecialchars(addslashes($row->fileURL))?>" class="catalog-article__additional-image" data-image-num="<?php echo (int)$i?>">
                              <img src="/<?php echo htmlspecialchars($row->tnURL)?>" alt="<?php echo htmlspecialchars($row->name)?>" /></div>
                        <?php } ?>
                      </div>
                  <?php } ?>
                </div>
              </div>
          <?php } ?>
          <div class="col-sm-6 col-lg-7">
            <div class="catalog-article__details">
              <div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <div class="catalog-article__text">
                  <div cp if ($Item->price_old && ($Item->price_old != $Item->price)) { ?>
                                        </div>
                <div class="catalog-article__available">
                  <link itemprop="availability" href="http://schema.org/<?php echo $Item->available ? 'InStock' : 'PreOrder'?>" />
                  <?php echo $Item->available ? '<span class="text-success">' . AVAILABLE . '</span>' : '<span class="text-danger">' . AVAILABLE_CUSTOM . '</span>'?>
                </div>
              </div>
              <!--noindex-->
              <form action="/cart/" class="catalog-article__controls" data-role="add-to-cart-form" data-id="<?php echo (int)$Item->id?>" data-price="<?php echo (int)$Item->price?>">
                <?php if ($Item->available) { ?>
                    <input type="hidden" name="action" value="add" />
                    <input type="hidden" name="id" value="<?php echo (int)$Item->id?>" />
                    <input type="hidden" name="back" value="1" />
                    <input type="number" class="form-control" autocomplete="off" name="amount" min="<?php echo (int)$Item->min ?: 1?>" step="<?php echo (int)$Item->step ?: 1?>" value="<?php echo (int)$Item->min ?: 1?>" />
                    <button type="submit" class="btn btn-danger"><?php echo TO_CART?></button>
                    <?php /* <a href="/cart/?action=add&id=<?php echo (int)$Item->id?>" class="btn btn-danger" data-role="add-to-cart-trigger" data-id="<?php echo (int)$Item->id?>" data-price="<?php echo (int)$Item->price?>" data-active-html="<?php echo DELETE_FROM_CART?>"><?php echo TO_CART?></button> */ ?>
                <?php } ?>
                <a href="/favorites/?action=add&id=<?php echo (int)$Item->id?>" class="btn btn-info" data-role="add-to-favorites-trigger" data-id="<?php echo (int)$Item->id?>" data-active-html="<?php echo DELETE_FROM_FAVORITES?>" rel="nofollow"><?php echo TO_FAVORITES?></a>
              </form>
              <!--/noindex-->
              <!--noindex-->
              <div class="share">
                <script type="text/javascript" src="//yastatic.net/share/share.js" charset="utf-8"></script>
                <?php echo SHARE?>: <div class="yashare-auto-init" style="display: inline-block; vertical-align: middle" data-yashareL10n="ru" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki,moimir" data-yashareTheme="counter"></div>
              </div>
              <!--/noindex-->
              <?php
              $propsText = '';
              $brands = $models = [];
              foreach ((array)$Item->model as $val) {
                  $brands[$val->brand->id] = $val->brand->name;
                  $models[$val->id] = $val->name;
              }
              unset($temp);
              foreach ($Item->fields as $key => $val) {
                  if (!in_array($val->urn, [
                      'images',
                      'brief',
                      'videos',
                      'videos_url',
                      'files',
                      'onmain',
                      'article',
                      'price',
                      'price_old',
                      'available',
                      'min',
                      'step'
                  ]) && !in_array($val->datatype, [
                      'image',
                      'file',
                      'material',
                      'checkbox'
                  ])) {
                      if ($val->doRich()) {
                          $v = implode(
                              ', ',
                              array_map(function ($x) use ($val) {
                                  return $val->doRich($x);
                              }, $val->getValues(true))
                          );
                          switch ($key) {
                              case 'width':
                              case 'height':
                                  $propsText .= ' <tr>
                                                    <th>' . htmlspecialchars($val->name) . ': </th>
                                                    <td itemprop="' . $key . '" itemtype="http://schema.org/QuantitativeValue">
                                                      <span itemprop="value">' . $v . '</span>
                                                    </td>
                                                  </tr>';
                                  break;
                              case 'article':
                                  $propsText .= ' <tr>
                                                    <th>' . htmlspecialchars($val->name) . ': </th>
                                                    <td itemprop="productID">' . $val['doRich'] . '</td>
                                                  </tr>';
                                  break;
                              case 'brand':
                                  $propsText .= ' <tr>
                                                    <th>' . htmlspecialchars($val->name) . ': </th>
                                                    <td itemprop="brand" itemscope itemtype="http://schema.org/Brand">
                                                      <span itemprop="name">' . $v . '</span>
                                                    </td>
                                                  </tr>';
                                  break;
                              default:
                                  $propsText .= ' <tr itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
                                                    <th itemprop="name">' . htmlspecialchars($val->name) . ': </th>
                                                    <td itemprop="value">' . $v . '</td>
                                                  </tr>';
                                  break;
                          }
                      }
                  }
              }
              if ($propsText) {
                  echo '<div class="catalog-article__props">
                          <table class="table table-striped"><tbody>' . $propsText . '</tbody></table>
                        </div>
                        <div class="clearfix"></div>';
              }
              ?>
            </div>
          </div>
        </div>
        <?php
        $tabs = [];
        foreach ([
            'description',
            'files',
            'videos',
            'reviews',
            'related'
        ] as $key) {
            $text = '';
            $name = $Item->fields[$key]->name;
            switch ($key) {
                case 'description':
                    $name = DESCRIPTION;
                    $text = '<div itemprop="description">' . trim($Item->description) . '</div>';
                    break;
                case 'files':
                    if ($Item->files) {
                        $text = '<div class="catalog-article__files-list">
                                   <div class="catalog-article-files-list">';
                        foreach ($Item->files as $file) {
                            $text .= '<div class="catalog-article-files-list__item">
                                        <a href="/' . htmlspecialchars($file->fileURL) . '" class="catalog-article-files-item catalog-article-files-item_' . mb_strtolower(pathinfo($file->fileURL, PATHINFO_EXTENSION)) . '">'
                                  .       htmlspecialchars($file->name ?: basename($file->fileURL))
                                  . '   </a>
                                      </div>';
                        }
                        $text .= ' </div>
                                 </div>';
                    }
                    break;
                case 'videos':
                    if ($Item->videos) {
                        $text .= '<div class="catalog-article__videos">';
                        for ($i = 0; $i < (count($Item->videos) / 4); $i++) {
                            $text .= '<div class="row">';
                            for ($j = $i * 4; $j < ($i + 1) * 4; $j++) {
                                if ($val = $Item->videos[$j]) {
                                    $ytid = $ytname = '';
                                    if (preg_match('/^(.*?)((http(s?):\\/\\/.*?(((\\?|&)v=)|(embed\\/)|(youtu\\.be\\/)))([\\w\\-\\_]+).*?)$/', $val, $regs)) {
                                        $ytname = trim($regs[1]);
                                        $ytid = trim($regs[10]);
                                    }
                                    if (!$ytname) {
                                        $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $ytid . '&key=AIzaSyCJgMFQqq6Ax9WlGhuslTz4viyG3RbPEic';
                                        $json = file_get_contents($url);
                                        $json = json_decode($json, true);
                                        if (isset($json['items'][0]['snippet']['title'])) {
                                            $ytname = trim($json['items'][0]['snippet']['title']);
                                        }
                                    }
                                    $text .= '<div class="col-sm-3">
                                                <div class="catalog-article__video">
                                                  <a href="https://youtube.com/embed/' . $ytid . '" data-lightbox-gallery="v" title="' . htmlspecialchars($ytname) . '">
                                                    <img src="https://i.ytimg.com/vi/' . htmlspecialchars($ytid) . '/hqdefault.jpg" alt="' . htmlspecialchars($ytname) . '">
                                                  </a>
                                                </div>
                                              </div>';

                                }
                            }
                            $text .= '</div>';
                        }
                        $text .= '</div>';
                    }
                    break;
                case 'reviews':
                    $name = REVIEWS . ($comments ? ' (' . count($comments) . ')' : '');
                    ob_start();
                    if ($comments) {
                        eval('?' . '>' . Snippet::importByURN('goods_comments')->description);
                    }
                    if ($commentFormBlock) {
                        $commentFormBlock->process($Page);
                    }
                    $text .= ob_get_clean();
                    break;
                case 'related':
                    if ($Item->related) {
                        $text .= '<div class="row catalog-list catalog-list_related">';
                        foreach ($Item->related as $row) {
                            $text .= '<div class="catalog-list__item">';
                            ob_start();
                            Snippet::importByURN('catalog_item')->process([
                                'item' => $row
                            ]);
                            $text .= ob_get_clean();
                            $text .= '</div>';
                        }
                        $text .= '</div>';
                    }
                    break;
            }
            if ($text) {
                $tabs[$key] = ['name' => $name, 'description' => $text];
            }
        }
        if ($tabs) {
            ?>
            <ul class="nav nav-tabs" role="tablist">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <li<?php echo !$i ? ' class="active"' : ''?>>
                    <a href="#<?php echo $key?>" aria-controls="<?php echo $key?>" role="tab" data-toggle="tab">
                      <?php echo htmlspecialchars($row['name'])?>
                    </a>
                  </li>
              <?php $i++; } ?>
            </ul>
            <div class="tab-content" style="padding: 15px 0;">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <div class="tab-pane<?php echo !$i ? ' active' : ''?>" id="<?php echo $key?>">
                    <?php echo $row['description']?>
                  </div>
              <?php $i++; } ?>
            </div>
        <?php } ?>
      </div>
    </div>
<?php } else { ?>
    <div class="catalog">
      <?php if ($Page->pid) { ?>
          <div class="catalog__filter">
            <?php eval('?' . '>' . Snippet::importByURN('catalog_filter')->description)?>
          </div>
      <?php } ?>
      <div class="catalog__inner">
        <?php
        if ($Set || $subCats) {
            if ($subCats) {
                ?>
                <div class="catalog__categories-list">
                  <div class="catalog-categories-list">
                    <?php foreach ($subCats as $row) { ?>
                        <div class="catalog-categories-list__item">
                          <?php Snippet::importByURN('catalog_category')->process(['page' => $row])?>
                        </div>
                    <?php } ?>
                  </div>
                </div>
                <?php
            }
            if ($Set) {
                ?>
                <div class="catalog__list">
                  <div class="catalog-list">
                    <?php foreach ($Set as $row) { ?>
                        <div class="catalog-list__item">
                          <?php Snippet::importByURN('catalog_item')->process(['item' => $row])?>
                        </div>
                    <?php } ?>
                  </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p><?php echo NO_RESULTS_FOUND?></p>
        <?php } ?>
      </div>
      <?php
      if ($Set) {
          include \RAAS\CMS\Package::i()->resourcesDir . '/pages.inc.php';
          if ($Pages->pages > 1) {
              ?>
              <div data-pages="<?php echo $Pages->pages?>">
                <ul class="pagination pull-right">
                  <?php
                  echo $outputNav(
                      $Pages,
                      [
                          'pattern' => '<li><a href="' . \SOME\HTTP::queryString('page={link}') . '">{text}</a></li>',
                          'pattern_active' => '<li class="active"><a>{text}</a></li>',
                          'ellipse' => '<li class="disabled"><a>...</a></li>'
                      ]
                  );
                  ?>
                </ul>
              </div>
              <div class="clearfix"></div>
          <?php } ?>
      <?php } ?>
    </div>
<?php } ?>
