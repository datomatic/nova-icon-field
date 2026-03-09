let mix = require('laravel-mix');
let config = require('./webpack.config');

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'js/nova-icon-field.js')
  .vue({ version: 3 })
  .webpackConfig(config);
