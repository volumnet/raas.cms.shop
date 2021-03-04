export default function (cartType, no_amount)
{
    var _items = {};
    var _cartType = 0;

    var _load = this.load = function()
    {
        _items = {};
        var temp = Cookie.get('cart_' + _cartType);
        var id, metas, meta, c;
        if (temp) {
            temp = JSON.parse(temp)
            if (typeof(temp) == 'object') {
                for (id in temp) {
                    metas = temp[id];
                    if (typeof(metas) == 'object') {
                        for (meta in metas) {
                            c = parseInt(metas[meta]) || 0;
                            id = parseInt(id) || 0;
                            if (id && c) {
                                id = id.toString();
                                if (!_items[id]) {
                                    _items[id] = {};
                                }
                                _items[id][meta] = c;
                            }
                        }
                    }
                }
            }
        }
    };


    var _save = function()
    {
        Cookie.set(
            'cart_' + _cartType, 
            JSON.stringify(_items), 
            { expires: 14, path: '/' }
        );
    };

    
    this.cartType = function() {
        return _cartType;
    };

    
    this.rawItems = function() {
        return _items;
    };


    this.items = function() {
        var temp = [];
        var metas, item_id, meta, c, m, row;
        for (item_id in _items) {
            metas = _items[item_id];
            for (meta in metas) {
                c = parseInt(metas[meta]);
                if (c) {
                    row = { 'id': parseInt(item_id), 'meta': meta, 'amount': c };
                    temp.push(row);
                }
            }
        }
        return temp;
    };


    this.setCart = function(items)
    {
        _items = {};
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var id = item.id;
            var meta = item.meta;
            var amount = parseInt(item.amount) || 0;
            if (id && amount) {
                if (!_items[id]) {
                    _items[id] = {};
                }
                if (!_items[id][meta]) {
                    _items[id][meta] = 0;
                }
                _items[id][meta] += amount;
            }
        }
        _save();
    };

    
    this.set = function(item_id, amount, meta)
    {
        item_id = isNaN(parseInt(item_id)) ? 0 : item_id.toString();
        meta = meta || '';
        amount = Math.max(0, parseInt(amount));
        if (no_amount) {
            amount = Math.min(1, amount);
        }
        
        if (amount > 0) {
            if (item_id) {
                if (!_items[item_id]) {
                    _items[item_id] = {};
                }
                _items[item_id][meta] = amount;
            }
        } else {
            if (_items[item_id]) {
                delete _items[item_id][meta];
            }
        }
        _save();
    };


    this.count = function(item_id, meta)
    {
        item_id = isNaN(parseInt(item_id)) ? 0 : item_id.toString();
        meta = meta || '';
        
        if (item_id > 0) {
            if (_items[item_id] && _items[item_id][meta]) {
                return parseInt(_items[item_id][meta]);
            }
        } else {
            var sum = 0;
            for (item_id in _items) {
                var metas = _items[item_id];
                for (meta in metas) {
                    var c = parseInt(metas[meta]) || 0;
                    sum += c;
                }
            }
            return sum;
        }
        return 0;
    };


    this.add = function(item_id, amount, meta)
    {
        item_id = isNaN(parseInt(item_id)) ? 0 : item_id.toString();
        meta = meta || '';
        amount = parseInt(amount) || 0;
        if (amount <= 0) {
            amount = 1;
        }
        
        this.set(item_id, this.count(item_id, meta) + amount, meta);
    };


    this.reduce = function(item_id, amount, meta)
    {
        item_id = isNaN(parseInt(item_id)) ? 0 : item_id.toString();
        meta = meta || '';
        amount = parseInt(amount) || 0;
        if (amount <= 0) {
            amount = 1;
        }
        
        this.set(item_id, this.count(Item, meta) - amount, meta);
    };


    this.clear = function()
    {
        _items = {};
        _save();
    };


    {
        _cartType = cartType;
        _load();
    }
}
