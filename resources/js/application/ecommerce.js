/**
 * Класс работы с eCommerce
 */
export default class {
    /**
     * Конструктор класса
     * @param {Cart} cart Корзина
     * @param {Boolean} autoTrigger Автоматически вызывать событие
     */
    constructor(cart, autoTrigger = false) {
        this.items = cart.items;
        this.autoTrigger = autoTrigger;
        if (window.eCommerce) {
            for (let key of ['currencyCode', 'purchaseGoalId', 'couponId']) {
                if (window.eCommerce[key]) {
                    this[key] = window.eCommerce[key];
                }
            }
        }
    }


    /**
     * Вызывает событие ECommerce
     * @param {Object} data Данные для отображения
     */
    trigger(data) {
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

    }


    /**
     * Устанавливает количество товара
     * @param {Object} item Товар
     * @param {Number} amount Количество единиц товара
     * @return {Object} Данные для установки в eCommerce после обновления корзины
     */
    set(item, amount = null) {
        let result = null;
        if (item.eCommerce) {
            let matchingItems = this.items
                .filter(x => ((x.id == item.id) && (x.meta == (item.meta || ''))));
            let oldAmount = 0;
            if (matchingItems && matchingItems[0]) {
                oldAmount = parseFloat(matchingItems[0].amount) || 0;
            }
            // console.log(oldAmount, amount);
            let deltaAmount = amount - oldAmount;
            if (deltaAmount != 0) {
                result = {
                    action: (deltaAmount < 0) ? 'remove' : 'add',
                    products: [Object.assign(
                        {}, 
                        item.eCommerce, 
                        { amount: Math.abs(deltaAmount) }
                    )],
                };
                if (this.autoTrigger) {
                    this.trigger(result);
                }
            }
        }
        return result;
    }


    /**
     * Добавляет товар
     * @param {Object} item Товар
     * @param {Number} amount Количество единиц товара
     * @return {Object} Данные для установки в eCommerce после обновления корзины
     */
    add(item, amount = null) {
        let result = null;
        if (item.eCommerce) {
            if (amount != 0) {
                result = {
                    action: (amount < 0) ? 'remove' : 'add',
                    products: [Object.assign(
                        {}, 
                        item.eCommerce, 
                        { amount: Math.abs(amount) }
                    )],
                };
                if (this.autoTrigger) {
                    this.trigger(result);
                }
            }
        }
        return result;
    }


    /**
     * Удаляет товар
     * @param {Object} item Товар
     * @return {Object} Данные для установки в eCommerce после обновления корзины
     */
    delete(item) {
        let result = null;
        if (item.eCommerce) {
            let matchingItems = this.items
                .filter(x => ((x.id == item.id) && (x.meta == (item.meta || ''))));
            let oldAmount = 0;
            if (matchingItems && matchingItems[0]) {
                oldAmount = parseFloat(matchingItems[0].amount) || 0;
            }
            // console.log(oldAmount, amount);
            if (oldAmount != 0) {
                result = {
                    action: 'remove',
                    products: [Object.assign(
                        {}, 
                        item.eCommerce, 
                        { amount: oldAmount }
                    )],
                };
                if (this.autoTrigger) {
                    this.trigger(result);
                }
            }
        }
        return result;
    }


    /**
     * Очищает корзину
     * @return {Object} Данные для установки в eCommerce после обновления корзины
     */
    clear() {
        let result = null;
        result = {
            action: 'remove',
            products: this.items.filter(x => !!x.eCommerce).map((x) => {
                return Object.assign({}, x.eCommerce, { amount: x.amount });
            }),
        }
        if (this.autoTrigger) {
            this.trigger(result);
        }
        return result;
    }
};