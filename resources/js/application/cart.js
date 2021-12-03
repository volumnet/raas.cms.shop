/**
 * Класс корзины
 */
export default class {
    /**
     * Конструктор класса
     * @param {String} id Идентификатор (URN) корзины
     * @param {String|null} updateUrl URL для обновления
     */
    constructor(id, updateUrl = null) {
        /**
         * Идентификатор (URN) корзины
         * @type {String}
         */
        this.id = id;
        if (updateUrl) {
            this.updateUrl = updateUrl;
        } else {
            this.updateUrl = '/ajax/' + this.id + '/?AJAX=1';
        }
        /**
         * Получены данные
         * @type {Boolean}
         */
        this.dataLoaded = false;

        /**
         * Идет загрузка
         * @type {Boolean}
         */
        this.loading = false;

        /**
         * Товары в корзине
         * @type {Array|null}
         */
        this.items = [];

        /**
         * Сумма товаров без учета дополнительных данных 
         * (доставка, скидки и т.д.)
         * @type {Number}
         */
        this.sum = 0;

        /**
         * Количество товаров без учета дополнительных данных 
         * (доставка, скидки и т.д.)
         * @type {Number}
         */
        this.count = 0;

        /**
         * Количество товаров с учетом дополнительных данных 
         * (доставка, скидки и т.д.)
         * @type {Number}
         */
        this.rollup = 0;

        /**
         * Дополнительные данные * (доставка, скидки и т.д.)
         * @type {mixed|null}
         */
        this.additional = null;
    }


    /**
     * Получает часть строки запроса для товара
     * @param {Object} item Товар для получения строки запроса
     * @param {Number} amount Количество единиц товара
     * @return {String}
     */
    getItemQuery(item, amount = null) {
        let query = '&id=' + (parseInt(item.id) || 0);
        if (item.meta) {
            query += '&meta=' + encodeURIComponent(item.meta);
        }
        if (amount !== null) {
            query += '&amount=' + (parseInt(amount) || 0);
        }
        return query;
    }


    /**
     * Производит обновление корзины в общем виде
     * @param  {String} query GET-параметры запроса
     * @param  {Object} postData POST-параметры запроса
     */
    update(query, postData) {
        let self = this;
        let url = this.updateUrl;
        if (query) {
            url += (/\?/gi.test(url) ? '&' : '?');
            url += query;
        }
        if (url) {
            let ajaxSettings = {
                url: url,
                method: 'GET',
                dataType: 'json',
            };
            if (postData) {
                ajaxSettings.method = 'POST';
                ajaxSettings.data = postData;
            }
            this.loading = true;
            return $.ajax(ajaxSettings).then((remoteData) => {
                this.updateData(remoteData);
            });
        }
    }


    /**
     * Производит обновление корзины данными
     * @param {Object} remoteData Данные для обновления
     */
    updateData(remoteData) 
    {
        for (let key of [
            'items', 
            'sum', 
            'count', 
            'rollup', 
            'additional'
        ]) {
            if (remoteData[key] !== undefined) {
                this[key] = remoteData[key];
            } else {
                this[key] = null;
            }
        }
        this.dataLoaded = true;
        this.loading = false;
        $(document).trigger(
            'raas.shop.cart-updated', 
            [{id: this.id, remote: true, data: remoteData}]
        );
    }

    /**
     * Устанавливает количество товара
     * @param {Object} item Товар
     * @param {Number} amount Количество единиц товара
     * @param {Object} postData Дополнительные POST-данные
     */
    set(item, amount = null, postData = null) {
        let eCommerceData = null;
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
                eCommerceData = {
                    action: (deltaAmount < 0) ? 'remove' : 'add',
                    products: [Object.assign(
                        {}, 
                        item.eCommerce, 
                        { amount: Math.abs(deltaAmount) }
                    )],
                };
            }
        }
        let result = this.update(
            'action=set' + this.getItemQuery(item, parseInt(amount)), 
            postData
        ).then(() => {
            if (eCommerceData) {
                $(document).trigger('raas.shop.ecommerce', eCommerceData);
            }
        });
        return result;
    }


    /**
     * Добавляет товар
     * @param {Object} item Товар
     * @param {Number} amount Количество единиц товара
     * @param {Object} postData Дополнительные POST-данные
     */
    add(item, amount = null, postData = null) {
        let eCommerceData = null;
        if (item.eCommerce) {
            if (amount != 0) {
                eCommerceData = {
                    action: (amount < 0) ? 'remove' : 'add',
                    products: [Object.assign(
                        {}, 
                        item.eCommerce, 
                        { amount: Math.abs(amount) }
                    )],
                };
            }
        }
        let result = this.update(
            'action=add' + this.getItemQuery(item, parseInt(amount) || 1), 
            postData
        ).then(() => {
            if (eCommerceData) {
                $(document).trigger('raas.shop.ecommerce', eCommerceData);
            }
        });
        return result;
    }


    /**
     * Удаляет товар
     * @param {Object} item Товар
     * @param {Object} postData Дополнительные POST-данные
     */
    delete(item, postData) {
        let eCommerceData = null;
        if (item.eCommerce) {
            let matchingItems = this.items
                .filter(x => ((x.id == item.id) && (x.meta == (item.meta || ''))));
            let oldAmount = 0;
            if (matchingItems && matchingItems[0]) {
                oldAmount = parseFloat(matchingItems[0].amount) || 0;
            }
            // console.log(oldAmount, amount);
            if (oldAmount != 0) {
                eCommerceData = {
                    action: 'remove',
                    products: [Object.assign(
                        {}, 
                        item.eCommerce, 
                        { amount: oldAmount }
                    )],
                };
            }
        }
        let result = this.update(
            'action=delete' + this.getItemQuery(item), 
            postData
        ).then(() => {
            if (eCommerceData) {
                $(document).trigger('raas.shop.ecommerce', eCommerceData);
            }
        });
        return result;
    }


    /**
     * Очищает корзину
     * @param {Object} postData Дополнительные POST-данные
     */
    clear(postData) {
        let eCommerceData = null;
        if (this.items && this.items.length) {
            eCommerceData = {
                action: 'remove',
                products: this.items.filter(x => !!x.eCommerce).map((x) => {
                    return Object.assign({}, x.eCommerce, { amount: x.amount });
                }),
            }
        }
        let result = this.update('action=clear', postData).then(() => {
            if (eCommerceData && 
                eCommerceData.products && 
                eCommerceData.products.length
            ) {
                $(document).trigger('raas.shop.ecommerce', eCommerceData);
            }
        });
        return result;
    }


    /**
     * Проверяет количество товара в корзине
     * @param {Object} item Товар
     * @return {Number} Количество товара в корзине
     */
    checkAmount(item) {
        try {
            let filteredItems = this.items.filter((x) => {
                let result = (x.id == item.id) && 
                    ((x.meta || '') == (item.meta || ''));
                return result;
            });
            // console.log(this.item.id, this.item.meta, this.id, filteredItems[0]);
            return filteredItems[0].amount;
        } catch (e) {
            return 0;
        }
    }
}