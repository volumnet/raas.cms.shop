/**
 * Компонент списка избранного
 * требует компонента CatalogList
 */
export default {
    props: {
        /**
         * Корзина
         * @type {Object}
         */
        cart: {
            type: Object,
            required: true,
        },
    },
};