import CartAdditionalMixin from './cart-additional-mixin.vue.js';

/**
 * Компонент формы корзины
 * @requires AJAXForm
 * @requires AJAXFormStandalone
 */
export default {
    mixins: [CartAdditionalMixin],
    props: {
        /**
         * Данные формы
         * @type {Object}
         */
        form: {
            type: Object,
            required: true,
        },
        /**
         * Дополнительные данные корзины
         * @type {Object}
         */
        additional: {
            type: Object,
            default() {
                return {};
            },
        },
    },
    data() {
       let translations = {
            ASTERISK_MARKED_FIELDS_ARE_REQUIRED: 'Поля, помеченные звездочкой (*), обязательны для заполнения',
            ORDER_SUCCESSFULLY_SENT: 'Спасибо! Ваш заказ #%s успешно отправлен. В ближайшее время наш менеджер свяжется с Вами.',
            RECEIVING_METHOD: 'Способ получения',
            SELECT_YOUR_CITY: 'Выберите ваш город',
            SEND: 'Отправить',
            LOADING: 'Загрузка...',
            DELIVERY_PRICE: 'Стоимость доставки',
            FOR_FREE: 'Бесплатно',
            CANNOT_GET_DELIVERY_PRICE_HINT: 'Не удалось получить стоимость доставки. Заказ будет рассчитан без учета доставки.',
            PAYMENT_METHOD: 'Способ оплаты',
            CONTACT_DATA: 'Контактные данные',
            QUICK_ORDER: 'Быстрый заказ',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
            step: 0, // Шаг формы
            orderId: null, // ID# заказа
        };
    },
    methods: {
        /**
         * Устанавливает шаг формы
         * @param {Number}  step  Шаг формы
         * @param {Boolean} force Принудительно установить 
         *     (не проверять, что устанавливаемый шаг меньше текущего)
         */
        setStep(step, force = false) {
            if (force || (step < this.step)) {
                this.step = step;
            }
        },
    },
    computed: {
        /**
         * HTML-код подсказки об обязательных полях
         * @return {String}
         */
        asteriskHintHTML() {
            let result = this.translations.ASTERISK_MARKED_FIELDS_ARE_REQUIRED;
            result = result.replace(
                '*', 
                '<span class="feedback__asterisk">*</span>'
            );
            return result;
        },
        /**
         * Валидность вкладки способа получения
         * @return {Boolean}
         */
        deliveryTabValidity() {
            if (!this.formData.city || !this.formData.region) {
                return false;
            }
            if (!this.selectedReceivingMethod || 
                !this.selectedReceivingMethod.id
            ) {
                return false;
            } else {
                if (this.selectedReceivingMethod.isDelivery) {
                    for (let key of (['street', 'house', 'apartment'])) {
                        if (this.form.fields[key].required && 
                            !(this.formData[key] && this.formData[key].trim())
                        ) {
                            return false;
                        }
                    }
                    if (this.selectedReceivingMethod.serviceURN == 'russianpost' &&
                        !(
                            this.formData.post_code && 
                            this.formData.post_code.trim()
                        )
                    ) {
                        return false;
                    }
                } else {
                    if (!this.formData.pickup_point || 
                        !this.formData.pickup_point_id
                    ) {
                        return false;
                    }
                }
            }
            return true;
        },
        /**
         * Валидность вкладки контактных данных
         * @return {Boolean}
         */
        contactsTabValidity() {
            for (let key of ([
                'last_name', 
                'first_name', 
                'second_name', 
                'phone', 
                'email', 
                '_description_', 
                'agree'
            ])) {
                if (this.form.fields[key] && 
                    this.form.fields[key].required && 
                    !(this.formData[key] && this.formData[key].trim())
                ) {
                    return false;
                }
            }
            return true;
        },
    }
};
