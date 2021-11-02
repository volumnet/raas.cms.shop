/**
 * Компонент способа оплаты
 */
export default {
    props: {
        /**
         * Источник данных
         * @type {Object[]} <pre><code>array<{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     epay: Boolean Электронная оплата,
         *     price?: Number Комиссия,
         * }></code></pre>
         */
        source: {
            type: Array,
            required: true,
        },
        /**
         * Наименование контрола доставки
         * @type {Object}
         */
        name: {
            type: String,
            required: true,
        },
        /**
         * ID# выбранной доставки
         * @type {Object}
         */
        value: {
            required: true,
        },
    },
}
