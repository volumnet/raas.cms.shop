/**
 * Компонент списка групп сравнения
 */
export default {
    props: {
        /**
         * Группы для отображения
         * @type {Object}
         */
        groups: {
            type: Array,
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