/**
 * Выпадающее меню в фильтре
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
        values: {
            type: Array,
            required: true,
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
    },
    data: function () {
        let translations = {
            DOESNT_MATTER: 'не важно',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations,
        };
    },
};