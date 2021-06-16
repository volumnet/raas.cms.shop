/**
 * Панель управления каталога
 */
export default {
    props: {
        /**
         * Сортировка по умолчанию
         * @type {String} URN варианта - совпадает с GET-параметрами 
         *     sort:order, либо sort
         */
        defaultSort: {
            type: String,
            required: false,
        },
    },
    data: function () {
        let query = Object.assign(
            { sort: '', order: '' }, 
            window.queryString.parse(
                document.location.search, 
                { arrayFormat: 'bracket' }
            )
        );
        
        return {
            activeViewVariant: 'blocks', // Активный вариант отображения
            query: query, // Запрос
        };
    },
    mounted: function () {
        window.setTimeout(() => { // Поскольку app.vue инициализируется после компонента
            this.changeView(Cookie.get('view_as'));
            if (this.defaultSort) {
                this.changeSort(this.defaultSort);
            }
        }, 0);
    },
    methods: {
        update: function () {
            let query = window.queryString.parse(
                document.location.search, 
                { arrayFormat: 'bracket' }
            );
            delete query.page;
            delete query.sort;
            delete query.order;
            for (let key in this.query) {
                if (typeof this.query[key] == 'boolean') {
                    if (this.query[key]) {
                        query[key] = 1; 
                    } else {
                        delete query[key];
                    }
                } else {
                    query[key] = this.query[key];
                }
            }
            let url = window.queryString.stringify(
                query, 
                { arrayFormat: 'bracket' }
            );
            url = url ? ('?' + url) : window.location.pathname;
            $(document).trigger('raas.shop.catalogupdaterequest', url);
        },
        /**
         * Изменяет сортировку
         * @param {String} sortVariant URN варианта - совпадает с GET-параметрами 
         *     sort:order, либо sort
         */
        changeSort: function (sortVariant) {
            let sortArr = sortVariant.trim().split(':');
            this.query.sort = sortArr[0];
            this.query.order = sortArr[1] || 'asc';
            this.$forceUpdate();
        },
        changeView: function (viewVariant) {
            Cookie.set(
                'view_as', 
                viewVariant, 
                { expires: 14, path: '/' }
            );
            this.activeViewVariant = viewVariant;
            $(document).trigger('raas.shop.changeview', viewVariant);
        },
    },
    computed: {
        /**
         * Аналог this для привязки к слоту
         * @return {Object}
         */
        self: function () {
            return { ...this };
        },
        /**
         * URN варианта сортировки 
         * совпадает с GET-параметрами sort:order, либо sort
         * @return {String}
         */
        sort: function () {
            let sort = this.query.sort;
            let order = this.query.order;
            if (!sort) {
                if (this.defaultSort) {
                    return this.defaultSort;
                }
                sort = 'price';
            }
            let result = sort;
            if (sort != 'name') {
                result += ':'
                if (order == 'desc') {
                    result += 'desc';
                } else {
                    result += 'asc';
                }
            }
            return result;
        }
    },
}