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
        modelValue: {
            type: Number,
            required: false,
        },
    },
    emits: ['update:modelValue'],
};