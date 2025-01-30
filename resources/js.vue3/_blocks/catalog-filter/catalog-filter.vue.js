/**
 * Фильтр каталога
 */
export default {
    props: {
        /**
         * ID# категории каталога
         * @type {Number}
         */
        catalogId: {
            type: Number,
            required: true,
        },
        /**
         * ID# блока каталога (НЕ ФИЛЬТРА!!!)
         * @type {Number}
         */
        blockId: {
            type: Number,
            required: true,
        },
        /**
         * ID# блока фильтра
         * @type {Number}
         */
        filterBlockId: {
            type: Number,
            default: null,
        },
        /**
         * Источник данных фильтра
         * @type {Object} <pre><code>{
         *     counter: Number Счетчик товаров,
         *     filter: { 
         *         String|Number[] ID# характеристики: array<String> Значения
         *     } Данные фильтра,
         *     formData: { 
         *         String[] URN характеристики: mixed Значения
         *     } GET-параметры,
         *     properties: array<{
         *         id: Number ID# свойства,
         *         urn: String URN свойства,
         *         datatype: String Тип данных свойства,
         *         multiple: Number|Boolean Множественное ли свойство,
         *         name: String Наименование свойства,
         *         priority: Number Порядок отображения
         *         stdSource: null|Array Источник данных свойства,
         *         values: {
         *             String|Number ID# значения: {
         *                 enabled: Boolean Возможна ли отметка свойства,
         *                 value: String|Number Значение,
         *                 doRich: String Человеко-понятное значение,
         *                 checked: Boolean Отмечено ли свойство
         *             }
         *         }
         *     }>
         * }</code></pre>
         */
        source: {
            type: Object,
            required: false,
        },
        /**
         * URL предпросмотра
         * @type {String}
         */
        ajaxPreviewUrl: {
            type: String,
            default: '/ajax/catalog_filter/',
        },
        /**
         * Плавающий маркер (для выравнивания)
         * @type {Boolean}
         */
        floatingMarker: {
            type: Boolean,
            default: true,
        },
        /**
         * Можно выбирать несколько значений
         * @type {Boolean}
         */
        multiple: {
            type: Boolean,
            default: true,
        },
        /**
         * Таймаут задержки AJAX-запроса, мс
         * @type {Number}
         */
        timeout: {
            type: Number,
            default: 1000,
        },
        /**
         * Время погасания плавающего маркера предпросмотра, мс
         * @type {Number}
         */
        previewTimeout: {
            type: Number,
            default: 3000,
        },
    },
    data() {
        let result = {
            timeoutId: null, // ID# таймаута
            lastActiveElement: null, // Последний активный элемент (для выравнивания маркера)
            previewTimeoutId: null, // ID# таймаута для предпросмотра
            counter: 0, // Счетчик свойств
            formData: {}, // GET-параметры,
            filter: {}, // Данные фильтра,
            properties: [], // Доступные свойства
            isActive: false, // Фильтр активен (для мобильной версии)
        };
        if (this.source) {
            for (let key of ['counter', 'formData', 'filter', 'properties']) {
                if (this.source[key]) {
                    result[key] = this.source[key];
                }
            }
        }
        return result;
    },
    mounted() {
        window.setTimeout(() => {
            this.fixHtml();
        }, 0)
        $(window).on('resize', this.fixHtml.bind(this));
        $('.catalog-filter__close-link').on('click', (e) => { 
            this.isActive = false;
            return false;
        });
        $('.body').on('click', (e) => { 
            this.isActive = false;
        });
        $(this.$el).on('click', (e) => { 
            e.stopPropagation();
        });
        $(document).on('raas.shop.openfilter', () => {
            this.isActive = !this.isActive;
        })
        $(document).on('raas.shop.catalogupdaterequest', () => {
            this.previewTimeoutId = null;
            this.lastActiveElement = null;
        });
        window.setTimeout(() => {
            if (this.$root.windowWidth < this.$root.mediaTypes.lg) {
                $(this.$el).on('movestart', (e) => { 
                    if (e.distX <= -12) {
                        this.isActive = false;
                        return true;
                    }
                    e.preventDefault();
                    return false;
                });
            }
            if (this.properties.length) {
                $(document).trigger('raas.shop.displayfiltertrigger');
            }
        }, 0); // Задержка чтобы успел инициализироваться this.$root
        // 2021-10-05, AVS: добавили условие чтобы лишний раз 
        // не обновлялось при статическом фильтре
        if (!this.source) {
            this.refresh(); 
        }
    },
    methods: {
        /**
         * Перемещает блок для мобильной версии (поскольку левая колонка скрыта)
         */
        fixHtml() {
            if (this.$root.windowWidth >= this.$root.mediaTypes.lg) {
                if (!$(this.$el).closest('.catalog-filter__outer').length) {
                    $(this.$el).appendTo('.catalog-filter__outer');
                }
            } else if ($(this.$el).closest('.catalog-filter__outer').length) {
                $(this.$el).appendTo('.body');
            }
        },
        /**
         * Открывает фильтр
         */
        open() {
            this.isActive = true;
        },
        /**
         * Закрывает фильтр
         */
        close() {
            this.isActive = false;
        },
        /**
         * Переключает видимость фильтра
         */
        toggle() {
            this.isActive = !this.isActive;
        },
        /**
         * Изменение данных фильтра
         * @param {Event} event Событие, которое вызвало изменение данных
         */
        change(event) {
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
         * Устанавливает последний активный элемент
         * @param {HTMLElement} lastActiveElement
         */
        setLastActiveElement(lastActiveElement) {
            this.lastActiveElement = lastActiveElement;
        },


        /**
         * Получение URL-параметров формы
         * @return {String}
         */
        getFormUrl() {
            // 2021-10-05, AVS: убрали всё лишнее, оставили только updateQuery
            // let query = window.queryString.parse(
            //     document.location.search, 
            //     { arrayFormat: 'bracket' }
            // );
            // 2023-08-09, AVS: добавил поиск формы вверх и вниз, чтобы не ограничиваться тегами
            let $form;
            if ($(this.$el).is('form')) {
                $form = $(this.$el);
            } else if ($(this.$el).closest('form').length) {
                $form = $(this.$el).closest('form');
            } else if ($(this.$el).find('form').length) {
                $form = $(this.$el).find('form');
            } else {
                $form = $(this.$el);
            }
            let updateQuery = window.queryString.parse($form.formSerialize(), { arrayFormat: 'bracket' });
            let query = updateQuery;
            delete query.page;
            // for (let key in updateQuery) {
            //     if (typeof updateQuery[key] == 'boolean') {
            //         if (updateQuery[key]) {
            //             query[key] = 1; 
            //         } else {
            //             delete query[key];
            //         }
            //     } else {
            //         query[key] = updateQuery[key];
            //     }
            // }
            let url = window.queryString.stringify(
                query, 
                { arrayFormat: 'bracket' }
            );
            return url;
        },

        /**
         * Получение URL для предпросмотра
         * @return {String}
         */
        getPreviewUrl() {
            let url = this.ajaxPreviewUrl
                    + '?id=' + this.catalogId
                    + '&block_id=' + this.blockId
                    + '&' + this.getFormUrl();
            return url;
        },
        
        /**
         * Получение URL для обновления
         * @return {String}
         */
        getRefreshUrl() {
            let url = this.ajaxPreviewUrl + document.location.search;
            url += (/\?/.test(url) ? '&' : '?')
                +  'id=' + this.catalogId +
                   '&block_id=' + this.blockId;
            return url;
        },
        
        /**
         * Предпросмотр
         */
        async preview() {
            let url = this.getPreviewUrl();
            const result = await this.$root.api(url, null, this.filterBlockId);
            this.update(result);
            if (this.previewTimeoutId) {
                window.clearTimeout(this.previewTimeoutId);
                this.previewTimeoutId = null;
            }
            if (this.lastActiveElement) {
                this.previewTimeoutId = window.setTimeout(() => {
                    this.previewTimeoutId = null;
                    this.lastActiveElement = null;
                }, this.previewTimeout);
            }
        },
        
        /**
         * Обновление (сброс)
         */
        async refresh() {
            let url = this.getRefreshUrl();
            const result = await this.$root.api(url);
            this.update(result);
        },
        
        /**
         * Обновление фильтра входящими данными
         * @param {Object} result Входящие данные
         */
        update(result) {
            this.formData = result.formData;
            this.filter = result.filter;
            this.properties = result.properties;
            this.counter = result.counter;
            if (this.properties.length) {
                $(document).trigger('raas.shop.displayfiltertrigger');
            }
        },

        /**
         * Применение фильтра
         * @param {Event} event Событие, которое вызвало изменение данных
         */
        submit(event) {
            let url = document.location.pathname + '?' + this.getFormUrl();
            $(document).trigger('raas.shop.catalogupdaterequest', url)
            if (event) {
                event.preventDefault();
            }
            $(this.$el).closest('.catalog-filter').removeClass('catalog-filter_active');
        },
    },
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self() { 
            return {
                catalogId: this.catalogId,
                blockId: this.blockId,
                filterBlockId: this.filterBlockId,
                source: this.source,
                ajaxPreviewUrl: this.ajaxPreviewUrl,
                floatingMarker: this.floatingMarker,
                multiple: this.multiple,
                timeout: this.timeout,
                previewTimeout: this.previewTimeout,
                timeoutId: this.timeoutId,
                lastActiveElement: this.lastActiveElement,
                previewTimeoutId: this.previewTimeoutId,
                counter: this.counter,
                formData: this.formData,
                filter: this.filter,
                properties: this.properties,
                isActive: this.isActive,
                fixHtml: this.fixHtml.bind(this),
                open: this.open.bind(this),
                close: this.close.bind(this),
                toggle: this.toggle.bind(this),
                change: this.change.bind(this),
                setLastActiveElement: this.setLastActiveElement.bind(this),
                getFormUrl: this.getFormUrl.bind(this),
                getPreviewUrl: this.getPreviewUrl.bind(this),
                getRefreshUrl: this.getRefreshUrl.bind(this),
                preview: this.preview.bind(this),
                refresh: this.refresh.bind(this),
                update: this.update.bind(this),
                submit: this.submit.bind(this),
            };
        },
    },
};