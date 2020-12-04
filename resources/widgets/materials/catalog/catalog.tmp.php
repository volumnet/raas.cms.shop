<?php
/**
 * Виджет каталога
 * @param Page $Page Текущая страница
 * @param Block_Material $Block Текущий блок
 * @param array<Material>|null $Set Набор материалов для отображения
 * @param Material $Item Текущий материал для отображения
 */
namespace RAAS\CMS;

use SOME\HTTP;
use SOME\Text;
use RAAS\Attachment;

$hiddenProps = Snippet::importByURN('hidden_props')->process();

if ($Item) {
    $host = 'http' . (mb_strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '') . '://'
          . $_SERVER['HTTP_HOST'];
    $jsonLd = [
        '@context' => 'http://schema.org',
        '@type' => 'Product',
        'name' => $Item->name,
        'description' => trim($Item->description) ?: '.',
        'productID' => $Item->article,
        'sku' => $Item->article,
        'offers' => [
            '@type' => 'Offer',
            'sku' => $Item->article,
            'url' => $host . $Item->url,
            'price' => (float)$Item->price,
            'priceCurrency' => 'RUB',
            'availability' => 'http://schema.org/' . ($Item->available ? 'InStock' : 'PreOrder'),
        ]
    ];
    $Page->headPrefix = 'product: http://ogp.me/ns/product#';
    $Page->headData = ' <meta property="og:title" content="' . htmlspecialchars($Item->name) . '" />
                        <meta property="og:type" content="product.item" />
                        <meta property="og:url" content="' . $host . $Item->url . '" />';
    if ($Item->visImages) {
        $Page->headData .= ' <meta property="og:image" content="' . $host . '/' . $Item->visImages[0]->fileURL . '" />';
    }
    ?>
    <div class="catalog">
      <div class="catalog-article" itemscope itemtype="http://schema.org/Product">
        <meta itemprop="name" content="<?php echo htmlspecialchars($Item->name)?>" />
        <?php /* ?>
            <h1 class="catalog-article__title" itemprop="name">
              <?php echo htmlspecialchars($Item->name)?>
            </h1>
        <?php */ ?>
        <div data-vue-role="catalog-article" data-vue-inline-template data-v-bind_id="<?php echo (int)$Item->id?>" data-v-bind_name="'<?php echo htmlspecialchars($Item->name)?>'" data-v-bind_price="<?php echo (float)$Item->price?>" data-v-bind_priceold="<?php echo (float)($Item->price_old ?: $Item->price)?>" data-v-bind_min="<?php echo $Item->min || 1?>" data-v-bind_step="<?php echo $Item->step || 1?>" data-v-bind_image="'<?php echo htmlspecialchars($Item->visImages ? ('/' . $Item->visImages[0]->smallURL) : '')?>'" data-v-bind_cart="cart" data-v-bind_favorites="favorites">
          <div class="catalog-article__inner">
            <?php if ($Item->visImages) { ?>
                <div class="catalog-article__images-container">
                  <!--noindex-->
                  <div class="catalog-article__images-list<?php echo (count($Item->visImages) == 1) ? ' catalog-article__images-list_alone' : ''?>">
                    <div class="catalog-article-images-list">
                      <a href="#" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_prev" data-role="slider-prev"></a>
                      <div class="catalog-article-images-list__inner" data-role="slider" data-slider-carousel="jcarousel" data-slider-vertical="true" data-slider-wrap="circular" data-slider-duration="500">
                        <div class="catalog-article-images-list__list">
                          <?php for ($i = 0; $i < count($Item->visImages); $i++) { $row = $Item->visImages[$i]; ?>
                              <a class="catalog-article-images-list__item" href="/<?php echo $Item->visImages[$i]->fileURL?>" data-v-on_click="clickThumbnail(<?php echo (int)$i?>, $event)" data-lightbox-gallery="tn">
                                <img src="/<?php echo htmlspecialchars($row->smallURL)?>" alt="<?php echo htmlspecialchars($row->name)?>" /></a>
                          <?php } ?>
                        </div>
                      </div>
                      <a href="#" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_next" data-role="slider-next"></a>
                    </div>
                  </div>
                  <!--/noindex-->
                  <div class="catalog-article__image<?php echo (count($Item->visImages) == 1) ? ' catalog-article__image_alone' : ''?>">
                    <?php for ($i = 0; $i < count($Item->visImages); $i++) {
                        $jsonLd['image'][] = '/' . $Item->visImages[$i]->fileURL; ?>
                        <a itemprop="image" href="/<?php echo $Item->visImages[$i]->fileURL?>" <?php echo $i ? 'style="display: none"' : ''?> data-v-bind_style="{display: ((selectedImage == <?php echo $i?>) ? 'block' : 'none')}" data-lightbox-gallery="g">
                          <img src="/<?php echo Package::i()->tn($Item->visImages[$i]->fileURL, 600, 600, 'frame')?>" alt="<?php echo htmlspecialchars($Item->visImages[$i]->name ?: $row->name)?>" /></a>
                    <?php } ?>
                  </div>
                </div>
            <?php } ?>
            <div class="catalog-article__details">
              <div class="catalog-article__article">
                <?php echo ARTICLE_SHORT?>
                <span itemprop="productID">
                  <?php echo htmlspecialchars($Item->article)?>
                </span>
                <meta itemprop="sku" content="<?php echo htmlspecialchars($Item->article)?>" />
              </div>
              <div class="catalog-article__offer" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <meta itemprop="sku" content="<?php echo htmlspecialchars($Item->article)?>" />
                <link itemprop="url" href="<?php echo htmlspecialchars($host . $Item->url)?>" />
                <div class="catalog-article__price-container" data-price="<?php echo (float)$Item->price?>">
                  <?php if ($Item->price_old && ($Item->price_old != $Item->price)) { ?>
                      <span class="catalog-article__price catalog-article__price_old" data-v-html="formatPrice(priceold * amount)">
                        <?php echo Text::formatPrice((float)$Item->price_old)?>
                      </span>
                  <?php } ?>
                  <span class="catalog-article__price <?php echo ($Item->price_old && ($Item->price_old != $Item->price)) ? ' catalog-article__price_new' : ''?>">
                    <span data-role="price-container" itemprop="price" content="<?php echo (float)$Item->price?>" data-v-html="formatPrice(price * amount)">
                      <?php echo Text::formatPrice((float)$Item->price)?>
                    </span>
                    <span itemprop="priceCurrency" content="RUB" class="catalog-article__currency">₽</span>
                  </span>
                </div>
                <div class="catalog-article__available catalog-article__available_<?php echo $Item->available ? '' : 'not-'?>available">
                  <link itemprop="availability" href="http://schema.org/<?php echo $Item->available ? 'InStock' : 'PreOrder'?>" />
                  <?php echo $Item->available ? AVAILABLE : AVAILABLE_CUSTOM?>
                </div>
              </div>
              <!--noindex-->
              <div class="catalog-article__controls">
                <?php if ($Item->available) { ?>
                    <div class="catalog-article__amount-block">
                      <a class="catalog-article__decrement" data-v-on_click="amount -= step; checkAmount();">–</a>
                      <input type="number" class="catalog-article__amount" autocomplete="off" name="amount" min="<?php echo (int)$Item->min ?: 1?>" step="<?php echo (int)$Item->step ?: 1?>" value="<?php echo (int)$Item->min ?: 1?>" data-v-model="amount" data-v-on_change="checkAmount()" />
                      <a class="catalog-article__increment" data-v-on_click="amount += step; checkAmount();">+</a>
                    </div>
                    <button type="button" data-v-on_click="addToCart()" class="catalog-article__add-to-cart">
                      <?php echo TO_CART?>
                    </button>
                    <!--
                    <button type="button" data-v-on_click="toggleCart()" class="catalog-article__add-to-cart" data-v-bind_class="{ 'catalog-article__add-to-cart_active': inCart}" data-v-bind_title="inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'" data-v-html="inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'">
                      <?php echo TO_CART?>
                    </button>
                    -->
                <?php } ?>
                <button type="button" data-v-on_click="toggleFavorites()" class="catalog-article__add-to-favorites" data-v-bind_class="{ 'catalog-article__add-to-favorites_active': inFavorites}" data-v-bind_title="inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'" data-v-html="inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'">
                  <?php echo TO_FAVORITES?>
                </button>
              </div>
              <!--/noindex-->
              <!--noindex-->
              <div class="catalog-article__share">
                <?php echo SHARE?>:
                <div class="ya-share2" style="display: inline-block; vertical-align: middle" data-services="vkontakte,facebook,twitter,whatsapp"></div>
              </div>
              <!--/noindex-->
              <?php
              $propsArr = '';
              foreach ($Item->fields as $fieldURN => $field) {
                  if (!in_array($field->urn, $hiddenProps) &&
                      !in_array($field->datatype, [
                          'image',
                          'file',
                          'material',
                          'checkbox'
                      ])
                  ) {
                      if ($field->doRich()) {
                          $richValues = array_map(function ($val) use ($field) {
                              return $field->doRich($val);
                          }, $field->getValues(true));
                          $textValue = implode(', ', $richValues);
                          switch ($fieldURN) {
                              case 'width':
                              case 'height':
                                  $jsonLd[$fieldURN] = [
                                      '@type' => 'QuantitativeValue',
                                      'value' => $textValue,
                                      'unitCode' => 'CMT',
                                  ];
                                  $propsArr[] = '<div class="catalog-article-props-item">
                                                   <span class="catalog-article-props-item__title">'
                                              .      htmlspecialchars($field->name) . ':
                                                   </span>
                                                   <span class="catalog-article-props-item__value" itemprop="' . $fieldURN . '" itemscope itemtype="http://schema.org/QuantitativeValue">
                                                     <span itemprop="value">'
                                              .        htmlspecialchars($textValue)
                                              .     '</span>
                                                     <meta itemprop="unitCode" content="CMT">
                                                   </span>
                                                 </div>';
                                  break;
                              case 'article':
                                  $jsonLd['productID'] = $textValue;
                                  $jsonLd['sku'] = $textValue;
                                  $propsArr[] = '<div class="catalog-article-props-item">
                                                   <span class="catalog-article-props-item__title">'
                                              .      htmlspecialchars($field->name) . ':
                                                   </span>
                                                   <span class="catalog-article-props-item__value" itemprop="productID">'
                                              .      htmlspecialchars($textValue)
                                              .   '</span>
                                                 </div>';
                                  break;
                              case 'brand':
                                  $jsonLd[$fieldURN] = [
                                      '@type' => 'Brand',
                                      'name' => $textValue,
                                  ];
                                  $propsArr[] = '<div class="catalog-article-props-item">
                                                   <span class="catalog-article-props-item__title">'
                                              .      htmlspecialchars($field->name) . ':
                                                   </span>
                                                   <span class="catalog-article-props-item__value" itemprop="brand" itemscope itemtype="http://schema.org/Brand">
                                                     <span itemprop="name">'
                                              .        htmlspecialchars($textValue)
                                              .     '</span>
                                                   </span>
                                                 </div>';
                                  break;
                              default:
                                  $jsonLd['additionalProperty'][] = [
                                      '@type' => 'PropertyValue',
                                      'name' => $field->name,
                                      'value' => $textValue
                                  ];
                                  $propsArr[] = ' <div class="catalog-article-props-item" itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
                                                    <span class="catalog-article-props-item__title" itemprop="name">'
                                              .       htmlspecialchars($field->name) . ':
                                                    </span>
                                                    <span class="catalog-article-props-item__value" itemprop="value">'
                                              .       htmlspecialchars($textValue)
                                              .    '</span>
                                                  </div>';
                                  break;
                          }
                      }
                  }
              }
              if ($propsArr) {
                  $propsArr = array_map(function ($x) {
                      return str_replace(
                          'catalog-article-props-item"',
                          'catalog-article-props-list__item catalog-article-props-item"',
                          $x
                      );
                  }, $propsArr); ?>
                  <div class="catalog-article__props-list">
                    <div class="catalog-article-props-list">
                      <?php echo implode("\n", $propsArr)?>
                    </div>
                  </div>
              <?php } ?>
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
            $name = isset($Item->fields[$key])
                  ? $Item->fields[$key]->name
                  : '';
            $text = '';
            switch ($key) {
                case 'description':
                    $name = DESCRIPTION;
                    $text = '<div class="catalog-article__description" itemprop="description">'
                          .    (trim($Item->description) ?: '.')
                          . '</div>';
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
                        $text = '<div class="catalog-article__videos-list">
                                   <div class="catalog-article-videos-list">';
                        foreach ($Item->videos as $video) {
                            $ytid = $ytname = '';
                            if (preg_match('/^(.*?)((http(s?):\\/\\/.*?(((\\?|&)v=)|(embed\\/)|(youtu\\.be\\/)))([\\w\\-\\_]+).*?)$/', $video, $regs)) {
                                $ytname = trim($regs[1]);
                                $ytid = trim($regs[10]);
                            }
                            $text .= '<div class="catalog-article-videos-list__item">
                                        <div class="catalog-article-videos-item" itemscope itemtype="http://schema.org/VideoObject">
                                          <meta itemprop="name" content="' . htmlspecialchars($Item->name) . '" />
                                          <meta itemprop="description" content="' . htmlspecialchars($Item->name) . '" />
                                          <meta itemprop="uploadDate" content="' . date('Y-m-d', strtotime($Item->modify_date)) . '" />
                                          <meta itemprop="thumbnailUrl" content="https://i.ytimg.com/vi/' . htmlspecialchars(addslashes($ytid)) . '/hqdefault.jpg" />
                                          <a itemprop="url" class="catalog-article-videos-item__image" href="https://youtube.com/embed/' . $ytid . '" data-lightbox-gallery="v" title="' . htmlspecialchars($ytname) . '">
                                            <img itemprop="thumbnail" src="https://i.ytimg.com/vi/' . htmlspecialchars($ytid) . '/hqdefault.jpg" alt="' . htmlspecialchars($ytname) . '">
                                          </a>
                                        </div>
                                      </div>';
                            $jsonLd = [
                                '@context' => 'http://schema.org',
                                '@type' => 'VideoObject',
                                'name' => $Item->name,
                                'description' => $Item->name,
                                'url' => 'https://youtube.com/embed/' . $ytid,
                                'thumbnailUrl' => 'https://i.ytimg.com/vi/' . addslashes($ytid) . '/hqdefault.jpg',
                                'uploadDate' => date('Y-m-d', strtotime($Item->modify_date)),
                            ];
                            $text .= '<script type="application/ld+json">' . json_encode($jsonLd) . '</script>';
                        }
                        $text .= ' </div>
                                 </div>';
                    }
                    break;
                case 'reviews':
                    if ($comments || $commentFormBlock->id) {
                        $name = REVIEWS . ($comments ? ' (' . count($comments) . ')' : '');
                        $text = '<div class="catalog-article__reviews">
                                   <div class="catalog-article__reviews-list">'
                              .      $commentsListText
                              . '  </div>';
                        if ($commentFormBlock->id) {
                            $text .= '<div class="catalog-article__reviews-form">';
                            ob_start();
                            $commentFormBlock->process($Page);
                            $text .= ob_get_clean();
                            $text .= '</div>';
                        }
                        $text .= '</div>';
                    }
                    break;
                case 'related':
                    if ($Item->related) {
                        $text = '<div class="catalog-article__related">
                                   <div class="catalog-list catalog-list_related">';
                        foreach ($Item->related as $row) {
                            $text .= '<div class="catalog-list__item">';
                            ob_start();
                            Snippet::importByURN('catalog_item')->process([
                                'item' => $row
                            ]);
                            $text .= ob_get_clean();
                            $text .= '</div>';
                        }
                        $text .= ' </div>
                                 </div>';
                    }
                    break;
            }
            if ($text) {
                $tabs[$key] = ['name' => $name, 'description' => $text];
            }
        }
        if ($tabs) {
            ?>
            <ul class="nav nav-tabs catalog-article__tabs-nav-list catalog-article-tabs-nav-list" role="tablist">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <li class="catalog-article-tabs-nav-list__item <?php echo !$i ? ' active' : ''?>">
                    <a class="catalog-article-tabs-nav-item" href="#<?php echo $key?>" aria-controls="<?php echo $key?>" role="tab" data-toggle="tab">
                      <?php echo htmlspecialchars($row['name'])?>
                    </a>
                  </li>
              <?php $i++; } ?>
            </ul>
            <div class="tab-content catalog-article__tabs-list catalog-article-tabs-list">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <div class="catalog-article-tabs-list__item catalog-article-tabs-item tab-pane<?php echo !$i ? ' active' : ''?>" id="<?php echo $key?>">
                    <?php echo $row['description']?>
                  </div>
              <?php $i++; } ?>
            </div>
        <?php } ?>
      </div>
    </div>
    <script type="application/ld+json"><?php echo json_encode($jsonLd)?></script>
    <?php Package::i()->requestJS([
        '//yastatic.net/es5-shims/0.0.2/es5-shims.min.js',
        '//yastatic.net/share2/share.js',
    ]) ?>
<?php } else { ?>
    <div class="catalog">
      <div data-vue-role="catalog-loader" data-vue-inline-template data-v-bind_page="<?php echo (int)$Pages->page?>" data-v-bind_pages="<?php echo (int)$Pages->pages?>" data-v-bind_cart="cart" data-v-bind_favorites="favorites">
        <div>
          <div class="catalog__inner">
            <?php
            if ($Set || $subcats) {
                if ($subcats) {
                    ?>
                    <div class="catalog__categories-list">
                      <div class="catalog-categories-list">
                        <?php foreach ($subcats as $row) { ?>
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
                    <div class="catalog__sort" data-role="catalog-sort">
                      <?php Snippet::importByURN('catalog_sort')->process([
                          'sort' => $sort,
                          'order' => $order,
                          'Block' => $Block,
                      ])?>
                    </div>
                    <div class="catalog__list">
                      <div class="catalog-list" data-role="catalog-list">
                        <?php foreach ($Set as $row) { ?>
                            <div class="catalog-list__item" data-role="catalog-list-item">
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
          <?php if ($Set) {
              $nextPage = min($Pages->pages, $Pages->page + 1);
              if ($nextPage == 1) {
                  $nextPage = '';
              }
              ?>
              <div class="catalog__ajax-loader" data-v-if="busy"></div>
              <div class="catalog__controls">
                <div class="catalog__pagination" data-role="catalog-pagination">
                  <?php Snippet::importByURN('pagination')->process(['pages' => $Pages]); ?>
                </div>
                <div class="catalog__more" data-role="catalog-more" data-v-if="currentPage < pagesTotal">
                  <a href="<?php echo HTTP::queryString('page=' . $nextPage)?>" class="btn btn-primary">
                    <?php echo SHOW_MORE_CATALOG?>
                  </a>
                </div>
              </div>
          <?php } ?>
        </div>
      </div>
    </div>
<?php } ?>
<?php Package::i()->requestJS(['/js/catalog.js'])?>
