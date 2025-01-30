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
        this.cart = cart;
        this.items = JSON.parse(JSON.stringify(cart.items));
        // console.log(this.items);
        this.autoTrigger = autoTrigger;
        window.setTimeout(() => {
            for (let key of ['currencyCode', 'purchaseGoalId', 'couponId']) {
                let eCommerceVal = (window.eCommerce && window.eCommerce[key]) || window.app[key];
                if (eCommerceVal) {
                    this[key] = eCommerceVal;
                }
            }

        })
    }


    /**
     * Обновляет данные о товарах в корзине
     * @param {Object[]} newItems Новые товары корзины
     * @param {Boolean} trigger Генерировать событие
     * @return {Object[]} Набор объектов для генерации событий
     */
    updateCart(newItems, trigger = true)
    {
        if (this.cart.dataLoaded && trigger && this.autoTrigger) { // Только если обновление, при первой загрузке не вызываем
            // Найдем товары для удаления
            const itemsToDelete = this.items.filter(oldItem => {
                let matchingNewItems = newItems
                    .filter(newItem => ((newItem.id == oldItem.id) && (newItem.meta == oldItem.meta)));
                return !matchingNewItems.length;
            }).map(item => {
                return { ...item, amount: 0, oldAmount: item.amount };
            });
            const itemsToChange = newItems.map(newItem => {
                let matchingOldItems = this.items
                    .filter(oldItem => ((newItem.id == oldItem.id) && (newItem.meta == oldItem.meta)));
                return { ...newItem, oldAmount: matchingOldItems.length ? matchingOldItems[0].amount : 0 };
            }).filter(item => item.amount != item.oldAmount);
            const items = itemsToDelete.concat(itemsToChange);

            // Уже для eCommerce
            const itemsToAdd = itemsToChange.filter(item => item.amount > item.oldAmount).map(item => {
                return { ...item.eCommerce, amount: item.amount - item.oldAmount };
            }); 
            const itemsToRemove = items.filter(item => item.amount < item.oldAmount).map(item => {
                return { ...item.eCommerce, amount: item.oldAmount - item.amount };
            });

            const result = [];
            if (itemsToRemove.length) {
                result.push({ action: 'remove', products: itemsToRemove });
            }
            if (itemsToAdd.length) {
                result.push({ action: 'add', products: itemsToAdd });
            }

            if (result.length) {
                this.trigger(result);
            }
        }
        this.items = JSON.parse(JSON.stringify(newItems));
    }


    /**
     * Вызывает событие ECommerce
     * @param {Object|Object[]} data Данные для отображения
     */
    trigger(data) {
        if (!(data instanceof Array) && data) {
            data = [data];
        }
        if ((this.cart.id != 'cart') || !data || !data.length || !window.app.ecommerceEnabled) {
            return null;
        }
        const eCommerceData = { ecommerce: { currencyCode: this.currencyCode } };
        for (const event of data) {
            let actionData;
            if (event.action == 'impressions') {
                actionData = event.products;
            } else {
                actionData = { products: event.products || [] };
            }
            if ((event.action == 'purchase') && event.orderId) {
                actionData.actionField = { id: event.orderId };
                if (this.couponId) {
                    actionData.actionField.coupon = this.couponId;
                }
                if (this.purchaseGoalId) {
                    actionData.actionField.goal_id = this.purchaseGoalId;
                }
            }
            eCommerceData.ecommerce[event.action] = actionData;
        }

        const dataLayer = (window.dataLayer || []);
        dataLayer.push(eCommerceData);
        console.log(eCommerceData, window.dataLayer);
    }
};