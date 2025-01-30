/**
 * Приложение с обновляемыми ценами
 */
export default {
    data() {
        return {
            requestedIds: [], // Запрошенные ID# товаров
            remotePrices: {}, // Внешние данные по ценам/наличию
            updatePricesTimeout: 100, // Время в мс, после которого отправляется запрос на цены
            updatePricesTimeoutId: null, // ID# таймаута обновления цен
            goodsPricesURL: '/ajax/goods_prices/',
        };
    },
    mounted() {
        $(document).on('raas.shop.request-price', (e, data) => {
            this.requestPrice(data.id);
        });
    },
    methods: {
        /**
         * Запрос цены
         * @param {Number} id ID# товара
         */
        requestPrice(id) {
            this.requestedIds.push(id);
            if (this.updatePricesTimeoutId) {
                window.clearTimeout(this.updatePricesTimeoutId);
            }
            this.updatePricesTimeoutId = window.setTimeout(() => {
                this.sendRequest();
            }, this.updatePricesTimeout);
            // console.log('Price for #' + id + ' requested');
        },
        /**
         * Передача запроса
         */
        async sendRequest() {
            const uniqueRequestedIds = this.requestedIds.filter((id, index, array) => {
                return array.indexOf(id) === index;
            });
            const realRequestedIds = uniqueRequestedIds.filter((id) => {
                return Object.keys(this.remotePrices).indexOf(id + '') == -1;
            });
            if (realRequestedIds.length) {
                const url = this.goodsPricesURL + '?' + realRequestedIds.map(x => ('id[]=' + x)).join('&');
                const data = await this.api(url, null, this.blockId);
                if (data.prices) {
                    this.remotePrices = Object.assign({}, this.remotePrices, data.prices);
                }
            }
            const result = {};
            for (let id of uniqueRequestedIds) {
                if (this.remotePrices[id + '']) {
                    result[id + ''] = this.remotePrices[id + ''];
                }
            }
            $(document).trigger('raas.shop.prices-retrieved', result);
            // console.log(result);
            this.requestedIds = [];

        }
    },
};