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
    data() {
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
    mounted() {
        window.setTimeout(() => { // Поскольку app.vue инициализируется после компонента
            this.changeView(Cookie.get('view_as'));
            // 2022-12-08, AVS: запутывает, непонятно зачем сделано
            // if (this.defaultSort) {
            //     this.changeSort(this.defaultSort);
            // }
        }, 0);
    },
    methods: {
        update() {
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
        changeSort(sortVariant) {
            let sortArr = sortVariant.trim().split(':');
            this.query.sort = sortArr[0];
            this.query.order = sortArr[1] || 'asc';
            this.$forceUpdate();
        },
        changeView(viewVariant) {
            if (!viewVariant) {
                return;
            }
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
         * URN варианта сортировки 
         * совпадает с GET-параметрами sort:order, либо sort
         * @return {String}
         */
        sort() {
            let sort = this.query.sort;
            let order = this.query.order;
            // alert(sort + ' ' + order)
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
        },
        /**
         * Аналог this для привязки к слоту
         * @return {Object}
         */
        self() {
            return {
                defaultSort: this.defaultSort,
                activeViewVariant: this.activeViewVariant,
                query: this.query,
                update: this.update.bind(this),
                changeSort: this.changeSort.bind(this),
                changeView: this.changeView.bind(this),
                sort: this.sort,
            };
        },
    },
}