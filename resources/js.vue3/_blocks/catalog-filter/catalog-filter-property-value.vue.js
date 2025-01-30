/**
 * Значение фильтра
 */
export default {
    props: {
        /**
         * Свойство
         * @type {Object} <pre><code>{
         *     id: Number ID# свойства,
         *     urn: String URN свойства,
         *     datatype: String Тип данных свойства,
         *     multiple: Number|Boolean Множественное ли свойство,
         *     name: String Наименование свойства,
         *     priority: Number Порядок отображения
         *     stdSource: null|Array Источник данных свойства,
         *     values: {
         *         String|Number ID# значения: {
         *             enabled: Boolean Возможна ли отметка свойства,
         *             value: String|Number Значение,
         *             doRich: String Человеко-понятное значение,
         *             checked: Boolean Отмечено ли свойство
         *         }
         *     }
         * }</code></pre>
         */
        property: {
            type: Object,
            required: true,
        },
        /**
         * Значение свойства
         * @type {Array} <pre><code>{
         *     enabled: Boolean Возможна ли отметка свойства,
         *     value: String|Number Значение,
         *     doRich: String Человеко-понятное значение,
         *     checked: Boolean Отмечено ли свойство
         * }</code></pre>
         */
        modelValue: {
            type: Object,
            required: true,
        },
    },
    emits: ['update:modelValue', 'setactiveelement'],
    computed: {
        /**
         * Получает URL установленного/отмененного значения
         * @return {String}
         */
        url() {
            let urn = this.property.urn;
            let value = this.modelValue.value;
            let qs = window.queryString.parse(
                window.location.search, 
                { arrayFormat: 'bracket' }
            );
            if (qs[urn] instanceof Array) {
                let i = qs[urn].indexOf(value);
                if (i != -1) {
                    qs[urn].splice(i, 1);
                } else {
                    qs[urn].push(value);
                }
            } else {
                qs[urn] = [value];
            }
            let result = '?' 
                + window.queryString.stringify(qs, { arrayFormat: 'bracket' });
            return result;
        }
    },
    watch: {
        modelValue() {
            this.checked = this.modelValue.checked;
        },
    },
};