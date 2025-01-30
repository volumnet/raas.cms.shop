/**
 * Компонент блока доставки
 */
export default {
    props: {
        /**
         * Источник данных
         * @type {Object}
         */
        source: {
            type: Object,
            required: true,
        },
        /**
         * Данные формы
         * @type {Object}
         */
        formData: {
            type: Object,
            default() {
                return {};
            },
        },
        /**
         * Начальный выбранный тип получения
         * @type {String} <pre><code>'delivery'|'pickup'</code></pre>
         */
        initialReceivingTypeUrn: {
            type: String,
            required: false,
        }
    },
    emits: ['update:modelValue'],
    data() {
        let translations = {
            PICKUP: 'Самовывоз',
            DELIVERY: 'Доставка',
            SELECT_DELIVERY_TYPE: 'Выберите тип доставки',
            FILL_DELIVERY_ADDRESS: 'Заполните адрес доставки',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            receivingTypeURN: this.initialReceivingTypeUrn || '', // Тип получения
            translations, // Переводы
        };
    },
    mounted() {
        if (!this.receivingTypeURN) {
            for (let key of ['pickup', 'delivery']) {
                if (this.receivingTypes.filter(x => x.urn == key).length) {
                    this.receivingTypeURN = key;
                    break;
                }
            }
        }
    },
    methods: {
        /**
         * Получает данные для обновления формы самовывоза
         * @param {Number} pickupPointId ID# точки самовывоза
         * @return {Object}
         */
        getPickupData(pickupPointId) {
            let result = { 
                pickup_point_id: '', 
                pickup_point: '', 
                delivery: '', 
                post_code: '',
                street: '',
                house: '',
                building: '',
                apartment: '',
            };
            let matchingPoints = this.source.points
                .filter(x => x.id == pickupPointId);
            if (matchingPoints.length) {
                result.pickup_point_id = matchingPoints[0].id;
                result.pickup_point = (
                    matchingPoints[0].serviceURN ? 
                    ('#' + matchingPoints[0].id + ' ') : 
                    ''
                ) + matchingPoints[0].address;
                let matchingMethods = this.pickupMethods
                    .filter(x => x.serviceURN == matchingPoints[0].serviceURN);
                if (matchingMethods.length) {
                    result.delivery = matchingMethods[0].id;
                }
            }
            return result;
        },
    },
    computed: {
        /**
         * Способы самовывоза
         * @return {Array} <pre><code>array<{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     fullName: String Полное наименование,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *     dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         * }></code></pre>
         */
        pickupMethods() {
            let result = (this.source.methods || []).filter((method) => {
                if (method.isDelivery) {
                    return false;
                }
                let points = (this.source.points || [])
                    .filter(point => point.serviceURN == method.serviceURN);
                if (!points.length) {
                    return false;
                }
                return true;
            });
            return result;
        },
        /**
         * Способы доставки
         * @return {Array} <pre><code>array<{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     fullName: String Полное наименование,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *     dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         * }></code></pre>
         */
        deliveryMethods() {
            let result = (this.source.methods || []).filter((method) => {
                if (!method.isDelivery) {
                    return false;
                }
                return true;
            });
            return result;
        },
        /**
         * Типы получения
         * @return {Array} <pre><code>array<{
         *     urn: String URN способа получения,
         *     name: String Наименование способа получения,
         *     source: array<{
         *         id: Number ID# способа получения,
         *         name: String Краткое наименование,
         *         fullName: String Полное наименование,
         *         serviceURN: String URN сервиса,
         *         price?: Number Стоимость доставки,
         *         dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *         dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         *     }> Способы получения
         * }></code></pre>
         */
        receivingTypes() {
            let result = [];
            if (this.pickupMethods.length) {
                result.push({
                    urn: 'pickup',
                    name: this.translations.PICKUP,
                    source: this.pickupMethods,
                });
            }
            if (this.deliveryMethods.length) {
                result.push({
                    urn: 'delivery',
                    name: this.translations.DELIVERY,
                    source: this.deliveryMethods,
                });
            }
            return result;
        },
    },
    watch: {
    },
}