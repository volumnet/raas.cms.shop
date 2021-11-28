/**
 * Компонент товара в заказе
 */
export default {
    props: {
        /**
         * Товар
         * <pre><code>{
         *     id: Number ID#,
         *     name: String Наименование,
         *     price: Number Стоимость,
         *     price_old: Number Старая цена,
         *     meta: String мета-данные, с которыми добавляется товар
         *     min: Number Минимальное количество для добавления в корзину
         *     step: Шаг корзины,
         *     image: String Ссылка на изображение,
         *     available: Boolean Товар в наличии,
         * }</code></pre>
         */
        item: {
            type: Object,
            required: true
        },
    },
    methods: {
        /**
         * Форматирование цены (отделение тысяч, два знака после запятой)
         * @param  {Number} x Цена
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
    },
};