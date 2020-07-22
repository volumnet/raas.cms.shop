import CatalogItem from './catalog/catalog-item.vue';
import CatalogArticle from './catalog/catalog-article.vue';
import CatalogLoader from './catalog/catalog-loader.vue';

window.RAASShopCatalogItem = Vue.component('catalog-item', CatalogItem);
window.RAASShopCatalogArticle = Vue.component('catalog-article', CatalogArticle);
window.RAASShopCatalogArticle = Vue.component('catalog-loader', CatalogLoader);

jQuery(document).ready(function ($) {
    // Выстраиваем картинки
    window.adjustCatalog = function () {
        $('.catalog .catalog-item .catalog-item__description').adjustHeight();
        $('.catalog .catalog-item').adjustHeight();
        $('.catalog .catalog-category').adjustHeight();
    };

    $(document).on('raas.shop.catalog-ready', function() {
        window.adjustCatalog();
        window.setTimeout(window.adjustCatalog, 500)
    });
    $(document).trigger('raas.shop.catalog-ready');

    $(window).on('load', window.adjustCatalog);
    $('body').on('shown.bs.tab', '.catalog-article .nav-tabs a[href="#related"]', function () {
        $('.catalog-article #related .catalog-item').adjustHeight();
    });

});