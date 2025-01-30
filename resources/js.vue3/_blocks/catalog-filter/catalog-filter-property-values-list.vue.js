/**
 * Список значений фильтра
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
         * Реальные значения свойства
         * @type {Array} <pre><code>array<{
         *     enabled: Boolean Возможна ли отметка свойства,
         *     value: String|Number Значение,
         *     doRich: String Человеко-понятное значение,
         *     checked: Boolean Отмечено ли свойство
         * }></code></pre>
         */
        modelValue: {
            type: Array,
            required: true,
        },
    },
    emits: ['update:modelValue', 'setactiveelement'],
};