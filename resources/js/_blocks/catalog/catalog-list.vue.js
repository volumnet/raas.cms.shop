/**
 * Список товаров
 */
export default {
    data: function () {
        return {
            viewAs: '', // Отображение (блоками или списком)
        };
    },
    mounted: function () {
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
        self: function () {
            return { ...this };
        },
    },
}