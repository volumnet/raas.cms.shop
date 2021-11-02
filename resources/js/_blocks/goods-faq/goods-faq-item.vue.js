/**
 * Компонент элемента вопрос-ответ товара
 */
export default {
    data: function () {
        return {
            /**
             * Раскрытый текст
             * @type {Boolean}
             */
            active: false,
            /**
             * Текст ответа не влазит в краткое описание
             * @type {Boolean}
             */
            overflowed: false,
        };
    },
    methods: {
        /**
         * Устанавливает значение превышения текста
         * @param {Boolean} isOverflowed
         */
        setOverflows: function (isOverflowed) {
            if (!this.active) {
                this.overflowed = isOverflowed;
            }
        },
        /**
         * Меняет активность
         */
        toggle: function () {
            this.active = !this.active;
        },
    },
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self: function () { 
            return { ...this };
        },
    },
}