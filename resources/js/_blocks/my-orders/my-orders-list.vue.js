/**
 * Компонент списка "Мои заказы"
 */
export default {
    props: {
        /**
         * ID# блока
         * @type {Number}
         */
        blockId: {
            type: Number,
            required: true,
        },
        /**
         * Исходный список заказов
         * @type {Object[]}
         */
        initialItems: {
            type: Array,
            required: true,
        },
    },
    data: function () {
        let translations = {
            DATE: 'Дата',
            GOODS: 'Товары',
            STATUS: 'Статус',
            SUM: 'Сумма',
            YOU_HAVE_NO_ORDERS: 'У вас пока нет ни одного заказа',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
            items: this.initialItems, // Список заказов
        };
    },
    methods: {
        /**
         * Обработчик удаления заказа
         * @param {Object} event <pre><code>{
         *     item: Object Удаленный заказ,
         *     result: Object Результат (список заказов) после удаления заказа
         * }</code></pre>
         */
        onDeleteItem: function (event) {
            if (event.result && event.result.items) {
                this.items = event.result.items;
            }
        },
    },
};