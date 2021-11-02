/**
 * Компонент списка способов оплаты
 */
export default {
    props: {
        /**
         * Метод оплаты
         * @type {Object} <pre><code>{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     epay: Boolean Электронная оплата,
         *     price?: Number Комиссия,
         * }</code></pre>
         */
        method: {
            type: Object,
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
