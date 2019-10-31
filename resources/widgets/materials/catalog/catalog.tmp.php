<?php
/**
 * Виджет каталога
 * @param Page $Page Текущая страница
 * @param Block_Material $Block Текущий блок
 * @param array<Material>|null $Set Набор материалов для отображения
 * @param Material $Item Текущий материал для отображения
 */
namespace RAAS\CMS;

use SOME\Text;
use RAAS\Attachment;

$ignoredFields = [
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
];

if ($Item) {
    ?>
    <div class="catalog">
      <div class="catalog-article" itemscope itemtype="http://schema.org/Product">
        <meta itemprop="name" content="<?php echo htmlspecialchars($Item->name)?>" />

        <div class="catalog-article__inner">
          <?php if ($Item->visImages) { ?>
              <div class="catalog-article__images-container">
                <?php if (count($Item->visImages) > 1) { ?>
                    <div class="catalog-article__images-list">
                      <div class="catalog-article-images-list">
                        <a href="#" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_prev" data-role="slider-prev"></a>
                        <div class="catalog-article-images-list__inner" data-role="slider" data-slider-carousel="jcarousel" data-slider-vertical="true" data-slider-wrap="circular" data-slider-duration="500">
                          <div class="catalog-article-images-list__list">
                            <?php for ($i = 0; $i < count($Item->visImages); $i++) { $row = $Item->visImages[$i]; ?>
                                <div class="catalog-article-images-list__item" data-v-on_click="selectedImage = <?php echo (int)$i?>">
                                  <img src="/<?php echo htmlspecialchars($row->smallURL)?>" alt="<?php echo htmlspecialchars($row->name)?>" /></div>
                            <?php } ?>
                          </div>
                        </div>
                        <a href="#" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_next" data-role="slider-next"></a>
                      </div>
                    </div>
                <?php } ?>
                <!--noindex-->
                <div class="catalog-article__image">
                  <?php for ($i = 0; $i < count($Item->visImages); $i++) { ?>
                      <a itemprop="image" href="/<?php echo $Item->visImages[$i]->fileURL?>" <?php echo $i ? 'style="display: none"' : ''?> data-v-bind_style="{display: ((selectedImage == <?php echo $i?>) ? 'block' : 'none')}" data-lightbox-gallery="g">
                        <img src="/<?php echo Package::i()->tn($Item->visImages[$i]->fileURL, 600, 600, 'frame')?>" alt="<?php echo htmlspecialchars($Item->visImages[$i]->name ?: $row->name)?>" /></a>
                  <?php } ?>
                </div>
                <!--/noindex-->
              </div>
          <?php } ?>
          <div class="catalog-article__details">
            <div class="catalog-article__article">
              <?php echo ARTICLE_SHORT?>
              <span itemprop="productID">
                <?php echo htmlspecialchars($Item->article)?>
              </span>
            </div>
            <div class="catalog-article__offer" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
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
                    <input type="number" class="catalog-article__amount" autocomplete="off" name="amount" min="<?php echo (int)$item->min ?: 1?>" step="<?php echo (int)$item->step ?: 1?>" value="<?php echo (int)$item->min ?: 1?>" data-v-model="amount" data-v-on_change="checkAmount()" />
                    <a class="catalog-article__increment" data-v-on_click="amount += step; checkAmount();">+</a>
                  </div>
                  <button type="button" data-v-on_click="addToCart()" class="catalog-article__add-to-cart">
                    <?php echo TO_CART?>
                  </button>
                  <!-- <button type="button" data-v-on_click="toggleCart()" class="catalog-article__add-to-cart" data-v-bind_class="{active: inCart}" data-v-bind_title="inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'" data-v-html="inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'">
                    <?php echo TO_CART?>
                  </button> -->
              <?php } ?>
              <button type="button" data-v-on_click="toggleFavorites()" data-v-bind_class="{active: inFavorites}" class="catalog-article__add-to-favorites" data-v-bind_title="inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'" data-v-html="inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'">
                <?php echo TO_FAVORITES?>
              </button>
            </div>
            <!--/noindex-->
            <!--noindex-->
            <div class="catalog-article__share">
              <script type="text/javascript" src="//yastatic.net/share/share.js" charset="utf-8"></script>
              <?php echo SHARE?>: <div class="yashare-auto-init" style="display: inline-block; vertical-align: middle" data-yashareL10n="ru" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki,moimir" data-yashareTheme="counter"></div>
            </div>
            <!--/noindex-->
            <?php
            $propsArr = '';
            foreach ($Item->fields as $fieldURN => $field) {
                if (!in_array($field->urn, $ignoredFields) &&
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
                                $propsArr[] = '<div class="catalog-article-props-item">
                                                 <span class="catalog-article-props-item__title">'
                                            .      htmlspecialchars($field->name) . ':
                                                 </span>
                                                 <span class="catalog-article-props-item__value" itemprop="' . $fieldURN . '" itemtype="http://schema.org/QuantitativeValue">
                                                   <span itemprop="value">'
                                            .        htmlspecialchars($textValue)
                                            .     '</span>
                                                 </span>
                                               </div>';
                                break;
                            case 'article':
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
                        'catalog-article-props-item',
                        'catalog-article-props-list__item catalog-article-props-item',
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
                          .    trim($Item->description)
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
                            if (!$ytname) {
                                $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $ytid . '&key=AIzaSyCJgMFQqq6Ax9WlGhuslTz4viyG3RbPEic';
                                $json = file_get_contents($url);
                                $json = json_decode($json, true);
                                if (isset($json['items'][0]['snippet']['title'])) {
                                    $ytname = trim($json['items'][0]['snippet']['title']);
                                }
                            }
                            $text .= '<div class="catalog-article-videos-list__item">
                                        <div class="catalog-article-videos-item">
                                          <a class="catalog-article-videos-item__image" href="https://youtube.com/embed/' . $ytid . '" data-lightbox-gallery="v" title="' . htmlspecialchars($ytname) . '">
                                            <img src="https://i.ytimg.com/vi/' . htmlspecialchars($ytid) . '/hqdefault.jpg" alt="' . htmlspecialchars($ytname) . '">
                                          </a>
                                        </div>
                                      </div>';

                            $text .= '</div>';
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
            <div class="tab-content catalog-article__tabs-list catalog-article-tabs-list" style="padding: 15px 0;">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <div class="catalog-article-tabs-list__item catalog-article-tabs-item tab-pane<?php echo !$i ? ' active' : ''?>" id="<?php echo $key?>">
                    <?php echo $row['description']?>
                  </div>
              <?php $i++; } ?>
            </div>
        <?php } ?>
      </div>
    </div>
    <?php
    $vueData = [
        'id' => (int)$Item->id,
        'meta' => '',
        'price' => (float)$Item->price,
        'priceold' => (float)($Item->price_old ?: $Item->price),
        'amount' => (int)$Item->step ?: 1,
        'min' => (int)$Item->min ?: 1,
        'step' => (int)$Item->step ?: 1,
        'selectedImage' => 0,
        'inCart' => false,
        'inFavorites' => false,
    ];
    ?>
    <script>
    jQuery(document).ready(function($) {
        raasShopCatalogArticle = new Vue({
            el: '.catalog-article',
            data: function () {
                return <?php echo json_encode($vueData)?>;
            },
            mounted: function () {
                var self = this;
                window.setTimeout(function () {
                    window.lightBoxInit(true)
                }, 0);
                $(document).on('raas.shop.cart-updated', function (e, data) {
                    if (data.id == 'cart') {
                        var inCart = false;
                        if ((data.data.items)) {
                            for (var item of data.data.items) {
                                if ((item.id == self.id) && (item.meta == self.meta)) {
                                    inCart = true;
                                    break;
                                }
                            }
                        }
                        self.inCart = inCart;
                    } else if (data.id == 'favorites') {
                        var inFavorites = false;
                        if ((data.data.items)) {
                            for (var item of data.data.items) {
                                if (item.id == self.id) {
                                    inFavorites = true;
                                    break;
                                }
                            }
                        }
                        self.inFavorites = inFavorites;
                    }
                })
            },
            methods: {
                formatPrice: window.formatPrice,
                checkAmount: function () {
                    this.amount = Math.max(this.min, this.amount);
                },
                toggleFavorites: function () {
                    if (!this.inFavorites) {
                        $.RAAS.Shop.itemAddedToFavoritesModal.modal('show');
                    } else {
                        $.RAAS.Shop.itemDeletedFromFavoritesModal.modal('show');
                    }
                    $.RAAS.Shop.ajaxFavorites.set(this.id, this.inFavorites ? 0 : 1, '');
                    return false;
                },
                addToCart: function () {
                    $.RAAS.Shop.itemAddedToCartModal.modal('show');
                    $.RAAS.Shop.ajaxCart.add(this.id, this.amount, this.meta, this.price);
                    return false;
                },
                toggleCart: function () {
                    if (!this.inCart) {
                        $.RAAS.Shop.itemAddedToCartModal.modal('show');
                    } else {
                        $.RAAS.Shop.itemDeletedFromCartModal.modal('show');
                    }
                    $.RAAS.Shop.ajaxCart.set(this.id, this.inCart ? 0 : 1, '');
                    return false;
                },
            },
        })
    });
    </script>
<?php } else { ?>
    <div class="catalog">
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
      <?php if ($Set) { ?>
          <div class="catalog__pagination" data-pages="<?php echo $Pages->pages?>">
            <?php Snippet::importByURN('pagination')->process(['pages' => $Pages]); ?>
          </div>
      <?php } ?>
    </div>
<?php } ?>
