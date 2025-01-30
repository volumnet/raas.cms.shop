/**
 * Отзыв к товару
 */
export default {
    props: {
        /**
         * ID# отзыва
         * @type {Object}
         */
        itemId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            /**
             * Количество голосов за
             * @type {Number}
             */
            pros: 0,
            /**
             * Количество голосов против
             * @type {Number}
             */
            cons: 0,
            /**
             * Голос текущего пользователя
             * @type {Number}
             */
            voted: 0,
            /**
             * Голоса получены
             * @type {Number}
             */
            votesRetrieved: false,
        };
    },
    mounted() {
        $(document).on(
            'raas.shop.goods-comments-votes-retrieved', 
            (event, data) => {
                if (data[this.itemId]) {
                    this.pros = data[this.itemId].pros;
                    this.cons = data[this.itemId].cons;
                    this.voted = (parseInt(data[this.itemId].voted) || 0);
                    this.votesRetrieved = true;
                }
            }
        );
    },    
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self() { 
            return {
                itemId: this.itemId,
                pros: this.pros,
                cons: this.cons,
                voted: this.voted,
                votesRetrieved: this.votesRetrieved,
            };
        },
    },
};