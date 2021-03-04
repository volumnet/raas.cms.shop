/**
 * Mixin приложения с интернет-магазином
 */
export default {
    data: function () {
        const CART_TYPE_ID = 1;
        const FAVORITES_TYPE_ID = 2;
        const COMPARE_TYPE_ID = 3;
        let translations = {
            CART_DELETE_CONFIRM: 'Вы действительно хотите удалить этот товар?',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            CART_TYPE_ID, // ID# типа корзины "Корзина"
            FAVORITES_TYPE_ID, // ID# типа корзины "Избранное"
            COMPARE_TYPE_ID, // ID# типа корзины "Сравнение"
            cart: null, // Объект корзины
            favorites: null, // Объект избранного
            compare: null, // Объект сравнения
            translations, // Переводы
        }
    },
    mounted: function () {
        window.setTimeout(() => {
            this.cart && this.cart.update();
            this.favorites && this.favorites.update();
            this.compare && this.compare.update();
        }, 0);
    },
    methods: {
        /**
         * Запрос на удаление
         * @param  {[type]} item [description]
         * @param  {[type]} cart [description]
         * @return {[type]}      [description]
         */
        requestItemDelete: function (item, cart) {
            this.confirm(this.translations.CART_DELETE_CONFIRM).then(() => {
                cart.set(item.id, 0, item.meta, item.price);
            });
        },
    },
    computed: {
        // Компонент "Товар добавлен в избранное"
        addedModal: function () {
            return this.$refs.addedModal;
        },
    },
};