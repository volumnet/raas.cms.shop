export default {
    props: {
        /**
         * Номер текущей страницы (исходная, начиная с 1)
         * @type {Number}
         */
        page: {
            type: Number,
            required: true,
        },
        /**
         * Количество страниц (исходное)
         * @type {Number}
         */
        pages: {
            type: Number,
            required: true,
        },
        /**
         * CSS-селектор внутренних ссылок (по отношению к загрузчику) 
         * для обработки динамического клика (подгрузки через AJAX)
         * @type {String}
         */
        dynamicLinkSelector: {
            type: String,
            default: '',
        },
        /**
         * CSS-селектор обновляемых внутренних блоков (по отношению к загрузчику) 
         * @type {String[]}
         */
        updatableBlockSelectors: {
            type: Array,
            default: function () {
                return [];
            },
        },
    },
    data: function () {
        return {
            currentPage: this.page, // Номер текущей страницы (динамический, начиная с 1)
            pagesTotal: this.pages, // Количество страниц (динамическое)
            busy: false,
        };
    },
    mounted: function () {
        let self = this;

        $(this.$el).on('click', '[data-role="loader-more"] a', function () {
            self.loadCatalog($(this).attr('href'), false);
            return false;
        });
        $(this.$el).on('click', '[data-role="loader-pagination"] a', function () {
            self.loadCatalog($(this).attr('href'), true);
            return false;
        });
        if (this.dynamicLinkSelector) {
            $(this.$el).on('click', this.dynamicLinkSelector, function () {
                self.loadCatalog($(this).attr('href'), true);
                return false;
            });
        }
        $(document).on('raas.shop.catalogupdaterequest', ($event, url) => {
            self.loadCatalog(url, true);
        });

    },
    methods: {
        /**
         * Добавление данных
         * @param jQuery $remote jQuery, полученный как результат запроса
         * @param bool clear Очищать 
         */
        appendData: function ($remote, clear) {
            let self = this;
            
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
                '[data-role="loader-pagination"]',
                '[data-role="loader-more"]',
            ].forEach((selector) => {
                $(selector, document).html($(selector, $remoteLoader).html());
            })
            if (this.updatableBlockSelectors && 
                this.updatableBlockSelectors.length
            ) {
                this.updatableBlockSelectors.forEach((selector) => {
                    $(selector, document).html($(selector, $remote).html());
                })
            }
            
            if (clear) {
                $('[data-role="loader-list"]', document).empty();
            }
            $('[data-role="loader-list"]', document).append(
                $('[data-role="loader-list-item"]', $remoteLoader)
            );
            $('[data-vue-role="catalog-item"]', this.$el).attr('data-just-appended', '1')
            new window.VueW3CValid({ el: '.body' });
            $('[data-just-appended]', this.$el).each(function () {
                $(this).removeAttr('data-just-appended')
                    .attr('inline-template', 'inline-template')
                    .removeAttr('v-slot');
                $('> *', this).wrapAll('<div class="catalog-item"></div>');
                let itemData = JSON.parse($(this).attr('v-bind:item'));
                $(this).html($(this).html().replaceAll('vm.', ''));
                
                let raasShopCatalogItem = new window.registeredRAASComponents['catalog-item-dynamic']({
                    propsData: { item: itemData },
                });
                raasShopCatalogItem.$mount(this);
            })
        },


        /**
         * Загружаем каталог
         * @param {String} url URL для загрузки
         * @param {Boolean} clear Очистить предыдущий список
         * @return jQuery.Deferred Promise, разрешаемый jQuery полученной страницы
         */
        loadCatalog: function (url, clear) {
            let self = this;
            let $dO = $.Deferred();
            let title = document.title;
            if (this.busy) {
                $dO.reject(false);
                return $dO;
            }
            this.busy = true;
            $.get(url).then((result) => {
                const rxRes = /\<body.*?\>([\s\S]*?)\<\/body\>/m.exec(result);
                if (clear) {
                    let rxTitle = /<title>(.*)<\/title>/.exec(result);
                    if (rxTitle && rxTitle[1]) {
                        title = rxTitle[1];
                    }
                }
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
                window.history.pushState({}, title, url);
                if (clear) {
                    document.title = title;
                }
                self.busy = false;
                return $result;
            });
            return $dO;
        },
    },
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self: function () { 
            return { ...this };
        },
    },
}