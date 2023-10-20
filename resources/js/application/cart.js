import ECommerce from './ecommerce.js';


/**
 * Класс корзины
 */
export default class {
    /**
     * Конструктор класса
     * @param {String} id Идентификатор (URN) корзины
     * @param {String|null} updateUrl URL для обновления
     * @param {Number} blockId ID# блока
     */
    constructor(id, updateUrl = null, blockId = null) {
        /**
         * Идентификатор (URN) корзины
         * @type {String}
         */
        this.id = id;

        /**
         * ID# блока
         * @type {Number}
         */
        this.blockId = blockId;
        
        /**
         * URL для обновления
         * @type {String}
         */
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

        /**
         * Экземпляр ECommerce
         * @type {ECommerce}
         */
        this.eCommerce = (this.id == 'cart') ? (new ECommerce(this)) : null;
    }


    /**
     * Получает часть строки запроса для товара
     * @param {Object|Object[]} items Товар или набор товаров
     * @param {Number} amount Количество единиц товара
     * @return {String}
     */
    getItemQuery(items, amount = null) {
        let query = '';
        let item = null;
        if (items instanceof Array) {
            if (items.length == 1) {
                item = items[0];
            } else if (!items.length) {
                return '';
            }
        } else {
            item = items;
        }

        if (item) {
            query = '&id=' + (parseInt(item.id) || 0);
            if (item.meta) {
                query += '&meta=' + encodeURIComponent(item.meta);
            }
            if (amount !== null) {
                query += '&amount=' + (parseInt(amount) || 0);
            }
        } else {
            for (let item of items) {
                query += '&id[' + item.id + (item.meta ? ('_' + item.meta) : '') + ']=';
                if (item.amount !== null) {
                    query += (parseInt(item.amount) || 0);
                } else {
                    query += 1;
                }
            }
        }
        return query;
    }


    /**
     * Производит обновление корзины в общем виде
     * @param  {String} query GET-параметры запроса
     * @param  {Object} postData POST-параметры запроса
     */
    async update(query, postData) {
        let self = this;
        let url = this.updateUrl;
        if (query) {
            url += (/\?/gi.test(url) ? '&' : '?');
            url += query;
        }
        if (url) {
            this.loading = true;
            const remoteData = await window.app.api(url, postData || null, this.blockId);
            this.updateData(remoteData);
            this.loading = false;
        }
    }


    /**
     * Производит обновление корзины данными
     * @param {Object} remoteData Данные для обновления
     */
    updateData(remoteData) 
    {
        const eCommerce = this.getECommerce();
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
        if (eCommerce) {
            // console.log(remoteData);
            eCommerce.updateCart(this.items, !remoteData.success); // Именно здесь, поскольку до установки dataLoaded, eCommerce не генерирует событий
        }
        this.dataLoaded = true;
        $(document).trigger('raas.shop.cart-updated',  [{id: this.id, remote: true, data: remoteData}]);
    }


    /**
     * Устанавливает количество товара
     * @param {Object|Object[]} items Товар или набор товаров
     * @param {Number} amount Количество единиц товара
     * @param {Object} postData Дополнительные POST-данные
     */
    async set(items, amount = null, postData = null) {
        await this.update('action=set' + this.getItemQuery(items, parseInt(amount)), postData);
    }


    /**
     * Устанавливает все товары с заменой
     * @param {Object[]} items Набор товаров
     * @param {Object} postData Дополнительные POST-данные
     */
    async setAll(items, postData = null) {
        // console.log(items)
        let itemsToDelete = JSON.parse(JSON.stringify(this.items)).filter(oldItem => {
            let matchingNewItems = items.filter(newItem => ((newItem.id == oldItem.id) && (newItem.meta == oldItem.meta)));
            return matchingNewItems.length == 0;
        }).map((oldItem) => {
            oldItem.amount = 0;
            return oldItem;
        });

        await this.update('action=set&clear=1' + this.getItemQuery(items), postData);
    }


    /**
     * Добавляет товар
     * @param {Object|Object[]} items Товар или набор товаров
     * @param {Number} amount Количество единиц товара
     * @param {Object} postData Дополнительные POST-данные
     */
    async add(items, amount = null, postData = null) {
        await this.update('action=add' + this.getItemQuery(items, parseInt(amount) || 1), postData);
    }


    /**
     * Удаляет товар
     * @param {Object|Object[]} items Товар или набор товаров
     * @param {Object} postData Дополнительные POST-данные
     */
    async delete(items, postData) {
        await this.update('action=delete' + this.getItemQuery(items), postData);
    }


    /**
     * Очищает корзину
     * @param {Object} postData Дополнительные POST-данные
     */
    async clear(postData) {
        await this.update('action=clear', postData);
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


    /**
     * Получает экземпляр ECommerce
     * @param {Boolean} autoTrigger Автоматически генерировать событие
     * @return {ECommerce}
     */
    getECommerce(autoTrigger = true) {
        if (this.eCommerce) {
            this.eCommerce.autoTrigger = autoTrigger;
        }
        return this.eCommerce;
        // return new ECommerce(this, autoTrigger);
    }
}