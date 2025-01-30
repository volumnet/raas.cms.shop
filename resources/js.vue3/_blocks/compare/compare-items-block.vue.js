/**
 * Компонент блока товаров в сравнении
 */
export default {
    props: {
        /**
         * Сторона блока
         * @type {String}
         */
        side: {
            type: String,
            required: true,
        },
        /**
         * Товары для отображения
         * @type {Array}
         */
        items: {
            type: Array,
            required: true,
        },
    },
    emits: ['slid'],
    data() {
        let result = {};
        /**
         * Анимация слайдера, активная в текущий момент
         * @type {Object} <pre><code>{
         *     left: ''|'left'|'right' левая анимация,
         *     right: ''|'left'|'right' правая анимация
         * }</code></pre>
         */
        result.slideAnimation = {
            left: '',
            right: '',
        };
        return result;
    },
    mounted() {
        // $(document).on('raas.shop.compare-sorted', (e) => {
        //     this.$el.__vue__.refresh(true);
        // });
        // $(document).on('raas.shop.cart-updated', (e) => {
        //     this.$el.__vue__.refresh(false);
        // });
    },
};