/**
 * Меню каталога
 */
export default {
    data: function () {
        return {
            /**
             * Активность меню по кнопке
             * @type {Boolean}
             */
            active: false,
        };
    },
    mounted: function () {
        $('body').on('click', () => {
            this.active = false;
        })
    },
    methods: {
        /**
         * Переключение меню
         */
        toggle: function () {
            this.active = !this.active;
        }
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
};