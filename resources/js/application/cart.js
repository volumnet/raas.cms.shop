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
        this.id = id;
        if (updateUrl) {
            this.updateUrl = updateUrl;
        } else {
            this.updateUrl = '/ajax/' + this.id + '/?AJAX=1';
        }
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
                for (let key in ['items', 'sum', 'count', 'additional']) {
                    if (remoteData[key]) {
                        this[key] = remoteData[key];
                    } else {
                        this[key] = null;
                    }
                }
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
     * @param {Object} postData Дополнительные POST-данные
     */
    set(item, postData) {
        this.update('action=set' + getItemQuery(item, item.amount), postData);
    }


    /**
     * Добавляет товар
     * @param {Object} item Товар
     * @param {Object} postData Дополнительные POST-данные
     */
    add(item, postData) {
        this.update(
            'action=add' + getItemQuery(item, parseInt(item.min) || 1), 
            postData
        );
    }


    /**
     * Удаляет товар
     * @param {Object} item Товар
     * @param {Object} postData Дополнительные POST-данные
     */
    delete(item, postData) {
        this.update('action=delete' + getItemQuery(item), postData);
    }


    /**
     * Очищает корзину
     * @param {Object} postData Дополнительные POST-данные
     */
    clear(postData) {
        this.update('action=clear', postData);
    }
}