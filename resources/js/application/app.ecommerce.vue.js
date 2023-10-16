/**
 * Mixin приложения с eCommerce Яндекса
 * @requires Shop
 */
export default {
    data: function () {
        return {
            ecommerceEnabled: true,
            currencyCode: 'RUB', // Валюта сайта
            purchaseGoalId: null, // ID# цели покупки
            couponId: null, // ID# скидочного купона
        };
    },
    mounted: function () {
        for (let key of ['currencyCode', 'purchaseGoalId', 'couponId']) {
            if (window.eCommerce && window.eCommerce[key]) {
                this[key] = window.eCommerce[key];
            }
        }
        $(document).on('raas.shop.ecommerce', (e, data) => {
            this.cart.getECommerce().trigger(data);
        });
    },
}