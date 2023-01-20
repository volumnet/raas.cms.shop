import CartAdditionalMixin from './cart-additional-mixin.vue.js';

/**
 * Компонент блока самовывоза
 */
export default {
    mixins: [CartAdditionalMixin],
    props: {
        /**
         * Способы получения 
         * @type {Array} <pre><code>array<{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     fullName: String Полное наименование,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *     dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         * }></code></pre>
         */
        methods: {
            type: Array,
            required: true,
        },
        /**
         * Пункты выдачи
         * @type {Array} <pre><code>array<{
         *     id: String ID# пункта выдачи,
         *     name: String Наименование пункта выдачи,
         *     address: String Адрес пункта выдачи
         *     description: String Подсказка к адресу,
         *     lat: Number Широта,
         *     lon: Number Долгота,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки,
         *     dateTo?: String Максимальная дата доставки,
         *     schedule?: String Время работы,
         *     phones?: String[] Телефоны (Последние 10 цифр),
         *     images?: String[] URL картинок,
         * }></code></pre>
         */
        points: {
            type: Array,
            required: true,
        },
        /**
         * Наименование контрола ID# пункта выдачи
         * @type {Object}
         */
        name: {
            type: String,
            required: true,
        },
        /**
         * ID# выбранного пункта выдачи
         * @type {Object}
         */
        value: {
            required: true,
        },
    },
    data: function () {
        let result = {
            methodsFilter: this.methods.map(x => x.id), // Фильтр по ТК
            listActive: true,
        };
        return result;
    },
    methods: {
        /**
         * Обработчик события выбора точки на карте
         * @param {Object} point <pre><code>{
         *     id: String ID# пункта выдачи,
         *     name: String Наименование пункта выдачи,
         *     address: String Адрес пункта выдачи
         *     description: String Подсказка к адресу,
         *     lat: Number Широта,
         *     lon: Number Долгота,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки,
         *     dateTo?: String Максимальная дата доставки,
         *     schedule?: String Время работы,
         *     phones?: String[] Телефоны (Последние 10 цифр),
         *     images?: String[] URL картинок,
         * }</code></pre> Пункт выдачи
         */
        selectMapPoint: function (point) {
            this.listActive = true;
            this.$emit('input', point.id);
            window.setTimeout(() => {
                let $point = $('input[type="radio"][name="' + this.name + '"][value="' + point.id + '"]').closest('label');
                let $list = $('.cart-points-list__list');
                if ($point.length) {
                    $list.scrollTop($list.scrollTop() + $point.position().top)
                }
            }, 100); // Чтобы успело поменяться значение
        },
        /**
         * Привязать обработчик события на балун точки
         * @param {Object} point <pre><code>{
         *     id: String ID# пункта выдачи,
         *     name: String Наименование пункта выдачи,
         *     address: String Адрес пункта выдачи
         *     description: String Подсказка к адресу,
         *     lat: Number Широта,
         *     lon: Number Долгота,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки,
         *     dateTo?: String Максимальная дата доставки,
         *     schedule?: String Время работы,
         *     phones?: String[] Телефоны (Последние 10 цифр),
         *     images?: String[] URL картинок,
         * }</code></pre> Пункт выдачи
         */
        bindMapPointListener: function (point) {
            document.getElementById('ymapsBalloonSelector').addEventListener(
                'click', 
                this.selectMapPoint.bind(this, point)
            );
        },
        /**
         * Отвязать обработчик события от балуна точки
         * @param {Object} point <pre><code>{
         *     id: String ID# пункта выдачи,
         *     name: String Наименование пункта выдачи,
         *     address: String Адрес пункта выдачи
         *     description: String Подсказка к адресу,
         *     lat: Number Широта,
         *     lon: Number Долгота,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки,
         *     dateTo?: String Максимальная дата доставки,
         *     schedule?: String Время работы,
         *     phones?: String[] Телефоны (Последние 10 цифр),
         *     images?: String[] URL картинок,
         * }</code></pre> Пункт выдачи
         */
        unbindMapPointListener: function (point) {
            document.getElementById('ymapsBalloonSelector').removeEventListener(
                'click', 
                this.selectMapPoint.bind(this, point)
            );
        },
    },
    computed: {
        /**
         * Источник данных для фильтра
         * @return {Object[]}
         */
        filterSource: function () {
            let result = this.methods.map((x) => {
                return { value: x.id, name: x.name };
            });
            return result;
        },
        /**
         * Отфильтрованные пункты выдачи
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
        filteredPoints: function () {
            let result = [];
            for (let point of this.points) {
                let matchingMethods = this.methods.filter((method) => { 
                    if (this.methodsFilter.length && 
                        this.methodsFilter.indexOf(method.id) == -1
                    ) {
                        return false;
                    }
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
         * Границы Яндекс-карт
         * @return {Array} <pre><code>[
         *     [Number минимальная широта, Number минимальная долгота],
         *     [Number максимальная широта, Number максимальная долгота]
         * ]</code></pre>
         */
        mapBounds: function () {
            let minLat = 0;
            let minLon = 0;
            let maxLat = 0;
            let maxLon = 0;
            for (let point of this.filteredPoints) {
                let lat = parseFloat(point.lat) || 0;
                let lon = parseFloat(point.lon) || 0;
                if (lat) {
                    minLat = minLat ? Math.min(minLat, lat) : lat;
                    maxLat = maxLat ? Math.max(maxLat, lat) : lat;
                }
                if (lon) {
                    minLon = minLon ? Math.min(minLon, lon) : lon;
                    maxLon = maxLon ? Math.max(maxLon, lon) : lon;
                }
            }
            if (minLat && maxLat && (minLat == maxLat)) {
                minLat -= 0.01;
                maxLat += 0.01;
            }
            if (minLon && maxLon && (minLon == maxLon)) {
                minLon -= 0.01;
                maxLon += 0.01;
            }
            return [[minLat, minLon], [maxLat, maxLon]];
        },
        /**
         * Координаты центра Яндекс-карт
         * @return {Array} <pre><code>[
         *     Number широта, 
         *     Number долгота
         * ]</code></pre>
         */
        mapCoords: function () {
            let lat = 0;
            let lon = 0;
            let c = 0;
            for (let point of this.filteredPoints) {
                if (parseFloat(point.lat) && parseFloat(point.lon)) {
                    lat += parseFloat(point.lat);
                    lon += parseFloat(point.lon);
                    c++;
                }
            }
            if (c) {
                lat /= c;
                lon /= c;
            }
            return [lat, lon];
        },
    },
    watch: {
        methodsFilter: function () {
            let matchingPoints = this.filteredPoints.filter(x => x.id == this.value);
            if (!matchingPoints.length) {
                this.$emit('input', '');
            }
        }
    }
}
