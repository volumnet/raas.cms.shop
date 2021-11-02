/**
 * Компонент формы корзины
 */
export default {
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
            default: function () {
                return {};
            },
        },
    },
    data: function () {
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
         * Набор атрибутов поля
         * @param {Object} field Данные поля
         * @return {Object}
         */
        fieldAttrs: function (field) {
            let result = { 
                type: field, 
                'class': {
                    'is-invalid': !!this.errors[field.urn]
                },
                title: (this.errors[field.urn] || ''),
            };
            if ([
                'checkbox', 
                'radio', 
                'htmlarea', 
                'material'
            ].indexOf(field.datatype) == -1) {
                result['class']['form-control'] = true
            }
            // console.log(result);
            return result;
        },
        /**
         * Устанавливает шаг формы
         * @param {Number}  step  Шаг формы
         * @param {Boolean} force Принудительно установить 
         *     (не проверять, что устанавливаемый шаг меньше текущего)
         */
        setStep: function (step, force = false) {
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
        asteriskHintHTML: function () {
            let result = this.translations.ASTERISK_MARKED_FIELDS_ARE_REQUIRED;
            result = result.replace(
                '*', 
                '<span class="feedback__asterisk">*</span>'
            );
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
        selectedReceivingMethod: function () {
            let result = ((
                this.additional && 
                this.additional.delivery && 
                this.additional.delivery.methods
            ) || []).filter((method) => {
                return method.id == this.formData.delivery;
            });
            return result[0] || null;
        },
        /**
         * Валидность вкладки способа получения
         * @return {Boolean}
         */
        deliveryTabValidity: function () {
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
         * Выбранный способ оплаты
         * @return {Object|null} <pre><code>{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     epay: Boolean Электронная оплата,
         *     price?: Number Комиссия,
         * }</code></pre>, либо null если не найден
         */
        selectedPaymentMethod: function () {
            let result = ((
                this.additional && 
                this.additional.payment && 
                this.additional.payment.methods
            ) || []).filter((method) => {
                return method.id == this.formData.payment;
            });
            return result[0] || null;
        },
        /**
         * Валидность вкладки контактных данных
         * @return {Boolean}
         */
        contactsTabValidity: function () {
            for (let key of ([
                'last_name', 
                'first_name', 
                'second_name', 
                'phone', 
                'email', 
                '_description_', 
                'agree'
            ])) {
                if (this.form.fields[key].required && 
                    !(this.formData[key] && this.formData[key].trim())
                ) {
                    return false;
                }
            }
            return true;
        },
    }
};
