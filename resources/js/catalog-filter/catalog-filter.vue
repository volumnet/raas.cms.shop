<script>
import CatalogFilterPropertiesList from './catalog-filter-properties-list.vue';
import CatalogFilterPreviewMarker from './catalog-filter-preview-marker.vue';

/**
 * Фильтр каталога
 */
export default {
    data: function () {
        return Object.assign({
            catalogId: 0, // ID# страницы
            blockId: 0, // ID# блока
            ajaxPreviewURL: '/ajax/catalog_filter/', // URL для подгрузки актуальных значений
            floatingMarker: true, // Плавающий маркер
            multiple: true, // Можно выбрать несколько значений одного свойства
            timeout: 1000, // Таймаут задержки AJAX-запроса, в миллисекундах
            timeoutId: null, // ID# таймаута
            lastActiveElement: null, // Последний активный элемент (для выравнивания маркера)
            previewTimeout: 3000, // Таймаут для предпросмотра
            previewTimeoutId: null, // ID# таймаута для предпросмотра
        }, window.raasShopCatalogFilterData);
    },
    template: '#catalog-filter-template',
    components: {
        'catalog-filter-properties-list': CatalogFilterPropertiesList,
        'catalog-filter-preview-marker': CatalogFilterPreviewMarker,
    },
    mounted: function () {
        this.fixHtml();
        $(window).on('resize', this.fixHtml.bind(this));
        $('.catalog-filter__close-link').on('click', function(e) { 
            $('.catalog-filter').removeClass('catalog-filter_active');
            return false;
        });
        $('.body').on('click', function(e) { 
            $('.catalog-filter').removeClass('catalog-filter_active');
        });
        $('.catalog-filter').on('click', function(e) { 
            e.stopPropagation();
        });
        $(document).on('raas.shop.openfilter', function () {
            $('.catalog-filter').addClass('catalog-filter_active');
        })
        if ($(window).outerWidth() < 992) {
            $('.catalog-filter').on('movestart', function(e) { 
                if (e.distX <= -12) {
                    $(this).removeClass('catalog-filter_active');
                    return true;
                }
                e.preventDefault()
                return false;
            });
        }
        this.refresh();
    },
    methods: {
        fixHtml: function () {
            if ($(window).outerWidth() >= 992) {
                $('.catalog-filter').appendTo('.catalog-filter__outer');
            } else {
                $('.catalog-filter').appendTo('.body');
            }
        },
        /**
         * Изменение данных фильтра
         * @param  {Event} event Событие, которое вызвало изменение данных
         */
        change: function (event) {
            if (event.target) {
                this.lastActiveElement = event.target;
            }
            if (this.timeoutId) {
                window.clearTimeout(this.timeoutId);
                this.timeoutId = null;
            }
            this.timeoutId = window.setTimeout(
                this.preview.bind(this),
                this.timeout
            );
        },

        /**
         * Получение URL для предпросмотра
         * @return {String}
         */
        getPreviewUrl: function () {
            var url = this.ajaxPreviewURL
                    + '?id=' + this.catalogId
                    + '&block_id=' + this.blockId
                    + '&' + $(this.$el).formSerialize();
            return url;
        },
        
        /**
         * Получение URL для обновления
         * @return {String}
         */
        getRefreshUrl: function () {
            var url = this.ajaxPreviewURL + document.location.search;
            url += (/\?/.test(url) ? '&' : '?')
                +  'id=' + this.catalogId +
                   '&block_id=' + this.blockId;
            return url;
        },
        
        /**
         * Предпросмотр
         */
        preview: function () {
            var self = this;
            var url = this.getPreviewUrl();
            $.getJSON(url, function (result) {
                self.update(result);
                if (self.previewTimeoutId) {
                    window.clearTimeout(self.previewTimeoutId);
                    self.previewTimeoutId = null;
                }
                if (self.lastActiveElement) {
                    self.previewTimeoutId = window.setTimeout(
                        function () {
                            self.previewTimeoutId = null;
                            self.lastActiveElement = null;
                        },
                        self.previewTimeout
                    );
                }
            });
        },
        
        /**
         * Обновление (сброс)
         */
        refresh: function () {
            var self = this;
            var url = this.getRefreshUrl();
            $.getJSON(url, this.update.bind(this));
        },
        
        /**
         * Обновление фильтра входящими данными
         * @param {Object} result Входящие данные
         */
        update: function (result) {
            this.data = result.data;
            this.filter = result.filter;
            this.properties = result.properties;
            this.counter = result.counter;
            if (this.properties.length) {
                $(document).trigger('raas.shop.displayfiltertrigger');
            }
        },

        /**
         * Применение фильтра
         */
        submit: function (event) {
            var url = document.location.pathname + '?' + $(this.$el).formSerialize();
            $(document).trigger('raas.shop.catalogupdaterequest', url)
            if (event) {
                event.preventDefault();
            }
            $(this.$el).closest('.catalog-filter').removeClass('catalog-filter_active');
        },

        /**
         * Функция обратной связи после обновления
         */
        onSubmit: function ($obj) {
        },
    }
};
</script>