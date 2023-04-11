let mix = require('laravel-mix');

require('./mix');

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'dist/js/nova-icon-field.js')
  .vue({ version: 3 })
  .nova('tecnobit-srl/nova-icon-field');
