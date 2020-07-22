<script>
export default {
    props: [
        'page', // Текущая страница (исходная)
        'pages', // Общее количество страниц (исходное)
        'cart', // Корзина
        'favorites', // Избранное
    ],
    data: function () {
        return {
            currentPage: this.page,
            pagesTotal: this.pages,
            busy: false,
        };
    },
    mounted: function () {
        var self = this;

        $(this.$el).on('click', '[data-role="catalog-more"] a', function () {
            self.loadCatalog($(this).attr('href'), false);
            return false;
        });
        $(this.$el).on(
            'click', 
            '[data-role="catalog-pagination"] a, [data-role="catalog-sort"] a', 
            function () {
                self.loadCatalog($(this).attr('href'), true);
                return false;
            }
        );
        $(document).on('raas.shop.catalogupdaterequest', function ($event, url) {
            self.loadCatalog(url, true);
        });

    },
    methods: {
        /**
         * Добавление данных
         * @param jQuery $remote jQuery, полученный как результат запроса
         * @param bool clear Очищать 
         */
        appendData: function ($remote, clear)
        {
            var self = this;
            const $remoteLoader = $('[data-vue-role="catalog-loader"]', $remote);
            if (!$remoteLoader.length) {
                return;
            }
            this.currentPage = parseInt($remoteLoader.attr('data-v-bind_page')) || 1;
            this.pagesTotal = parseInt($remoteLoader.attr('data-v-bind_pages')) || 1;
            
            [
                'h1', 
                '.catalog__description',
                '[data-role="catalog-counter"]',
            ].forEach((selector) => {
                $(selector, document).html($(selector, $remote).html());
            });

            [
                '[data-role="catalog-pagination"]',
                '[data-role="catalog-more"]',
                '[data-role="catalog-sort"]',
            ].forEach((selector) => {
                $(selector, document).html($(selector, $remoteLoader).html());
            })
            
            if (clear) {
                $('[data-role="catalog-list"]', document).empty();
            }
            $('[data-role="catalog-list"]', document).append(
                $('[data-role="catalog-list-item"]', $remoteLoader)
            );
            $('[data-vue-role="catalog-item"]', this.$el).attr('data-just-appended', '1')
            window.prepareW3CVue();
            let $items = $('[data-just-appended]', this.$el);
            $items.each(function () {
                $(this).removeAttr('data-just-appended');
                // console.log(this.getAttribute('v-bind:id'))
                let raasShopCatalogItem = new window.RAASShopCatalogItem({
                    propsData: {
                        id: $(this).attr('v-bind:id'),
                        name: $(this).attr('v-bind:name'),
                        price: $(this).attr('v-bind:price'),
                        priceold: $(this).attr('v-bind:priceold'),
                        meta: $(this).attr('v-bind:meta'),
                        min: $(this).attr('v-bind:min'),
                        step: $(this).attr('v-bind:step'),
                        image: $(this).attr('v-bind:image'),
                        cart: self.cart,
                        favorites: self.favorites,
                    }
                });
                raasShopCatalogItem.$mount(this);
            })
            // console.log($items);
        },


        /**
         * Загружаем каталог
         * @param {String} url URL для загрузки
         * @param {Boolean} clear Очистить предыдущий список
         * @return jQuery.Deferred Promise, разрешаемый jQuery полученной страницы
         */
        loadCatalog: function (url, clear) {
            var self = this;
            var $dO = $.Deferred();
            if (this.busy) {
                $dO.reject(false);
                return $dO;
            }
            this.busy = true;
            $.get(url).then(function (result) {
                const rxRes = /\<body.*?\>([\s\S]*?)\<\/body\>/m.exec(result);
                if (rxRes) {
                    result = $.trim(rxRes[1]);
                }
                return $(result);
            }).then(function ($result) {
                self.appendData($result, clear);
                if (clear) {
                    $.scrollTo(self.$el, 500);
                }
                $(document).trigger('raas.shop.catalog-ready');
                window.history.pushState({}, document.title, url);
                self.busy = false;
                $dO.resolve($result);
            });
            return $dO;
        },
    },
}
</script>