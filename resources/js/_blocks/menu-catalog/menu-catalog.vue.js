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
        toggle: function () {
            this.active = !this.active;
        }
    },
    computed: {
        self: function () {
            return { ...this };
        },
    },
};