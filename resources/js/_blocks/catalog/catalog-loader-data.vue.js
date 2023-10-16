/**
 * Загрузчик каталога с подгрузкой чистых данных через AJAX
 *
 * Требует для подгрузки данные в формате:
 * <pre><code>{
 *     pages: Number общее количество страниц,
 *     page: Number текущая страница,
 *     nextUrl?: String URL следующей страницы,
 *     items: Object[] Данные объектов списка,
 * }</code></pre>
 * 
 * @requires CatalogLoader
 */
export default {
    props: {
        /**
         * ID# блока
         * @type {Object}
         */
        blockId: {
            type: Number,
            required: true,
        },
        /**
         * Исходные данные
         * @type {Object} <pre><code>{
         *     pages: Number общее количество страниц,
         *     page: Number текущая страница,
         *     nextUrl?: String URL следующей страницы,
         *     items: Object[] Данные объектов списка,
         * }</code></pre>
         */
        initialData: {
            type: Object,
            default() {
                return {};
            },
        },
    },
    mounted() {
        this.appendData(this.initialData, true);
    },
    data() {
        let translations = {
            SHOW_MORE_CATALOG: 'Показать еще',
            NO_RESULTS_FOUND: 'По вашему запросу ничего не найдено',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }

        return {
            items: this.initialData.items || [],
            nextUrl: this.initialData.nextUrl || '',
            translations, // Переводы
        };
    },
    methods: {
        /**
         * Добавление данных
         * @param {Object} remote Данные, полученные как результат запроса
         * @param bool clear Очищать 
         */
        appendData(remote, clear) {
            let self = this;
            
            if (remote.pages) {
                this.pagesTotal = remote.pages;
            }
            if (remote.page) {
                this.currentPage = remote.page;
            }
            if (remote.nextUrl) {
                this.nextUrl = remote.nextUrl;
            }

            if (clear) {
                this.items = [];
            }
            if (remote.items) {
                this.items = this.items.concat(remote.items);
                window.setTimeout(() => {
                    if (this.$root.ecommerceEnabled) {
                        const products = remote.items.map(item => {
                            if (item.eCommerce) {
                                return { ...item.eCommerce, list: 'main' };
                            }
                            return null;
                        }).filter(x => !!x);
                        if (products.length) {
                            this.$root.cart.getECommerce().trigger({ action: 'impressions', products });
                        }
                    }
                }); // Чтобы отработало прикрепление $root
            }
            this.$root.lightBoxInit();
        },


        /**
         * Загружаем каталог
         * @param {String} url URL для загрузки
         * @param {Boolean} clear Очистить предыдущий список
         * @param {Boolean} setBusy Устанавливать статус "загрузка"
         * @return {Object} Данные полученной страницы
         */
        async loadCatalog(url, clear, setBusy = true, writeToHistory = true) {
            if (this.busy) {
                throw new Error('Catalog is busy');
            }
            if (setBusy) {
                this.busy = true;
            }
            let result = await this.$root.api(url, null, this.blockId);
            this.appendData(result, clear);
            if (clear && setBusy) {
                $.scrollTo(this.scrollToSelector || this.$el, 500);
            }
            $(document).trigger('raas.shop.catalog-ready');
            this.$emit('catalogloaded', result);
            if (writeToHistory) {
                window.history.pushState({}, document.title, url);
            }
            this.busy = false;
            return result;
        },
    },
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self() { 
            return { ...this };
        },
    },
    watch: {
        initialData(newVal, oldVal) {
            if (JSON.stringify(newVal) != JSON.stringify(oldVal)) {
                this.appendData(newVal, true);
            }
        }
    }
}