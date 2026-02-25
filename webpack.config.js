/*

IMPORTANT - to make this work, run: npm install copy-webpack-plugin --save-dev

*/
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins,
        new CopyWebpackPlugin({
            patterns: [
                {
                    from: 'src/rpdyt-mainstage-controller.js',
                    to: 'rpdyt-mainstage-controller.js'
                }
            ]
        })
    ]
};
