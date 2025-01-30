/**
 * Отзывы к товару
 */
export default {
    props: {
        /**
         * ID# блока
         * @type {Object}
         */
        blockId: {
            type: Number,
            required: true,
        },
    },
    mounted() {
        this.getVotes();
    },    
    methods: {
        /**
         * Получает голоса
         */
        getVotes() {
            $.get(this.votesURL).then((result) => {
                $(document).trigger(
                    'raas.shop.goods-comments-votes-retrieved', 
                    result.votes
                );
            });
        },
        /**
         * Голосует за отзыв
         * @param  {Number} itemId ID# отзыва
         * @param  {Number} vote 1 - за, -1 - против
         */
        vote(itemId, vote) {
            $.post(this.votesURL, { id: itemId, vote }).then((result) => {
                if (!result.localError) {
                    $(document).trigger(
                        'raas.shop.goods-comments-votes-retrieved', 
                        result.votes
                    );
                }
            });
        },
    },
    computed: {
        /**
         * URL получения оценок
         * @return {String}
         */
        votesURL() {
            let url = window.location.pathname + window.location.search;
            if (/\?/gi.test(url)) {
                url += '&';
            } else {
                url += '?';
            }
            url += 'AJAX=' + this.blockId;
            return url;
        },
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self() { 
            return {
                blockId: this.blockId,
                getVotes: this.getVotes.bind(this),
                vote: this.vote.bind(this),
                votesURL: this.votesURL,
            };
        },
    },
};