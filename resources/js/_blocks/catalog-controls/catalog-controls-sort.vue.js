/**
 * Меню сортировки панели управления каталогом
 */
export default {
    props: {
        /**
         * Варианты сортировки
         * @type {Array} <pre><code>array<{
         *     urn: String URN варианта - совпадает с GET-параметрами 
         *         sort:order, либо sort
         *     name: String Наименование варианта
         * }></code></pre>
         */
        source: {
            type: Array,
            required: true,
        },
        /**
         * Значение сортировки
         * @type {String} URN варианта - совпадает с GET-параметрами 
         *     sort:order, либо sort
         */
        value: {
            type: String,
        },
    },
    data: function () {
        let result = {
            menuActive: false, // Меню раскрыто
        };
        return result;
    },
    mounted: function () {
        $('body').on('click', () => {
            this.menuActive = false;
        });
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
         * Вариант сортировки
         * @return {Object} <pre><code>array<{
         *     urn: String URN варианта - совпадает с GET-параметрами 
         *         sort:order, либо sort
         *     name: String Наименование варианта
         * }></code></pre>
         */
        sort: function () {
            if (this.source.length) {
                for (let variant of this.source) {
                    if (variant.urn == this.value) {
                        return variant;
                    }
                }
                return this.source[0];
            }
            return { urn: '', name: '' };
        }
    },
}