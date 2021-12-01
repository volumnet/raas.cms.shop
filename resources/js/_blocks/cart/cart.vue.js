/**
 * Компонент корзины
 */
export default {
    props: {
        /**
         * ID# блока
         * @type {Number}
         */
        blockId: {
            type: Number,
            required: true,
        },
        /**
         * Корзина
         * @type {Object}
         */
        cart: {
            type: Object,
            required: true,
        },
        /**
         * Данные формы
         * @type {Object}
         */
        form: {
            type: Object,
            required: true,
        },
        /**
         * Исходные POST-данные
         * @type {Object}
         */
        initialFormData: {
            type: Object,
            default: function () {
                return {};
            }
        }
    },
    data: function () {
        let translations = {
            ARE_YOU_SURE_TO_CLEAR_CART: 'Вы действительно хотите очистить корзину?',
            CART_IS_LOADING: 'Корзина загружается...',
            CHECKOUT_TITLE: 'Оформление заказа',
            YOUR_CART_IS_EMPTY: 'Ваша корзина пуста',
            QUICK_ORDER: 'Быстрый заказ',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            initialHeader: '', // Изначальный заголовок H1
            initialDocumentTitle: '', // Изначальный заголовок документы
            translations, // Переводы
            proceed: false, // Оформление заказа
            quickorder: false, // Быстрый заказ
            success: false, // Заказ оформлен
            oldFormData: Object.assign({}, this.initialFormData), // Старые данные формы
            formData: Object.assign({}, this.initialFormData), // Данные формы
            getAdditionalTimeout: null, // ID# таймаута получения доп. данных
            DEFAULT_ADDITIONAL_TIMEOUT: 3000, // Таймаут получения доп. данных по умолчанию, мс
            instantUpdateFields: [
                'city', 
                'delivery', 
                'payment', 
                'promo'
            ], // Поля, обновление которых требует немедленного получения доп. данных
            delayUpdateFields: ['post_code'], // Поля, обновление которых требует отложенного получения доп. данных
        };
    },
    mounted: function () {
        this.initialHeader = $('.body__title').text();
        this.initialDocumentTitle = document.title;
        $(window).on('scroll', () => {
            this.adjustRightPane();
        });
        $(window).on('raas.shop.cart-updated', () => {
            this.checkResultForAdditional();
        });
    },
    methods: {
        getAdditionalData: function () {
            let result = this.formData;
            return result;
        },
        /**
         * Получает дополнительные данные
         * @param {Number} delay Получить данные через столько миллисекунд
         */
        getAdditional: function (delay) {
            if (this.getAdditionalTimeout) {
                window.clearTimeout(this.getAdditionalTimeout);
            }
            if (delay) {
                this.getAdditionalTimeout = window.setTimeout(() => {
                    this.cart.update('action=refresh', this.getAdditionalData());
                }, delay);
            } else {
                this.cart.update('action=refresh', this.getAdditionalData());
            }
        },
        /**
         * Проверяет, нужно ли получить дополнительные данные по POST-у
         * и при необходимости получает их
         */
        checkResultForAdditional: function () {
            if (this.formData.promo && 
                !(this.cart.additional && this.cart.additional.discount) && 
                !this.cart.loading
            ) {
                window.setTimeout(this.getAdditional.bind(this), 10);
            }
        },
        /**
         * Переходит к оформлению заказа
         */
        doProcess: function () {
            this.proceed = true;
            $('.body__title').text(this.translations.CHECKOUT_TITLE);
            document.title = this.translations.CHECKOUT_TITLE;
            $.scrollTo(0);
        },
        /**
         * Переходит к оформлению быстрого заказа
         */
        doQuickOrder: function () {
            this.quickorder = true;
            this.formData.agree = 1;
        },
        /**
         * Переходит к оформлению заказа
         */
        goBack: function () {
            this.proceed = false;
            $('.body__title').text(this.initialHeader);
            document.title = this.initialDocumentTitle;
            $.scrollTo(0);
        },
        /**
         * Запрос на очистку корзины
         */
        requestClear: function () {
            return this.$root.requestCartClear(
                this.cart, 
                this.translations.ARE_YOU_SURE_TO_CLEAR_CART
            );
        },
        /**
         * Обновляет промо-код (дисконтную карту)
         * @param {String} discountCard Дисконтная карта
         */
        updateDiscountCard: function (discountCard) {
            this.formData.promo = discountCard || '';
        },
        /**
         * Выравнивает правую часть
         */
        adjustRightPane: function () {
            let margin = 0;
            let $pane = $(this.$refs.rightPane);
            let $float = $(this.$refs.rightPaneFloat);
            if (this.$root.windowWidth > this.$root.mediaTypes.lg) {
                let scroll = $(window).scrollTop();
                let paneHeight = $pane.height();
                let floatInnerHeight = $float.outerHeight();
                let paneTop = $pane.offset() && $pane.offset().top;
                let maxPadding = paneHeight - floatInnerHeight;
                if (scroll > paneTop) {
                    margin = Math.min(scroll - paneTop, maxPadding);
                }
                // console.log(scroll, paneHeight, paneTop, maxPadding, margin);
            }
            $float.css('margin-top', margin + 'px');
        },

    },
    watch: {
        formData: {
            handler: function () {
                for (let key of this.instantUpdateFields) {
                    if (this.formData[key] != this.oldFormData[key]) {
                        this.getAdditional();
                    }
                }
                for (let key of this.delayUpdateFields) {
                    if (this.formData[key] != this.oldFormData[key]) {
                        this.getAdditional(this.DEFAULT_ADDITIONAL_TIMEOUT);
                    }
                }
                this.oldFormData = JSON.parse(JSON.stringify(this.formData));
            },
            deep: true,
        },
    }
};