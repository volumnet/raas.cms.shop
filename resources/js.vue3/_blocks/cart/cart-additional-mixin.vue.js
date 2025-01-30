/**
 * Mixin общих параметров дополнительной информации
 * требует наличия свойства additional и formData
 */
export default {
    computed: {
        /**
         * Информация о доставке из корзины
         * @return {Object}
         */
        delivery() {
            return this.additional.delivery || {};
        },
        /**
         * Информация об оплате из корзины
         * @return {Object}
         */
        payment() {
            return this.additional.payment || {};
        },
        /**
         * Способы доставки
         * @return {Object[]}
         */
        deliveryMethods() {
            return this.delivery.methods || [];
        },
        /**
         * Способы оплаты
         * @return {Object[]}
         */
        paymentMethods() {
            return this.payment.methods || [];
        },
        /**
         * Точки выдачи
         * @return {Object[]}
         */
        pickupPoints() {
            return this.delivery.points || [];
        },
        /**
         * Отфильтрованные пункты выдачи по сервису доставки
         * @return {Object[]} <pre><code>array<{
         *     id: String ID# пункта выдачи,
         *     name: String Наименование пункта выдачи,
         *     address: String Адрес пункта выдачи
         *     description: String Подсказка к адресу,
         *     lat: Number Широта,
         *     lon: Number Долгота,
         *     serviceURN: String URN сервиса,
         *     serviceName: String Наименование сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки,
         *     dateTo?: String Максимальная дата доставки,
         *     schedule?: String Время работы,
         *     phones?: String[] Телефоны (Последние 10 цифр),
         *     images?: String[] URL картинок,
         * }></code></pre>
         */
        filteredPoints() {
            let result = [];
            for (let point of this.pickupPoints) {
                let matchingMethods = this.deliveryMethods.filter((method) => { 
                    return method.serviceURN == point.serviceURN;
                });
                if (matchingMethods.length) {
                    let newPoint = { ...point };
                    newPoint.serviceName = matchingMethods[0].name;
                    if (matchingMethods[0].price !== null) {
                        newPoint.price = matchingMethods[0].price;
                    }
                    for (let key of ['dateFrom', 'dateTo']) {
                        if (matchingMethods[0][key]) {
                            newPoint[key] = matchingMethods[0][key];
                        }
                    }
                    result.push(newPoint);
                }
            }
            return result;
        },
        /**
         * Выбранный способ получения
         * @return {Object|null} <pre><code>{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     isDelivery: Boolean Доставка
         *     fullName: String Полное наименование,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *     dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         * }</code></pre>, либо null если не найден
         */
        selectedReceivingMethod() {
            let result = (this.deliveryMethods || []).filter((method) => {
                return method.id == this.formData.delivery;
            });
            return result[0] || null;
        },
        /**
         * Выбранный способ оплаты
         * @return {Object|null} <pre><code>{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     epay: Boolean Электронная оплата,
         *     price?: Number Комиссия,
         * }</code></pre>, либо null если не найден
         */
        selectedPaymentMethod() {
            let result = (this.paymentMethods || []).filter((method) => {
                return method.id == this.formData.payment;
            });
            return result[0] || null;
        },
        /**
         * Выбранный способ оплаты
         * @return {Object|null} <pre><code>{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     epay: Boolean Электронная оплата,
         *     price?: Number Комиссия,
         * }</code></pre>, либо null если не найден
         */
        selectedPickupPoint() {
            let result = this.filteredPoints.filter((point) => {
                return point.id == this.formData.pickup_point_id;
            });
            return result[0] || null;
        },
    }
}