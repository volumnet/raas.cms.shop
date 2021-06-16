<?php
/**
 * Виджет каталога
 * @param Page $Page Текущая страница
 * @param Block_Material $Block Текущий блок
 * @param array<Material>|null $Set Набор материалов для отображения
 * @param Material $Item Текущий материал для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\HTTP;
use SOME\Text;
use RAAS\Attachment;
use RAAS\CMS\Package;
use RAAS\CMS\Snippet;

if ($Item) {
    $formatter = new ItemArrayFormatter($Item);
    $itemData = $formatter->format([
        'visImages' => function ($item, $propsCache) {
            return $propsCache['images']['values'] ?: array_map(function ($x) {
                return [
                    'id' => $x->id,
                    'name' => $x->name,
                    'fileURL' => $x->fileURL,
                    'smallURL' => $x->smallURL,
                ];
            }, $item->visImages);
        },
        'available' => function ($item) {
            return (bool)(int)(
                $propsCache['available']['values'] ?
                $propsCache['available']['values'][0] :
                $item->available
            );
        },
        'article',
        'unit',
    ]);

    $host = 'http' . (mb_strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '') . '://'
          . $_SERVER['HTTP_HOST'];
    $jsonLd = [
        '@context' => 'http://schema.org',
        '@type' => 'Product',
        'name' => $Item->name,
        'description' => trim($Item->description) ?: '.',
        'productID' => $itemData['article'],
        'sku' => $itemData['article'],
        'offers' => [
            '@type' => 'Offer',
            'sku' => $itemData['article'],
            'url' => $host . $itemData['url'],
            'price' => (float)$itemData['price'],
            'priceCurrency' => 'RUB',
            'availability' => 'http://schema.org/' . ($Item->available ? 'InStock' : 'PreOrder'),
        ]
    ];
    $Page->headPrefix = 'product: http://ogp.me/ns/product#';
    $Page->headData = ' <meta property="og:title" content="' . htmlspecialchars($Item->name) . '" />
                        <meta property="og:type" content="product.item" />
                        <meta property="og:url" content="' . $host . $itemData['url'] . '" />';
    if ($itemData['visImages']) {
        $Page->headData .= ' <meta property="og:image" content="' . $host . '/' . $Item->visImages[0]->fileURL . '" />';
    }
    ?>
    <div class="catalog">
      <div class="catalog-article" itemscope itemtype="http://schema.org/Product"  data-vue-role="catalog-article" data-v-bind_item="<?php echo htmlspecialchars(json_encode($itemData))?>" data-v-slot="vm" data-id="<?php echo (int)$item->id?>">
        <meta itemprop="name" content="<?php echo htmlspecialchars($Item->name)?>" />
        <?php /* ?>
            <h1 class="catalog-article__title" itemprop="name">
              <?php echo htmlspecialchars($Item->name)?>
            </h1>
        <?php */ ?>
        <div class="catalog-article__inner">
          <?php if ($itemData['visImages']) { ?>
              <div class="catalog-article__images-container">
                <div class="catalog-article__image<?php echo (count($itemData['visImages']) == 1) ? ' catalog-article__image_alone' : ''?>">
                  <?php for ($i = 0; $i < count($Item->visImages); $i++) {
                      $jsonLd['image'][] = '/' . $Item->visImages[$i]->fileURL; ?>
                      <a itemprop="image" href="/<?php echo $Item->visImages[$i]->fileURL?>" <?php echo $i ? 'style="display: none"' : ''?> data-v-bind_style="{display: ((vm.selectedImage == <?php echo $i?>) ? 'block' : 'none')}" data-lightbox-gallery="g">
                        <img loading="lazy" src="/<?php echo Package::i()->tn($Item->visImages[$i]->fileURL, 600, 600, 'frame')?>" alt="<?php echo htmlspecialchars($Item->visImages[$i]->name ?: $row->name)?>" /></a>
                  <?php } ?>
                </div>
                <!--noindex-->
                <div class="catalog-article__images-list<?php echo (count($Item->visImages) == 1) ? ' catalog-article__images-list_alone' : ''?>">
                  <div class="catalog-article-images-list slider slider_horizontal" data-vue-role="raas-slider" data-vue-type="horizontal" data-v-bind_wrap="false" data-v-bind_autoscroll="false" data-v-slot="slider">
                    <a data-v-on_click="slider.prev()" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_prev slider__arrow slider__arrow_prev" data-v-bind_class="{ 'catalog-article-images-list__arrow_active': slider.prevAvailable, 'slider__arrow_active': slider.prevAvailable }"></a>
                    <div class="catalog-article-images-list__inner slider__list" data-role="slider-list">
                      <div class="catalog-article-images-list__list slider-list slider-list_horizontal">
                        <?php for ($i = 0; $i < count($Item->visImages); $i++) { $row = $Item->visImages[$i]; ?>
                            <a class="catalog-article-images-list__item slider-list__item" href="/<?php echo $Item->visImages[$i]->fileURL?>" data-role="slider-item" data-v-bind_class="{ 'catalog-article-images-list__item_active': (slider.activeFrame == <?php echo $i?>), 'slider-list__item_active': (slider.activeFrame == <?php echo $i?>) }" data-v-on_click="clickThumbnail(<?php echo (int)$i?>, $event)" data-lightbox-gallery="tn">
                              <img loading="lazy" src="/<?php echo htmlspecialchars($row->smallURL)?>" alt="<?php echo htmlspecialchars($row->name)?>" />
                            </a>
                        <?php } ?>
                      </div>
                    </div>
                    <a data-v-on_click="slider.next()" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_next slider__arrow slider__arrow_next" data-v-bind_class="{ 'catalog-article-images-list__arrow_active': slider.nextAvailable, 'slider__arrow_active': slider.nextAvailable }"></a>
                  </div>
                </div>
                <!--/noindex-->
              </div>
          <?php } ?>
          <div class="catalog-article__details">
            <div class="catalog-article__article">
              <?php echo ARTICLE_SHORT?>
              <span itemprop="productID">
                <?php echo htmlspecialchars($itemData['article'])?>
              </span>
              <meta itemprop="sku" content="<?php echo htmlspecialchars($itemData['article'])?>" />
            </div>
            <div class="catalog-article__offer" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
              <meta itemprop="sku" content="<?php echo htmlspecialchars($itemData['article'])?>" />
              <link itemprop="url" href="<?php echo htmlspecialchars($host . $itemData['url'])?>" />
              <div class="catalog-article__price-container" data-price="<?php echo (float)$itemData['price']?>">
                <?php if ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) { ?>
                    <span class="catalog-article__price catalog-article__price_old" data-v-html="formatPrice(priceold * amount)">
                      <?php echo Text::formatPrice((float)$itemData['price_old'])?>
                    </span>
                <?php } ?>
                <span class="catalog-article__price <?php echo ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) ? ' catalog-article__price_new' : ''?>">
                  <span data-role="price-container" itemprop="price" content="<?php echo (float)$itemData['price']?>" data-v-html="formatPrice(price * amount)">
                    <?php echo Text::formatPrice((float)$itemData['price'])?>
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
                    <a class="catalog-article__decrement" data-v-on_click="vm.setAmount(parseInt(vm.amount) - parseInt(vm.item.step || 1));">–</a>
                    <input type="number" class="catalog-article__amount" autocomplete="off" name="amount" min="<?php echo (int)$itemData['min'] ?: 1?>" step="<?php echo (int)$itemData['step'] ?: 1?>" value="<?php echo (int)$itemData['min'] ?: 1?>" data-v-bind_value="vm.amount" data-v-on_input="vm.setAmount($event.target.value)" />
                    <a class="catalog-article__increment" data-v-on_click="vm.setAmount(parseInt(vm.amount) + parseInt(vm.item.step || 1))">+</a>
                  </div>
                  <button type="button" data-v-on_click="addToCart()" class="catalog-article__add-to-cart">
                    <?php echo TO_CART?>
                  </button>
                  <!--
                  <button type="button" data-v-on_click="toggleCart()" class="catalog-article__add-to-cart" data-v-bind_class="{ 'catalog-article__add-to-cart_active': vm.inCart}" data-v-bind_title="vm.inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'" data-v-html="vm.inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'">
                    <?php echo TO_CART?>
                  </button>
                  -->
              <?php } ?>
              <button type="button" data-v-on_click="toggleFavorites()" class="catalog-article__add-to-favorites" data-v-bind_class="{ 'catalog-article__add-to-favorites_active': vm.inFavorites}" data-v-bind_title="vm.inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'" data-v-html="vm.inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'">
                <?php echo TO_FAVORITES?>
              </button>
              <button type="button" data-v-on_click="toggleFavorites()" class="catalog-article__add-to-compare" data-v-bind_class="{ 'catalog-article__add-to-compare_active': vm.inCompare}" data-v-bind_title="vm.inCompare ? '<?php echo DELETE_FROM_COMPARISON?>' : '<?php echo TO_COMPARISON?>'" data-v-html="vm.inCompare ? '<?php echo DELETE_FROM_COMPARISON?>' : '<?php echo TO_COMPARISON?>'">
                <?php echo TO_COMPARISON?>
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
            $catalogInterface = new CatalogInterface();
            $propsIds = (array)$catalogInterface->getMetaTemplate($item->urlParent, 'main_props');
            $props = [];
            foreach ($propsIds as $propId) {
                $field = new Material_Field($propId);
                if ($field->id) {
                    $props[] = $field;
                }
            }
            if (!$props) {
                $props = $Item->visFields;
            }
            $propsArr = [];
            foreach ($props as $fieldURN => $field) {
                if (!in_array($field->urn, []) && !in_array($field->datatype, [
                    'image',
                    'file',
                    'material',
                    'checkbox'
                ])) {
                    $fieldName = $field->name;
                    $fieldValues = $field->getValues(true);
                    if ($fieldValues) {
                        $richValues = array_map(function ($val) use ($field) {
                            return $field->doRich($val);
                        }, $fieldValues);
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
                                            .      htmlspecialchars($fieldName) . ':
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
                                            .      htmlspecialchars($fieldName) . ':
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
                                            .      htmlspecialchars($fieldName) . ':
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
                                    'name' => $fieldName,
                                    'value' => $textValue
                                ];
                                $propsArr[] = ' <div class="catalog-article-props-item" itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
                                                  <span class="catalog-article-props-item__title" itemprop="name">'
                                            .       htmlspecialchars($fieldName) . ':
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
            if ($propsArr) { ?>
                <div class="catalog-article__props-list">
                  <div class="catalog-article-props-list">
                    <?php foreach ($propsArr as $propText) { ?>
                        <div class="catalog-article-props-list__item">
                          <?php echo $propText?>
                        </div>
                    <?php } ?>
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
                                            <img loading="lazy" itemprop="thumbnail" src="https://i.ytimg.com/vi/' . htmlspecialchars($ytid) . '/hqdefault.jpg" alt="' . htmlspecialchars($ytname) . '">
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
                    <a class="catalog-article-tabs-nav-item" href="#<?php echo $key?>" aria-controls="<?php echo $key?>" role="tab" data-bs-toggle="tab">
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
    <?php
    Package::i()->requestCSS(['/css/catalog-article.css']);
    Package::i()->requestJS([
        '//yastatic.net/es5-shims/0.0.2/es5-shims.min.js',
        '//yastatic.net/share2/share.js',
        '/js/catalog-article.js'
    ]);
} else { ?>
    <div class="catalog">
      <div data-vue-role="catalog-loader" data-v-bind_page="<?php echo (int)$Pages->page?>" data-v-bind_pages="<?php echo (int)$Pages->pages?>" data-v-slot="vm">
        <div class="catalog__inner">
          <?php
          if ($Set || $subcats) {
              if ($subcats) { ?>
                  <div class="catalog__categories-list">
                    <div class="catalog-categories-list">
                      <?php foreach ($subcats as $row) { ?>
                          <div class="catalog-categories-list__item">
                            <?php Snippet::importByURN('catalog_category')->process(['page' => $row])?>
                          </div>
                      <?php } ?>
                    </div>
                  </div>
              <?php }
              if ($Set) { ?>
                  <div class="catalog__controls" data-role="catalog-controls">
                    <?php Snippet::importByURN('catalog_controls')->process([
                        'sort' => $sort,
                        'order' => $order,
                        'Page' => $Page,
                        'Block' => $Block,
                    ])?>
                  </div>
                  <div class="catalog__list">
                    <div class="catalog-list" data-role="loader-list" data-vue-role="catalog-list">
                      <?php foreach ($Set as $row) { ?>
                          <div class="catalog-list__item" data-role="loader-list-item">
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
            <div class="catalog__pagination-outer">
              <div class="catalog__pagination" data-role="loader-pagination">
                <?php Snippet::importByURN('pagination')->process(['pages' => $Pages]); ?>
              </div>
              <div class="catalog__more" data-role="loader-more" data-v-if="vm.currentPage < vm.pagesTotal">
                <a href="<?php echo HTTP::queryString('page=' . $nextPage)?>" class="btn btn-primary">
                  <?php echo SHOW_MORE_CATALOG?>
                </a>
              </div>
            </div>
        <?php } ?>
      </div>
      <?php if ($description = $Page->_description) { ?>
          <div class="catalog__description">
            <?php echo $description?>
          </div>
      <?php } ?>
    </div>
    <?php
    Package::i()->requestCSS(['/css/catalog-list.css']);
    Package::i()->requestJS(['/js/catalog-list.js']);
}
