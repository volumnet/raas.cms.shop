/**
 * Товар с обновляемой ценой/наличием
 */
export default {
    data() {
        return {
            priceRetrieved: false, // Цены/наличия получены
            remotePrices: {}, // Полученные цены/наличия
        };
    },
    mounted() {
        $(document).on('raas.shop.prices-retrieved', (e, data) => {
            if (data && data[this.item.id + '']) {
                this.priceRetrieved = true;
                this.remotePrices = data[this.item.id + ''];
                this.updateItem();
            }
        });
        window.setTimeout(() => {
            $(this.$el).trigger('raas.shop.request-price', { id: this.item.id });
            // console.log('raas.shop.request-price', { id: this.item.id })
        }, 0); // Чтобы успел отработать обработчик в приложении
    },
    methods: {
        /**
         * Поддерживать товар от принудительного обновления Vue - обновить внешними данными
         */
        updateItem() {
            if (this.remotePrices.price !== undefined) {
                this.item.price = this.remotePrices.price;
            }
            if (this.remotePrices.available !== undefined) {
                this.item.available = this.remotePrices.available;
            }
            if (this.remotePrices.oldPrice !== undefined) {
                this.item.price_old = this.item.priceold = this.remotePrices.oldPrice;
            }
        }
    },
    watch: {
        item() {
            this.updateItem();
        },
    },
};