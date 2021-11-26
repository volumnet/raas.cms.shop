/**
 * Компонент списка товаров в сравнении
 * @requires CatalogList
 */
export default {
    props: {
        /**
         * Список товаров для отображения
         * @type {Object[]}
         */
        items: {
            type: Array,
            required: true,
        },
    },
};