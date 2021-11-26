/**
 * Компонент списка избранного
 * @requires CatalogList
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