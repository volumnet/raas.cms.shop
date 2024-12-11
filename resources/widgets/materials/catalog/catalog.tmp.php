<?php
/**
 * Каталог продукции
 * @param Page $Page Текущая страница
 * @param Block_Material $Block Текущий блок
 * @param array<Material>|null $Set Набор материалов для отображения
 * @param Material $Item Текущий материал для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\HTTP;
use SOME\Text;
use RAAS\AssetManager;
use RAAS\Attachment;
use RAAS\CMS\Field;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Snippet;

if ($Item) {
    $formatter = new ItemArrayFormatter($Item);
    $itemData = $formatter->format();
    $photoVideo = array_merge(
        (array)$itemData['visImages'],
        (array)$itemData['videos']
    );

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
        $Page->headData .= ' <meta
                               property="og:image"
                               content="' . $host . '/' . $itemData['visImages'][0]['fileURL'] . '"
                             />';
    }
    ?>
    <div class="catalog">
      <div
        class="catalog-article"
        itemscope
        itemtype="http://schema.org/Product"
        data-vue-role="catalog-article"
        data-v-bind_item="<?php echo htmlspecialchars(json_encode($itemData))?>"
        data-v-bind_bind-amount-to-cart="true"
        data-v-slot="vm"
        data-id="<?php echo (int)$Item->id?>"
      >
        <?php /*
        <meta itemprop="name" content="<?php echo htmlspecialchars($itemData['name'])?>" />
        */ ?>
        <div class="catalog-article__inner">
          <div class="catalog-article__images-container">
            <div class="catalog-article__image">
              <?php foreach ($photoVideo as $i => $row) {
                  if (($row['isVideo'] ?? false)) {
                      $videoJsonLd = [
                          '@context' => 'http://schema.org',
                          '@type' => 'VideoObject',
                          'name' => $row['name'] ?: $itemData['name'],
                          'description' => $row['name'] ?: $itemData['name'],
                          'url' => $row['url'],
                          'thumbnailUrl' => $row['image'],
                          'uploadDate' => date('Y-m-d', strtotime($itemData['modify_date'])),
                      ];
                      ?>
                      <div
                        itemscope
                        itemtype="http://schema.org/VideoObject"
                        <?php echo $i ? 'style="display: none"' : ''?>
                        data-v-bind_style="{display: ((vm.selectedImage == <?php echo $i?>) ? 'block' : 'none')}"
                      >
                        <meta itemprop="name" content="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>" />
                        <meta itemprop="description" content="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>" />
                        <meta
                          itemprop="uploadDate"
                          content="<?php echo date('Y-m-d', strtotime($itemData['modify_date']))?>"
                        />
                        <meta
                          itemprop="thumbnailUrl"
                          content="<?php echo htmlspecialchars($row['image'])?>"
                        />
                        <a
                          itemprop="url"
                          class="catalog-article__image-video"
                          href="<?php echo htmlspecialchars($row['url'])?>"
                          data-lightbox-gallery="catalog-article<?php echo (int)$Block->id?>__image"
                          title="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>"
                        >
                          <img
                            loading="lazy"
                            itemprop="thumbnail"
                            src="<?php echo htmlspecialchars($row['image'])?>"
                            alt="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>"
                          >
                        </a>
                      </div>
                      <script type="application/ld+json"><?php echo json_encode($videoJsonLd)?></script>
                  <?php } else {
                      $jsonLd['image'][] = '/' . $row['fileURL']; ?>
                      <a
                        itemprop="image"
                        href="<?php echo $row['fileURL']?>"
                        <?php echo $i ? 'style="display: none"' : ''?>
                        data-v-bind_style="{display: ((vm.selectedImage == <?php echo $i?>) ? 'block' : 'none')}"
                        data-lightbox-gallery="catalog-article<?php echo (int)$Block->id?>__image"
                      >
                        <img
                          loading="lazy"
                          src="/<?php echo Package::i()->tn(ltrim($row['fileURL'], '/'), 600, null, 'inline')?>"
                          alt="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>"
                        />
                      </a>
                  <?php }
              }
              if (!$photoVideo) { ?>
                  <img loading="lazy" src="/files/cms/common/image/design/nophoto.jpg" alt="" />
              <?php } ?>
            </div>
            <!--noindex-->
            <div
              class="catalog-article__images-list catalog-article-images-list slider slider_horizontal"
              data-vue-role="raas-slider"
              data-vue-type="horizontal"
              data-v-bind_wrap="false"
              data-v-bind_autoscroll="false"
              data-v-slot="slider"
            >
              <button
                type="button"
                data-v-on_click="slider.prev()"
                class="
                  catalog-article-images-list__arrow
                  catalog-article-images-list__arrow_prev
                  slider__arrow slider__arrow_prev
                "
                data-v-bind_class="{
                    'catalog-article-images-list__arrow_active': slider.prevAvailable,
                    'slider__arrow_active': slider.prevAvailable
                }"
              ></button>
              <div class="catalog-article-images-list__inner slider__list" data-role="slider-list">
                <div class="catalog-article-images-list__list slider-list slider-list_horizontal">
                  <?php foreach ($photoVideo as $i => $row) {
                      if ($row['isVideo'] ?? false) {
                          $image = $row['image'];
                          $href = $row['url'];
                      } else {
                          $image = $row['smallURL'];
                          $href = $row['fileURL'];
                      }
                      ?>
                      <a
                        class="
                          catalog-article-images-list__item
                          slider-list__item
                          catalog-article-images-item
                          <?php echo ($row['isVideo'] ?? false) ? 'catalog-article-images-item_video' : ''?>
                        "
                        href="<?php echo htmlspecialchars($href)?>"
                        data-v-on_click="vm.clickThumbnail(<?php echo (int)$i?>, $event)"
                        data-lightbox-gallery="catalog-article<?php echo (int)$Block->id?>__images"
                        data-role="slider-item"
                        data-v-bind_class="{
                            'catalog-article-images-list__item_active': (slider.activeFrame == <?php echo $i?>),
                            'slider-list__item_active': (slider.activeFrame == <?php echo $i?>)
                        }"
                      >
                        <img
                          loading="lazy"
                          src="<?php echo htmlspecialchars($image)?>"
                          alt="<?php echo htmlspecialchars($row['name'] ?: $itemData['name'])?>"
                        />
                      </a>
                  <?php }
                  if (!$photoVideo) { ?>
                      <span
                        class="catalog-article-images-list__item slider-list__item catalog-article-images-item"
                        data-role="slider-item"
                        data-v-bind_class="{
                          'catalog-article-images-list__item_active': true,
                          'slider-list__item_active': true
                        }"
                      >
                        <img loading="lazy" src="/files/cms/common/image/design/nophoto.jpg" alt="" />
                      </span>
                  <?php } ?>
                </div>
              </div>
              <button
                type="button"
                data-v-on_click="slider.next()"
                class="
                  catalog-article-images-list__arrow
                  catalog-article-images-list__arrow_next
                  slider__arrow slider__arrow_next
                "
                data-v-bind_class="{
                    'catalog-article-images-list__arrow_active': slider.nextAvailable,
                    'slider__arrow_active': slider.nextAvailable
                }"
              ></button>
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
                <?php if ($brand = $Item->brand) { ?>
                    <a
                      href="<?php echo htmlspecialchars($brand->url)?>"
                      class="catalog-article__header-right catalog-article__brand"
                      title="<?php echo htmlspecialchars($brand->name)?>"
                    >
                      <?php if ($brand->image->id) { ?>
                          <img
                            src="/<?php echo htmlspecialchars($brand->image->fileURL)?>"
                            alt="<?php echo htmlspecialchars($brand->name)?>"
                          >
                      <?php } else {
                          echo htmlspecialchars($brand->name);
                      } ?>
                    </a>
                <?php } ?>
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
                    ['image', 'file', 'material']
                );
            });

            $propsArr = [];
            foreach ($props as $fieldURN => $field) {
                $unit = '';
                if (in_array($field->datatype, ['text', 'number', 'range']) && $field->source) {
                    $unit = $field->source;
                }
                $fieldName = $field->name;
                $fieldValues = $field->getValues(true);
                $fieldValues = array_values(array_filter($fieldValues));
                if ($fieldValues) {
                    $richValues = array_map(function ($val) use ($field) {
                        if (($field->datatype == 'checkbox') && !$field->multiple) {
                            return $val ? _YES : _NO;
                        } else {
                            return $field->doRich($val);
                        }
                    }, $fieldValues);
                    $richValues = array_values(array_filter($richValues, function ($x) use ($field) {
                        return $x;
                    }));
                    if (!$richValues) {
                        continue;
                    }
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
                                if (preg_match('/кг|kg/umis', $unit)) {
                                    $unitCode = 'KGM';
                                } else {
                                    $unitCode = 'GRM';
                                }
                            } else {
                                if (preg_match('/см|cm/umis', $unit)) {
                                    $unitCode = 'CMT';
                                } elseif (preg_match('/мм|mm/umis', $unit)) {
                                    $unitCode = 'MMT';
                                } else {
                                    $unitCode = 'MTR';
                                }
                            }
                            $jsonLd[$schemaOrgURN] = [
                                '@type' => 'QuantitativeValue',
                                'value' => $textValue,
                                'unitCode' => $unitCode,
                            ];
                            ?>
                            <div class="catalog-article-props-list__item catalog-article-props-item">
                              <span class="catalog-article-props-item__title">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span
                                class="catalog-article-props-item__value"
                                itemprop="<?php echo $schemaOrgURN?>"
                                itemscope
                                itemtype="http://schema.org/QuantitativeValue"
                              >
                                <span itemprop="value">
                                  <?php echo htmlspecialchars($textValue)?>
                                </span>
                                <span itemprop="unitCode" content="<?php echo $unitCode?>">
                                  <?php echo htmlspecialchars($unit)?>
                                </span>
                              </span>
                            </div>
                            <?php
                            break;
                        case 'article':
                            $jsonLd['productID'] = $textValue;
                            $jsonLd['sku'] = $textValue;
                            ?>
                            <div class="catalog-article-props-list__item catalog-article-props-item">
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
                            <div class="catalog-article-props-list__item catalog-article-props-item">
                              <span class="catalog-article-props-item__title">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span
                                class="catalog-article-props-item__value"
                                itemprop="brand"
                                itemscope
                                itemtype="http://schema.org/Brand"
                              >
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
                                'value' => $textValue . ($unit ? (' ' . $unit) : ''),
                            ];
                            ?>
                            <div
                              class="catalog-article-props-list__item catalog-article-props-item"
                              itemprop="additionalProperty"
                              itemscope
                              itemtype="http://schema.org/PropertyValue"
                            >
                              <span class="catalog-article-props-item__title" itemprop="name">
                                <?php echo htmlspecialchars($fieldName)?>:
                              </span>
                              <span class="catalog-article-props-item__value" itemprop="value">
                                <?php
                                echo htmlspecialchars($textValue . ($unit ? (' ' . $unit) : ''));
                                ?>
                              </span>
                            </div>
                            <?php
                            break;
                    }
                    $propsArr[] = ob_get_clean();
                }
            }
            if ($propsArr) { ?>
                <div class="catalog-article__props-list catalog-article-props-list">
                  <?php echo implode(' ', $propsArr); ?>
                </div>
            <?php } ?>

            <?php /*** ПРЕДЛОЖЕНИЕ ***/ ?>
            <div class="catalog-article__offer" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
              <meta itemprop="sku" content="<?php echo htmlspecialchars($itemData['article'])?>" />
              <link itemprop="url" href="<?php echo htmlspecialchars($host . $itemData['url'])?>" />
              <link
                itemprop="availability"
                href="http://schema.org/<?php echo $itemData['available'] ? 'InStock' : 'PreOrder'?>"
              />
              <?php if ($itemData['price']) { ?>
                  <div class="catalog-article__price-container" data-price="<?php echo (float)$itemData['price']?>">
                    <?php if ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) { ?>
                        <span
                          data-v-if="vm.item.price_old && (vm.item.price_old > vm.item.price)"
                          class="catalog-article__price catalog-article__price_old"
                          data-v-html="vm.formatPrice(vm.item.price_old * Math.max(vm.item.min || 1, vm.amount))"
                        >
                          <?php echo Text::formatPrice((float)$itemData['price_old'])?>
                        </span>
                    <?php } ?>
                    <span class="
                      catalog-article__price
                      <?php
                      if ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) {
                          echo 'catalog-article__price_new';
                      }
                      ?>
                    ">
                      <span
                        data-role="price-container"
                        itemprop="price"
                        content="<?php echo (float)$itemData['price']?>"
                        data-v-html="vm.formatPrice(vm.item.price * Math.max(vm.item.min || 1, vm.amount))"
                      >
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
              <?php } ?>
              <div class="
                catalog-article__available
                catalog-article__available_<?php echo $itemData['available'] ? '' : 'not-'?>available"
              >
                <?php echo $itemData['available'] ? AVAILABLE : AVAILABLE_CUSTOM?>
              </div>
            </div>
            <!--noindex-->
            <?php if ($itemData['price'] && $itemData['available']) { ?>
                <div class="catalog-article__add-to-cart-outer">
                  <div class="catalog-article__amount-block" title="<?php echo IN_CART?>" data-v-if="vm.inCart">
                    <button
                      type="button"
                      class="catalog-article__decrement"
                      data-v-bind_disabled="vm.amount <= 0"
                      data-v-on_click="vm.setAmount(parseInt(vm.amount) - parseInt(vm.item.step || 1)); vm.setCart();"
                    >–</button>
                    <input
                      type="number"
                      class="form-control catalog-article__amount"
                      autocomplete="off"
                      min="0"
                      step="<?php echo (int)$itemData['step'] ?: 1?>"
                      <?php echo $itemData['max'] ? ('max="' . (int)$itemData['max'] . '"') : ''?>
                      data-v-bind_value="vm.amount"
                      data-v-on_change="vm.setAmount($event.target.value); vm.setCart();"
                    />
                    <button
                      type="button"
                      class="catalog-article__increment"
                      data-v-bind_disabled="vm.item.max && (vm.amount >= vm.item.max)"
                      data-v-on_click="vm.setAmount(parseInt(vm.amount) + parseInt(vm.item.step || 1)); vm.setCart();"
                    >+</button>
                  </div>
                  <button
                    data-v-else
                    type="button"
                    data-v-on_click="vm.setAmount(Math.max(vm.item.min, 1)); vm.setCart()"
                    class="btn btn-primary catalog-article__add-to-cart"
                  >
                    <?php echo DO_BUY?>
                  </button>
                </div>
            <?php } ?>
            <div class="catalog-article__controls">
              <button
                type="button"
                data-v-on_click="vm.toggleFavorites()"
                class="catalog-article__add-to-favorites"
                data-v-bind_class="{ 'catalog-article__add-to-favorites_active': vm.inFavorites}"
                data-v-bind_title="vm.inFavorites ? '<?php echo IN_FAVORITES?>' : '<?php echo TO_FAVORITES?>'"
                data-v-html="vm.inFavorites ? '<?php echo IN_FAVORITES?>' : '<?php echo TO_FAVORITES?>'"
              >
                <?php echo TO_FAVORITES?>
              </button>
              <button
                type="button"
                data-v-on_click="vm.toggleCompare()"
                class="catalog-article__add-to-compare"
                data-v-bind_class="{ 'catalog-article__add-to-compare_active': vm.inCompare}"
                data-v-bind_title="vm.inCompare ? '<?php echo IN_COMPARISON?>' : '<?php echo TO_COMPARISON?>'"
                data-v-html="vm.inCompare ? '<?php echo IN_COMPARISON?>' : '<?php echo TO_COMPARISON?>'"
              >
                <?php echo TO_COMPARISON?>
              </button>
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
            'comments' => REVIEWS . ($comments ? ' (' . count($comments) . ')' : ''),
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
                              <a
                                href="/<?php echo htmlspecialchars($file->fileURL)?>"
                                class="
                                  catalog-article-files-list__item
                                  catalog-article-files-item
                                  catalog-article-files-item_<?php echo mb_strtolower(pathinfo($file->fileURL, PATHINFO_EXTENSION))?>
                                "
                                target="_blank"
                              >
                                <?php echo htmlspecialchars($file->name ?: basename($file->fileURL))?>
                              </a>
                          <?php } ?>
                        </div>
                    <?php }
                    break;
                case 'comments':
                    if ($comments || $commentFormBlock->id) { ?>
                        <?php if ($commentsListText) { ?>
                            <div class="catalog-article__comments-list">
                              <?php echo $commentsListText?>
                            </div>
                        <?php } ?>
                        <?php if ($commentFormBlock->id) { ?>
                            <div class="catalog-article__comments-form">
                              <?php $commentFormBlock->process($Page)?>
                            </div>
                        <?php } ?>
                    <?php }
                    break;
                case 'faq':
                    if ($faq || $faqFormBlock->id) { ?>
                        <?php if ($faqListText) { ?>
                            <div class="catalog-article__faq-list">
                              <?php echo $faqListText?>
                            </div>
                        <?php } ?>
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
              <ul class="catalog-article-tabs-nav-list" role="tablist">
                <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                    <li class="catalog-article-tabs-nav-list__item">
                      <a
                        class="catalog-article-tabs-nav-item<?php echo !$i ? ' active' : ''?>"
                        href="#<?php echo $key?>"
                        aria-controls="<?php echo $key?>"
                        role="tab"
                        data-bs-toggle="tab"
                        data-bs-target="#<?php echo $key?>"
                      >
                        <?php echo htmlspecialchars($row['name'])?>
                      </a>
                    </li>
                <?php $i++; } ?>
              </ul>
            </div>
            <div class="catalog-article__tabs-list">
              <div class="catalog-article-tabs-list">
                <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                    <div
                      class="catalog-article-tabs-list__item <?php echo !$i ? ' active' : ''?>"
                      id="<?php echo $key?>"
                      role="tabpanel"
                    >
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
                <div
                  class="catalog-article-related__inner slider slider_horizontal"
                  data-vue-role="raas-slider"
                  data-vue-type="horizontal"
                  data-v-bind_wrap="true"
                  data-v-bind_autoscroll="true"
                  data-v-slot="slider"
                >
                  <button
                    type="button"
                    data-v-on_click="slider.prev()"
                    class="
                      catalog-article-related__arrow
                      catalog-article-related__arrow_prev
                      slider__arrow
                      slider__arrow_prev
                    "
                    data-v-bind_class="{
                        'catalog-article-related__arrow_active': slider.prevAvailable,
                        'slider__arrow_active': slider.prevAvailable
                    }"
                  ></button>
                  <div class="catalog-article-related__list slider__list" data-role="slider-list">
                    <div class="catalog-article-related-list slider-list slider-list_horizontal">
                      <?php foreach ((array)$related as $i => $item) { ?>
                          <div
                            class="catalog-article-related-list__item slider-list__item"
                            data-role="slider-item"
                            data-v-bind_class="{
                                'catalog-article-related-list__item_active': (slider.activeFrame == <?php echo $i?>),
                                'slider-list__item_active': (slider.activeFrame == <?php echo $i?>)
                            }"
                          >
                            <?php Snippet::importByURN('catalog_item')->process([
                                'item' => $item,
                                'page' => $Page,
                                'position' => $i,
                            ]); ?>
                          </div>
                      <?php } ?>
                    </div>
                  </div>
                  <button
                    type="button"
                    data-v-on_click="slider.next()"
                    class="
                      catalog-article-related__arrow
                      catalog-article-related__arrow_next
                      slider__arrow
                      slider__arrow_next
                    "
                    data-v-bind_class="{
                        'catalog-article-related__arrow_active': slider.nextAvailable,
                        'slider__arrow_active': slider.nextAvailable
                    }"
                  ></button>
                </div>
              </div>
            </div>
        <?php } ?>
      </div>
    </div>
    <script type="application/ld+json"><?php echo json_encode($jsonLd)?></script>
    <?php
    AssetManager::requestCSS(['/css/catalog-article.css']);
    AssetManager::requestJS([
        '//yastatic.net/es5-shims/0.0.2/es5-shims.min.js',
        '//yastatic.net/share2/share.js',
        '/js/catalog-article.js'
    ]);
} else {
    $itemsData = [
        'pages' => (int)$Pages->pages,
        'page' => (int)$Pages->page,
        'items' => [],
    ];
    $nextPage = min($Pages->pages, $Pages->page + 1);
    if ($nextPage > 1) {
        $itemsData['nextUrl'] = HTTP::queryString('AJAX=&page=' . $nextPage);
    }
    if ($Set) {
        Field::prefetch((array)$Set);
        foreach ($Set as $i => $item) {
            $formatter = new ItemArrayFormatter($item, (bool)(array)json_decode($item->cache_shop_props, true));
            $formatter->page = $Page ?? null;
            $formatter->position = $i ?? null;
            $itemData = $formatter->format();
            $itemsData['items'][] = $itemData;
        }
    }
    if ($_GET['AJAX'] == $Block->id) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($itemsData);
        exit;
    }
    ?>
    <div class="catalog">
      <?php if ($subcats) { ?>
            <div class="catalog__categories-list catalog-categories-list">
              <?php foreach ($subcats as $row) { ?>
                  <div class="catalog-categories-list__item">
                    <?php Snippet::importByURN('catalog_category')->process(['page' => $row])?>
                  </div>
              <?php } ?>
            </div>
      <?php } ?>
      <div class="catalog__controls" data-role="catalog-controls">
        <?php Snippet::importByURN('catalog_controls')->process([
            'sort' => $sort,
            'order' => $order,
            'Page' => $Page,
            'Block' => $Block,
        ])?>
      </div>
      <div
        class="catalog-loader"
        data-vue-role="catalog-loader"
        data-v-bind_initial-data="<?php echo htmlspecialchars(json_encode($itemsData))?>"
        data-v-bind_page="<?php echo (int)$Pages->page?>"
        data-v-bind_pages="<?php echo (int)$Pages->pages?>"
        data-v-bind_block-id="<?php echo (int)$Block->id?>"
        data-v-slot="vm"
      >
        <?php if ($itemsData['items']) { ?>
            <div class="catalog__list catalog-list">
              <?php foreach ($itemsData['items'] as $i => $itemData) { ?>
                  <div class="catalog-list__item">
                    <?php Snippet::importByURN('catalog_item')->process([
                        'item' => $itemData,
                        'noVue' => true,
                    ])?>
                  </div>
              <?php } ?>
            </div>
            <?php if ($itemsData['pages'] > 1) { ?>
                <div class="catalog__pagination-outer">
                  <div class="catalog__pagination">
                    <?php Snippet::importByURN('pagination')->process(['pages' => $Pages]); ?>
                  </div>
                  <?php if ($itemsData['nextUrl']) { ?>
                      <div class="catalog__more">
                        <a href="<?php echo htmlspecialchars($itemsData['nextUrl'])?>" class="btn btn-primary">
                          <?php echo SHOW_MORE_CATALOG?>
                        </a>
                      </div>
                  <?php } ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p><?php echo NO_RESULTS_FOUND?></p>
        <?php } ?>
      </div>
      <?php if ($description = $Page->_description_) { ?>
          <div class="catalog__description">
            <?php echo $description?>
          </div>
      <?php } ?>
    </div>
    <?php
    AssetManager::requestCSS(['/css/catalog-list.css', '/css/catalog-item.css', '/css/pagination.css']);
    AssetManager::requestJS(['/js/catalog-list.js', '/js/catalog-item.js', '/js/pagination.js']);
}
