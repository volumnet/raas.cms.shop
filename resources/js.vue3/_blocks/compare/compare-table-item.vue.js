/**
 * Компонент строки таблицы сравнения
 */
export default {
    props: {
        /**
         * Поле для отображения
         * @type {Object}
         */
        field: {
            type: Object,
            required: true,
        },
        /**
         * Характеристики товаров для отображения
         * @type {Array} <pre><code>{
         *     'left'|'right'[]: array<array<
         *         String|{
         *             href: String Ссылка,
         *             text: String Текст
         *         }
         *     >>
         * }</code></pre>
         */
        itemsValues: {
            type: Object,
            required: true,
        },
        /**
         * Параметры сортировки по полю
         * @type {Number} 0 - сортировка не по этому полю, 
         *     1 - сортировка по возрастанию, 
         *     -1 - сортировка по убыванию
         */
        sort: {
            type: Number,
            required: true,
        },
        /**
         * Уникальное поле
         * @type {Boolean}
         */
        unique: {
            type: Boolean,
            required: true,
        },
    },
    emits: ['sort'],
}