
let AjaxCart = function (id, cookieCart, updateUrl, withPrice) {
    this.id = id;
    this.cookieCart = cookieCart;
    this.updateUrl = updateUrl;
    this.withPrice = !!withPrice;
    if (this.withPrice && !this.cookieCart.sum) {            
        this.cookieCart.sum = 0;
    }
};


AjaxCart.prototype.update = function () {
    var self = this;
    var localData = {};
    localData.count = self.cookieCart.count();
    localData.items = self.cookieCart.items();
    if (self.withPrice) {
        localData.sum = self.cookieCart.sum;
    }
    $(document).trigger(
        'raas.shop.cart-updated', 
        [{id: self.id, data: localData}]
    );
    if (this.updateUrl) {
        return $.getJSON(this.updateUrl, function (remoteData) {
            if (self.withPrice && remoteData.sum) {
                self.cookieCart.sum = parseFloat(remoteData.sum) || 0;
            }
            $(document).trigger(
                'raas.shop.cart-updated', 
                [{id: self.id, remote: true, data: remoteData}]
            );
        });
    }
};


AjaxCart.prototype.setCart = function (items) {
    var self = this;
    var sum = 0;
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var amount = parseInt(item.amount) || 0;
        var price = parseFloat(item.price) || 0;
        if (amount && price) {
            sum += amount * price;
        }
        
    }
    self.cookieCart.setCart(items);
    self.cookieCart.sum = sum;
    self.update();
};


AjaxCart.prototype.addToCart = function (items) {
    var self = this;
    var sum = 0;
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        var amount = parseInt(item.amount) || 0;
        var price = parseFloat(item.price) || 0;
        if (amount && price) {
            sum += amount * price;
            self.cookieCart.add(item.id, amount, item.meta);
        }
        
    }
    self.cookieCart.sum += sum;
    self.update();
};


AjaxCart.prototype.set = function (id, amount, meta, price) {
    amount = parseInt(amount) || 0;
    price = parseFloat(price) || 0;
    var self = this;
    var oldAmount = self.cookieCart.count(id, meta);
    if (self.withPrice && price) {
        self.cookieCart.sum += (amount - oldAmount) * price;
    }
    self.cookieCart.set(id, amount, meta);
    self.update();
};


AjaxCart.prototype.add = function (id, amount, meta, price) {
    amount = parseInt(amount) || 0;
    price = parseFloat(price) || 0;
    var self = this;
    if (self.withPrice && price) {
        self.cookieCart.sum += amount * price;
    }
    self.cookieCart.add(id, amount, meta);
    self.update();
};


AjaxCart.prototype.clear = function () {
    var self = this;
    if (self.withPrice) {
        self.cookieCart.sum = 0;
    }
    self.cookieCart.clear();
    self.update();
};

export default AjaxCart;