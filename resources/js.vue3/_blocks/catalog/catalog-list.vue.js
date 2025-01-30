/**
 * Список товаров
 */
export default {
    props: {
        /**
         * Данные элементов для отображения
         * @type {Object}
         */
        items: {
            type: Array,
            default() {
                return [];
            },
        },
    },
    data() {
        return {
            viewAs: '', // Отображение (блоками или списком)
        };
    },
    mounted() {
        $(document).on('raas.shop.changeview', (e, variant) => {
            if (variant) {
                this.viewAs = variant;
            }
        })
    },
    computed: {
        /**
         * Аналог this для привязки к слоту
         * @return {Object}
         */
        self() {
            return { 
                items: this.items,
                viewAs: this.viewAs,
            };
        },
    },
}