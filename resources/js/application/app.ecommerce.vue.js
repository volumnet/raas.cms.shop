/**
 * Mixin приложения с eCommerce Яндекса
 * @requires Shop
 */
export default {
    data: function () {
        return {
            currencyCode: 'RUB', // Валюта сайта
            purchaseGoalId: null, // ID# цели покупки
            couponId: null, // ID# скидочного купона
        };
    },
    mounted: function () {
        if (window.eCommerce) {
            for (let key of ['currencyCode', 'purchaseGoalId', 'couponId']) {
                if (window.eCommerce[key]) {
                    this[key] = window.eCommerce[key];
                }
            }
        }
        $(document).on('raas.shop.ecommerce', (e, data) => {
            let actionData = {
                products: data.products || []
            };
            if ((data.action == 'purchase') && data.orderId) {
                actionData.actionField = {
                    id: data.orderId,
                };
                if (this.couponId) {
                    actionData.actionField.coupon = this.couponId;
                }
                if (this.purchaseGoalId) {
                    actionData.actionField.goal_id = this.purchaseGoalId;
                }
            }
            let eCommerceData = {
                ecommerce: {
                    currencyCode: this.currencyCode
                }
            };
            eCommerceData.ecommerce[data.action] = actionData;

            let dataLayer = (window.dataLayer || []);
            dataLayer.push(eCommerceData);
            console.log(eCommerceData, window.dataLayer);
        });
    }
}