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
            return $.ajax(ajaxSettings).then((remoteData) => {
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
                $(document).trigger(
                    'raas.shop.cart-updated', 
                    [{id: this.id, remote: true, data: remoteData}]
                );
            });
        }
    }

    /**
     * Устанавливает количество товара
     * @param {Object} item Товар
     * @param {Number} amount Количество единиц товара
     * @param {Object} postData Дополнительные POST-данные
     */
    set(item, amount = null, postData = null) {
        return this.update(
            'action=set' + this.getItemQuery(item, parseInt(amount)), 
            postData
        );
    }


    /**
     * Добавляет товар
     * @param {Object} item Товар
     * @param {Number} amount Количество единиц товара
     * @param {Object} postData Дополнительные POST-данные
     */
    add(item, amount = null, postData = null) {
        return this.update(
            'action=add' + this.getItemQuery(item, parseInt(amount) || 1), 
            postData
        );
    }


    /**
     * Удаляет товар
     * @param {Object} item Товар
     * @param {Object} postData Дополнительные POST-данные
     */
    delete(item, postData) {
        return this.update('action=delete' + this.getItemQuery(item), postData);
    }


    /**
     * Очищает корзину
     * @param {Object} postData Дополнительные POST-данные
     */
    clear(postData) {
        return this.update('action=clear', postData);
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