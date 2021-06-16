/**
 * Список свойств фильтра
 */
export default {
    props: {
        /**
         * Свойства фильтра
         * @type {Array} <pre><code>array<{
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
         * }></code></pre>
         */
        properties: {
            type: Array,
            required: true,
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
         * Источник данных фильтра
         * @type {Object} <pre><code>{ 
         *     String[] URN характеристики: mixed Значения
         * }</code></pre> GET-параметры
         */
        formData: {
            type: Object,
            required: true,
        },
        /**
         * Источник данных фильтра
         * @type {Object} <pre><code>{ 
         *     String|Number[] ID# характеристики: array<String> Значения
         * }</code></pre> Данные фильтра
         */
        filter: {
            type: Object,
            required: true,
        },
    },
};