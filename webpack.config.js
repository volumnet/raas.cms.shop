const path = require('path');

const config = require('d:/web/home/libs/raas.kernel/webpack.config.inc.js');

config.entry = {
    module: path.resolve('./public/src/module.js'),
};
config.output.publicPath = '/vendor/volumnet/raas.cms.shop/public/';

module.exports = config;