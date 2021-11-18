/**
 * Компонент элемента списка групп в сравнении
 */
export default {
    props: {
        /**
         * Группа для отображения
         * @type {Object}
         */
        group: {
            type: Object,
            required: true,
        },
        /**
         * ID# активной группы
         * @type {Object}
         */
        value: {
            type: Number,
            required: false,
        },
    },
};