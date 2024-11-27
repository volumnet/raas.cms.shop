const TerserJSPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const webpack = require('webpack');
const glob = require('glob');
const path = require('path');

const entryMap = glob.sync('./public/src/*.js')
    .reduce(function(obj, el) {
        obj[path.parse(el).name] = './' + el;
        return obj
    }, {});

module.exports = {
    mode: 'production',
    entry: entryMap,
    resolve: {
        modules: ['node_modules'],
        alias: {
            kernel: path.resolve(__dirname, 'd://web/home/libs/raas.kernel/public/src'),
            app: path.resolve(__dirname, 'public/src/'),
            jquery: path.resolve(__dirname, 'node_modules/jquery/dist/jquery'),
            cms: path.resolve(__dirname, 'd:/web/home/libs/raas.cms/resources/js'),
            "./dependencyLibs/inputmask.dependencyLib": "./dependencyLibs/inputmask.dependencyLib.jquery"
        },
        extensions: [
            '.styl',
            '.js',
            '.vue',
        ]
    },
    output: {
        filename: '[name].js',
        path: __dirname+'/public'
    },
    externals: {
        knockout: 'knockout',
        jQuery: 'jquery',
        $: 'jquery',
        'window.jQuery': 'jquery',
    },
    optimization: {
        minimizer: [
            new TerserJSPlugin({ 
                terserOptions: { output: { comments: false, }}
            }), 
            new OptimizeCSSAssetsPlugin({
                cssProcessorPluginOptions: {
                    preset: [
                        'default', 
                        { discardComments: { removeAll: true }}
                    ],
                },
            }),
        ],
    },
    devtool: 'inline-source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                use: 'babel-loader',
                exclude: /node_modules/
            },
            {
                test: /\.styl$/,
                use: [
                    // 'vue-style-loader',
                    // 'style-loader',
                    { loader: MiniCssExtractPlugin.loader },
                    'css-loader',
                    'stylus-loader'
                ]
            },
            {
                test: /\.scss$/,
                use: [
                    { loader: MiniCssExtractPlugin.loader },
                    'css-loader',
                    {
                        loader: 'postcss-loader', // Run postcss actions
                        options: {
                            postcssOptions: {
                                plugins: [
                                    ['postcss-utilities', { centerMethod: 'flexbox' }], 
                                    'autoprefixer',
                                    'rucksack-css',
                                    'postcss-short',
                                    // 'postcss-preset-env',
                                    'postcss-combine-duplicated-selectors',
                                    // 'postcss-sort-media-queries',
                                    // 'css-mqpacker', // Убрали, т.к. плохо работает со слоями, а выигрыш размера меньше 1%
                                    'postcss-pseudo-elements-content',
                                ],
                            },
                        },
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            additionalData: "@import 'kernel/_shared/init.scss';\n",
                        },
                    },
                ]
            },
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    'css-loader',
                ]
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/,
                loader: 'file-loader',
                options: { 
                    outputPath: './img', 
                    name: '[name].[ext]', 
                }
            },
            {
                test: /(\.(woff|woff2|eot|ttf|otf))|(font.*\.svg)$/,
                loader: 'file-loader',
                options: { 
                    outputPath: './fonts', 
                    name: '[name].[ext]',
                }
            },
            {
                test: /\.json$/,
                loader: 'json-loader'
            }
        ],
    },
    plugins: [
        new webpack.ProvidePlugin({
            knockout: 'knockout',
            // $: 'jquery',
            // jQuery: 'jquery',
            // 'window.jQuery': 'jquery',
        }),
        new VueLoaderPlugin(),
        new FixStyleOnlyEntriesPlugin(),
        new MiniCssExtractPlugin({ filename: './[name].css' }),
    ]
}