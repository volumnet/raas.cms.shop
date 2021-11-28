/**
 * Компонент развернутого заказа
 * @requires MyOrdersItem
 */
export default {
    methods: {
        deleteItem: async function (item) {
            if (!this.canBeDeleted) {
                return;
            }
            try {
                let confirmText = this.translations.ARE_YOU_SURE_TO_DELETE_ORDER;
                if (await this.$root.confirm(confirmText)) {
                    let result = await $.getJSON(this.itemDeleteURL);
                    window.location.href = '/my-orders/';
                }
            } catch (e) {}
        },
    }
};