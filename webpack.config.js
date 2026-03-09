const path = require('path');
const webpack = require('webpack');

module.exports = {
  resolve: {
    extensions: ['.js', '.json', '.vue'],
    alias: {
      'laravel-nova': path.join(
        __dirname,
        '../../vendor/laravel/nova/resources/js/mixins/packages.js'
      ),
    },
  },
  externals: {
    vue: 'Vue',
  },
  output: {
    uniqueName: 'datomatic/nova-icon-field',
  },
  plugins: [
    new webpack.ProvidePlugin({
      _: 'lodash',
      Errors: 'form-backend-validation',
    }),
  ],
};
