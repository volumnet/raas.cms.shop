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
        modelValue: {
            type: String,
        },
    },
    emits: ['update:modelValue'],
    data() {
        let result = {
            menuActive: false, // Меню раскрыто
        };
        return result;
    },
    mounted() {
        $('body').on('click', () => {
            this.menuActive = false;
        });
    },
    methods: {
        /**
         * Получает параметры сортировки URN
         * @param  {String} urn URN варианта - совпадает с GET-параметрами 
         *     sort:order, либо sort
         * @param {Boolean} useAscAsDefault Использовать значение 'asc' 
         *     по умолчанию, если не задано направление сортировки
         * @return {Object} <pre><code>array<{
         *     sort: String Поле сортировки (без направления),
         *     order: 'asc'|'desc'|null Направление сортировки
         * }></code></pre>
         */
        getVariantURNParams(urn, useAscAsDefault = false) {
            let result = {};
            let sortOrder = urn.split(':');
            result.sort = sortOrder[0];
            result.order = sortOrder[1] || (useAscAsDefault ? 'asc' : null);
            return result;
        },
        /**
         * Получает параметры сортировки варианта
         * @param  {Object} variant <pre><code>array<{
         *     urn: String URN варианта - совпадает с GET-параметрами 
         *         sort:order, либо sort
         *     name: String Наименование варианта
         * }></code></pre> Вариант сортировки
         * @param {Boolean} useAscAsDefault Использовать значение 'asc' 
         *     по умолчанию, если не задано направление сортировки
         * @return {Object} <pre><code>array<{
         *     urn: String URN варианта - совпадает с GET-параметрами 
         *         sort:order, либо sort
         *     name: String Наименование варианта,
         *     sort: String Поле сортировки (без направления),
         *     order: 'asc'|'desc'|null Направление сортировки
         * }></code></pre>
         */
        getVariantParams(variant, useAscAsDefault = false) {
            let result = Object.assign(
                {}, 
                variant, 
                this.getVariantURNParams(variant.urn)
            );
            return result;
        }
    },
    computed: {
        /**
         * Вариант сортировки
         * @return {Object} <pre><code>array<{
         *     urn: String URN варианта - совпадает с GET-параметрами 
         *         sort:order, либо sort
         *     name: String Наименование варианта
         * }></code></pre>
         */
        sort() {
            if (this.source.length) {
                for (let variant of this.source) {
                    if (variant.urn == this.modelValue) {
                        return variant;
                    }
                }
                return this.source[0];
            }
            return { urn: '', name: '' };
        },
        /**
         * Аналог this для привязки к слоту
         * @return {Object}
         */
        self() {
            return { 
                source: this.source,
                modelValue: this.modelValue,
                menuActive: this.menuActive,
                getVariantURNParams: this.getVariantURNParams.bind(this),
                getVariantParams: this.getVariantParams.bind(this),
                sort: this.sort,
            };
        },
    },
}