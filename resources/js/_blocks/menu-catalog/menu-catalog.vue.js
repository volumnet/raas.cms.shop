/**
 * Меню каталога
 */
export default {
    data() {
        return {
            /**
             * Активность меню по кнопке
             * @type {Boolean}
             */
            active: false,
        };
    },
    mounted() {
        $('body').on('click', () => {
            this.active = false;
        })
    },
    methods: {
        /**
         * Переключение меню
         */
        toggle() {
            this.active = !this.active;
        }
    },
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self() {
            return { ...this };
        },
    },
};