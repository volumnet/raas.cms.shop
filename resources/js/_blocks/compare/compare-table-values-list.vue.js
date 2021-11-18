/**
 * Компонент списка значений таблицы сравнения
 */
export default {
    props: {
        /**
         * Характеристики товаров для отображения
         * @type {Array} <pre><code>array<array<
         *     String|{
         *         href: String Ссылка,
         *         text: String Текст
         *     }
         * >></code></pre>
         */
        itemsValues: {
            type: Array,
            required: true,
        },
    },
};