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
use RAAS\CMS\Material_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Snippet;

if ($Item) {
    $formatter = new ItemArrayFormatter($Item);
    $itemData = $formatter->format([
        'visImages' => function ($item) {
            return array_map(function ($x) {
                return [
                    'id' => $x->id,
                    'name' => $x->name,
                    'fileURL' => $x->fileURL,
                    'smallURL' => $x->smallURL,
                ];
            }, (array)$item->visImages);
        },
        'modify_date',
        'available',
        'article',
        'unit',
        'videos',
    ]);
    $itemData['videos'] = (array)$itemData['videos'];
    $photoVideo = (array)$itemData['visImages'];
    foreach ($itemData['videos'] as $video) {
        $ytid = $ytname = '';
        if (preg_match('/^(.*?)((http(s?):\\/\\/.*?(((\\?|&)v=)|(embed\\/)|(youtu\\.be\\/)))([\\w\\-\\_]+).*?)$/', $video, $regs)) {
            $ytname = trim($regs[1]);
            $ytid = trim($regs[10]);
        }
        if ($ytid) {
            $videoEntry = [
                'ytid' => $ytid,
            ];
            if ($ytname) {
                $videoEntry['name'] = $ytname;
            }
            $photoVideo[] = $videoEntry;
        }
    }

    $host = 'http' . (mb_strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '') . '://'
          . $_SERVER['HTTP_HOST'];
    $jsonLd = [
        '@context' => 'http://schema.org',
        '@type' => 'Product',
        'name' => $itemData['name'],
        'description' => trim($Item->description) ?: '.',
        'productID' => $itemData['article'],
        'sku' => $itemData['article'],
        'offers' => [
            '@type' => 'Offer',
            'sku' => $itemData['article'],
            'url' => $host . $itemData['url'],
            'price' => (float)$itemData['price'],
            'priceCurrency' => 'RUB',
            'availability' => 'http://schema.org/' . ($itemData['available'] ? 'InStock' : 'PreOrder'),
        ]
    ];
    $Page->headPrefix = 'product: http://ogp.me/ns/product#';
    $Page->headData = ' <meta property="og:title" content="' . htmlspecialchars($itemData['name']) . '" />
                        <meta property="og:type" content="product.item" />
                        <meta property="og:url" content="' . $host . $itemData['url'] . '" />';
    if ($itemData['visImages']) {
        $Page->headData .= ' <meta property="og:image" content="' . $host . '/' . $itemData['visImages'][0]['fileURL'] . '" />';
    }
    ?>
    <div class="catalog">
      <div class="catalog-article" itemscope itemtype="http://schema.org/Product"  data-vue-role="catalog-article" data-v-bind_item="<?php echo htmlspecialchars(json_encode($itemData))?>" data-v-slot="vm" data-id="<?php echo (int)$Item->id?>">
        <?php /*
        <meta itemprop="name" content="<?php echo htmlspecialchars($itemData['name'])?>" />
        */ ?>
        <div class="catalog-article__inner">
          <div class="catalog-article__images-container">
            <div class="catalog-article__image<?php echo (count($photoVideo) <= 1) ? ' catalog-article__image_alone' : ''?>">
              <?php foreach ($photoVideo as $i => $row) {
                  if ($ytid = $row['ytid']) {
                      $videoJsonLd = [
                          '@context' => 'http://schema.org',
                          '@type' => 'VideoObject',
                          'name' => $itemData['name'],
                          'description' => $itemData['name'],
                          'url' => 'https://youtube.com/embed/' . $ytid,
                          'thumbnailUrl' => 'https://i.ytimg.com/vi/' . addslashes($ytid) . '/hqdefault.jpg',
                          'uploadDate' => date('Y-m-d', strtotime($itemData['modify_date'])),
                      ];
                      ?>
                      <div itemscope itemtype="http://schema.org/VideoObject" <?php echo $i ? 'style="display: none"' : ''?> data-v-bind_style="{display: ((vm.selectedImage == <?php echo $i?>) ? 'block' : 'none')}">
                        <meta itemprop="name" content="<?php echo htmlspecialchars($itemData['name'])?>" />
                        <meta itemprop="description" content="<?php echo htmlspecialchars($itemData['name'])?>" />
                        <meta itemprop="uploadDate" content="<?php echo date('Y-m-d', strtotime($itemData['modify_date']))?>" />
                        <meta itemprop="thumbnailUrl" content="https://i.ytimg.com/vi/<?php echo htmlspecialchars(addslashes($ytid))?>/hqdefault.jpg" />
                        <a itemprop="url" class="catalog-article__image-video" href="https://youtube.com/embed/<?php echo $ytid?>" data-lightbox-gallery="catalog-article<?php echo (int)$Block->id?>__image" title="<?php echo htmlspecialchars($row['name'])?>">
                          <img loading="lazy" itemprop="thumbnail" src="https://i.ytimg.com/vi/<?php echo htmlspecialchars($ytid)?>/hqdefault.jpg" alt="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>">
                        </a>
                      </div>
                      <script type="application/ld+json"><?php echo json_encode($videoJsonLd)?></script>
                  <?php } else {
                      $jsonLd['image'][] = '/' . $row['fileURL']; ?>
                      <a itemprop="image" href="/<?php echo $row['fileURL']?>" <?php echo $i ? 'style="display: none"' : ''?> data-v-bind_style="{display: ((vm.selectedImage == <?php echo $i?>) ? 'block' : 'none')}" data-lightbox-gallery="catalog-article<?php echo (int)$Block->id?>__image">
                        <img loading="lazy" src="/<?php echo Package::i()->tn($row['fileURL'], 600, 600, 'frame')?>" alt="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>" /></a>
                  <?php }
              }
              if (!$photoVideo) { ?>
                  <img loading="lazy" src="/files/cms/common/image/design/nophoto.jpg" alt="" />
              <?php } ?>
            </div>
            <!--noindex-->
            <div class="catalog-article__images-list<?php echo (count($photoVideo) <= 1) ? ' catalog-article__images-list_alone' : ''?>">
              <div class="catalog-article-images-list slider slider_horizontal" data-vue-role="raas-slider" data-vue-type="horizontal" data-v-bind_wrap="false" data-v-bind_autoscroll="false" data-v-slot="slider">
                <a data-v-on_click="slider.prev()" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_prev slider__arrow slider__arrow_prev" data-v-bind_class="{ 'catalog-article-images-list__arrow_active': slider.prevAvailable, 'slider__arrow_active': slider.prevAvailable }"></a>
                <div class="catalog-article-images-list__inner slider__list" data-role="slider-list">
                  <div class="catalog-article-images-list__list slider-list slider-list_horizontal">
                    <?php foreach ($photoVideo as $i => $row) {
                        if ($row['ytid']) {
                            $image = 'https://i.ytimg.com/vi/' . htmlspecialchars($ytid) . '/hqdefault.jpg';
                            $href = 'https://youtube.com/embed/' . $ytid;
                        } else {
                            $image = '/' . $row['smallURL'];
                            $href = '/' . $row['fileURL'];
                        }
                        ?>
                        <div class="catalog-article-images-list__item slider-list__item">
                          <a class="catalog-article-images-item<?php echo $row['ytid'] ? ' catalog-article-images-item_video' : ''?>" href="<?php echo htmlspecialchars($href)?>" data-role="slider-item" data-v-bind_class="{ 'catalog-article-images-list__item_active': (slider.activeFrame == <?php echo $i?>), 'slider-list__item_active': (slider.activeFrame == <?php echo $i?>) }" data-v-on_click="vm.clickThumbnail(<?php echo (int)$i?>, $event)" data-lightbox-gallery="catalog-article<?php echo (int)$Block->id?>__images">
                            <img loading="lazy" src="<?php echo htmlspecialchars($image)?>" alt="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>" />
                          </a>
                        </div>
                    <?php }
                    if (!$photoVideo) { ?>
                        <span class="catalog-article-images-list__item slider-list__item" data-role="slider-item" data-v-bind_class="{ 'catalog-article-images-list__item_active': true, 'slider-list__item_active': true }">
                          <img loading="lazy" src="/files/cms/common/image/design/nophoto.jpg" alt="" />
                        </span>
                    <?php } ?>
                  </div>
                </div>
                <a data-v-on_click="slider.next()" class="catalog-article-images-list__arrow catalog-article-images-list__arrow_next slider__arrow slider__arrow_next" data-v-bind_class="{ 'catalog-article-images-list__arrow_active': slider.nextAvailable, 'slider__arrow_active': slider.nextAvailable }"></a>
              </div>
            </div>
            <!--/noindex-->
          </div>
          <div class="catalog-article__details">
            <div class="catalog-article__header">
              <div class="catalog-article__header-left">
                <h1 class="catalog-article__title" itemprop="name">
                  <?php echo htmlspecialchars($itemData['name'])?>
                </h1>
                <div class="catalog-article__article">
                  <span class="catalog-article__article-title">
                    <?php echo ARTICLE_SHORT?>
                  </span>
                  <span class="catalog-article__article-value" itemprop="productID">
                    <?php echo htmlspecialchars($itemData['article'])?>
                  </span>
                  <meta itemprop="sku" content="<?php echo htmlspecialchars($itemData['article'])?>" />
                </div>
              </div>
              <div class="catalog-article__header-right">
                <div class="catalog-article__brand">
                  <?php if ($brand = $Item->brand) { ?>
                      <a href="<?php echo htmlspecialchars($brand->url)?>" class="catalog-article-brand" title="<?php echo htmlspecialchars($brand->name)?>">
                        <?php if ($brand->image->id) { ?>
                            <div class="catalog-article-brand__image">
                              <img src="/<?php echo htmlspecialchars($brand->image->fileURL)?>" alt="<?php echo htmlspecialchars($brand->name)?>">
                            </div>
                        <?php } else { ?>
                            <div class="catalog-article-brand__name">
                              <?php echo htmlspecialchars($brand->name)?>
                            </div>
                        <?php } ?>
                      </a>
                  <?php } ?>
                </div>
              </div>
            </div>
            <?php

            /*** ХАРАКТЕРИСТИКИ ***/

            $catalogInterface = new CatalogInterface();
            $propsIds = (array)$catalogInterface->getMetaTemplate($Item->urlParent, 'main_props');
            $props = [];
            foreach ($propsIds as $propId) {
                $field = new Material_Field($propId);
                if ($field->id) {
                    $field = $field->deepClone();
                    $field->Owner = $Item;
                    $props[$field->urn] = $field;
                }
            }
            if (!$props) {
                $props = $Item->visFields;
            }
            $props = array_filter($props, function ($field) {
                return !in_array($field->urn, []) && !in_array(
                    $field->datatype,
                    ['image', 'file', 'material', 'checkbox']
                );
            });

            $propsArr = [];
            foreach ($props as $fieldURN => $field) {
                $fieldName = $field->name;
                $fieldValues = $field->getValues(true);
                if ($fieldValues) {
                    $richValues = array_map(function ($val) use ($field) {
                        return $field->doRich($val);
                    }, $fieldValues);
                    $textValue = implode(', ', $richValues);
                    ob_start();
                    switch ($fieldURN) {
                        case 'length':
                        case 'width':
                        case 'height':
                        case 'weight':
                            if ($fieldURN == 'length') {
                                $schemaOrgURN = 'depth';
                            } else {
                                $schemaOrgURN = $fieldURN;
                            }
                            if ($fieldURN == 'weight') {
                                $unitCode = 'KGM';
                            } else {
                                $unitCode = 'CMT';
                            }
                            $jsonLd[$schemaOrgURN] = [
                                '@type' => 'QuantitativeValue',
                                'value' => $textValue,
                                'unitCode' => $unitCode,
                            ];
                            ?>
                            <div class="catalog-article-props-item">
                              <span class="catalog-article-props-item__title">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span class="catalog-article-props-item__value" itemprop="<?php echo $schemaOrgURN?>" itemscope itemtype="http://schema.org/QuantitativeValue">
                                <span itemprop="value">
                                  <?php echo htmlspecialchars($textValue)?>
                                </span>
                                <meta itemprop="unitCode" content="<?php echo $unitCode?>">
                              </span>
                            </div>
                            <?php
                            break;
                        case 'article':
                            $jsonLd['productID'] = $textValue;
                            $jsonLd['sku'] = $textValue;
                            ?>
                            <div class="catalog-article-props-item">
                              <span class="catalog-article-props-item__title">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span class="catalog-article-props-item__value" itemprop="productID">
                                <?php echo htmlspecialchars($textValue)?>
                              </span>
                            </div>
                            <?php
                            break;
                        case 'brand':
                            $jsonLd[$fieldURN] = [
                                '@type' => 'Brand',
                                'name' => $textValue,
                            ];
                            ?>
                            <div class="catalog-article-props-item">
                              <span class="catalog-article-props-item__title">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span class="catalog-article-props-item__value" itemprop="brand" itemscope itemtype="http://schema.org/Brand">
                                <span itemprop="name">
                                  <?php echo htmlspecialchars($textValue)?>
                                </span>
                              </span>
                            </div>
                            <?php
                            break;
                        default:
                            $jsonLd['additionalProperty'][] = [
                                '@type' => 'PropertyValue',
                                'name' => $fieldName,
                                'value' => $textValue
                            ];
                            ?>
                            <div class="catalog-article-props-item" itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
                              <span class="catalog-article-props-item__title" itemprop="name">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span class="catalog-article-props-item__value" itemprop="value">'
                                <?php echo htmlspecialchars($textValue)?>
                              </span>
                            </div>
                            <?php
                            break;
                    }
                    $propsArr[] = ob_get_clean();
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

            <?php /*** ПРЕДЛОЖЕНИЕ ***/ ?>
            <div class="catalog-article__offer" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
              <meta itemprop="sku" content="<?php echo htmlspecialchars($itemData['article'])?>" />
              <link itemprop="url" href="<?php echo htmlspecialchars($host . $itemData['url'])?>" />
              <div class="catalog-article__price-container" data-price="<?php echo (float)$itemData['price']?>">
                <?php if ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) { ?>
                    <span class="catalog-article__price catalog-article__price_old" data-v-if="vm.item.price_old && (vm.item.price_old > vm.item.price)">
                      <span data-v-html="vm.formatPrice(vm.item.price_old * vm.amount)">
                        <?php echo Text::formatPrice((float)$itemData['price_old'])?>
                      </span>
                    </span>
                <?php } ?>
                <span class="catalog-article__price <?php echo ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) ? ' catalog-article__price_new' : ''?>">
                  <span data-role="price-container" itemprop="price" content="<?php echo (float)$itemData['price']?>" data-v-html="formatPrice(vm.item.price * vm.amount)">
                    <?php echo Text::formatPrice((float)$itemData['price'])?>
                  </span>
                  <span itemprop="priceCurrency" content="RUB" class="catalog-article__currency">₽</span>
                </span>
                <?php if ($itemData['unit'] && !stristr($itemData['unit'], 'шт')) { ?>
                    <span class="catalog-article__unit">
                      / <?php echo htmlspecialchars($itemData['unit'])?>
                    </span>
                <?php } ?>
              </div>
              <div class="catalog-article__available catalog-article__available_<?php echo $itemData['available'] ? '' : 'not-'?>available">
                <link itemprop="availability" href="http://schema.org/<?php echo $itemData['available'] ? 'InStock' : 'PreOrder'?>" />
                <?php echo $itemData['available'] ? AVAILABLE : AVAILABLE_CUSTOM?>
              </div>
            </div>
            <!--noindex-->
            <?php if ($itemData['available']) { ?>
                <div class="catalog-article__add-to-cart-outer">
                  <div class="catalog-article__amount-block">
                    <a class="catalog-article__decrement" data-v-on_click="vm.setAmount(parseInt(vm.amount) - parseInt(vm.item.step || 1));">–</a>
                    <input type="number" class="form-control catalog-article__amount" autocomplete="off" name="amount" min="<?php echo (int)$itemData['min'] ?: 1?>" step="<?php echo (int)$itemData['step'] ?: 1?>" value="<?php echo (int)$itemData['min'] ?: 1?>" data-v-bind_value="vm.amount" data-v-on_input="vm.setAmount($event.target.value)" />
                    <a class="catalog-article__increment" data-v-on_click="vm.setAmount(parseInt(vm.amount) + parseInt(vm.item.step || 1))">+</a>
                  </div>
                  <button type="button" data-v-on_click="vm.addToCart()" class="btn btn-primary catalog-article__add-to-cart" data-v-bind_class="{ 'catalog-article__add-to-cart_active': vm.inCart}">
                    <?php echo TO_CART?>
                  </button>
                  <!--
                  <button type="button" data-v-on_click="vm.toggleCart()" class="btn btn-primary catalog-article__add-to-cart" data-v-bind_class="{ 'catalog-article__add-to-cart_active': vm.inCart}" data-v-bind_title="vm.inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'" data-v-html="vm.inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'">
                    <?php echo TO_CART?>
                  </button>
                  -->
                </div>
            <?php } ?>
            <div class="catalog-article__controls">
              <button type="button" data-v-on_click="vm.toggleFavorites()" class="catalog-article__add-to-favorites" data-v-bind_class="{ 'catalog-article__add-to-favorites_active': vm.inFavorites}" data-v-bind_title="vm.inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'" data-v-html="vm.inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'">
                <?php echo TO_FAVORITES?>
              </button>
              <button type="button" data-v-on_click="vm.toggleCompare()" class="catalog-article__add-to-compare" data-v-bind_class="{ 'catalog-article__add-to-compare_active': vm.inCompare}" data-v-bind_title="vm.inCompare ? '<?php echo DELETE_FROM_COMPARISON?>' : '<?php echo TO_COMPARISON?>'" data-v-html="vm.inCompare ? '<?php echo DELETE_FROM_COMPARISON?>' : '<?php echo TO_COMPARISON?>'">
                <?php echo TO_COMPARISON?>
              </button>
            </div>
            <!--/noindex-->

            <!--noindex-->
            <div class="catalog-article__share">
              <div class="catalog-article__share-title">
                <?php echo SHARE?>:
              </div>
              <div class="catalog-article__share-inner ya-share2" style="display: inline-block; vertical-align: middle" data-services="vkontakte,facebook,twitter,whatsapp"></div>
            </div>
            <!--/noindex-->
          </div>
        </div>
        <?php

        /*** ВКЛАДКИ ***/

        $tabs = [];
        foreach ([
            'description' => DESCRIPTION,
            'files',
            'reviews' => REVIEWS . ($comments ? ' (' . count($comments) . ')' : ''),
            'faq' => FAQ . ($faq ? ' (' . count($faq) . ')' : ''),
        ] as $key => $name) {
            if (is_numeric($key)) {
                $key = $name;
                $name = '';
                if ($field = $Item->fields[$key]) {
                    $name = $field->name;
                }
            }
            $text = '';
            ob_start();
            switch ($key) {
                case 'description':
                    ?>
                    <div class="catalog-article__description" itemprop="description">
                      <?php echo (trim($Item->description) ?: '.')?>
                    </div>
                    <?php
                    break;
                case 'files':
                    if ($files = $Item->files) { ?>
                        <div class="catalog-article-files-list">
                          <?php foreach ($files as $file) { ?>
                              <div class="catalog-article-files-list__item">
                                <a href="/<?php echo htmlspecialchars($file->fileURL)?>" class="catalog-article-files-item catalog-article-files-item_<?php echo mb_strtolower(pathinfo($file->fileURL, PATHINFO_EXTENSION))?>">
                                  <?php echo htmlspecialchars($file->name ?: basename($file->fileURL))?>
                                </a>
                              </div>
                          <?php } ?>
                        </div>
                    <?php }
                    break;
                case 'reviews':
                    if ($comments || $commentFormBlock->id) { ?>
                        <div class="catalog-article__reviews-list">
                          <?php echo $commentsListText?>
                        </div>
                        <?php if ($commentFormBlock->id) { ?>
                            <div class="catalog-article__reviews-form">
                              <?php $commentFormBlock->process($Page)?>
                            </div>
                        <?php } ?>
                    <?php }
                    break;
                case 'faq':
                    if ($faq || $faqFormBlock->id) { ?>
                        <div class="catalog-article__faq-list">
                          <?php echo $faqListText?>
                        </div>
                        <?php if ($faqFormBlock->id) { ?>
                            <div class="catalog-article__faq-form">
                              <?php $faqFormBlock->process($Page)?>
                            </div>
                        <?php } ?>
                    <?php }
                    break;
            }
            $text = trim(ob_get_clean());
            if ($text) {
                $tabs[$key] = ['name' => $name, 'description' => $text];
            }
        }
        if ($tabs) { ?>
            <div class="catalog-article__tabs-nav-list">
              <ul class="nav nav-tabs catalog-article-tabs-nav-list" role="tablist">
                <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                    <li class="nav-item catalog-article-tabs-nav-list__item">
                      <a class="catalog-article-tabs-nav-item nav-link<?php echo !$i ? ' active' : ''?>" href="#<?php echo $key?>" aria-controls="<?php echo $key?>" role="tab" data-bs-toggle="tab" data-bs-target="#<?php echo $key?>">
                        <?php echo htmlspecialchars($row['name'])?>
                      </a>
                    </li>
                <?php $i++; } ?>
              </ul>
            </div>
            <div class="catalog-article__tabs-list">
              <div class="catalog-article-tabs-list tab-content">
                <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                    <div class="tab-pane fade show catalog-article-tabs-list__item <?php echo !$i ? ' active' : ''?>" id="<?php echo $key?>" role="tabpanel">
                      <div class="catalog-article-tabs-item">
                        <?php echo $row['description']?>
                      </div>
                    </div>
                <?php $i++; } ?>
              </div>
            </div>
        <?php } ?>

        <?php if ($related = $Item->related) { ?>
            <div class="catalog-article__related">
              <div class="catalog-article-related">
                <div class="catalog-article-related__title h2">
                  <?php echo htmlspecialchars($Item->fields['related']->name)?>
                </div>
                <div class="catalog-article-related__inner slider slider_horizontal" data-vue-role="raas-slider" data-vue-type="horizontal" data-v-bind_wrap="true" data-v-bind_autoscroll="true" data-v-slot="slider">
                  <a data-v-on_click="slider.prev()" class="catalog-article-related__arrow catalog-article-related__arrow_prev slider__arrow slider__arrow_prev" data-v-bind_class="{ 'catalog-article-related__arrow_active': slider.prevAvailable, 'slider__arrow_active': slider.prevAvailable }"></a>
                  <div class="catalog-article-related__list slider__list" data-role="slider-list">
                    <div class="catalog-article-related-list slider-list slider-list_horizontal">
                      <?php foreach ((array)$related as $i => $row) { ?>
                          <div class="catalog-article-related-list__item slider-list__item" data-role="slider-item" data-v-bind_class="{ 'catalog-article-related-list__item_active': (slider.activeFrame == <?php echo $i?>), 'slider-list__item_active': (slider.activeFrame == <?php echo $i?>) }">
                            <?php Snippet::importByURN('catalog_item')->process(['item' => $row]); ?>
                          </div>
                      <?php } ?>
                    </div>
                  </div>
                  <a data-v-on_click="slider.next()" class="catalog-article-related__arrow catalog-article-related__arrow_next slider__arrow slider__arrow_next" data-v-bind_class="{ 'catalog-article-related__arrow_active': slider.nextAvailable, 'slider__arrow_active': slider.nextAvailable }"></a>
                </div>
              </div>
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
