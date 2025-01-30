/**
 * Модальное окно "Товар добавлен"
 */
export default {
    data() {
        let translations = {
            ORDER_ROLLUP: 'Общая сумма заказа:',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            items: [], // Добавленные товары
            cart: null, // Объект корзины (только в случае корзины с суммой)
            href: '', // Ссылка для перехода
            title: '', // Заголовок окна
            submitTitle: '', // Заголовок кнопки перехода
            dismissTitle: '', // Заголовок кнопки отмены
            translations, // Переводы
        };
    },
    methods: {
        /**
         * Форматирование цены
         * @param  {Number} x Цена
         * @return {String}
         */
        formatPrice(x) {
            return window.formatPrice(x);
        },
        /**
         * Метод отображения модального окна
         * @param  {Object} data <pre><code>{
         *     items: Array Добавленные товары
         *     cart: Object Объект корзины (только в случае корзины с суммой)
         *     href: String Ссылка для перехода
         *     title: String Заголовок окна
         *     submitTitle: String Заголовок кнопки перехода
         *     dismissTitle: String Заголовок кнопки отмены
         *     translations: Object Переводы
         * }</code></pre> Данные для отображения
         */
        show(data = {}) {
            this.items = [];
            this.cart = null;
            this.href = '';
            this.title = '';
            this.submitTitle = '';
            this.dismissTitle = '';
            for (let key in data) {
                this[key] = data[key];
            }
            $(this.$el).modal('show');
        }
    },
}