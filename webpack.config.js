const Encore = require('@symfony/webpack-encore')
const CompressionPlugin = require('compression-webpack-plugin')
const BrotliPlugin = require('brotli-webpack-plugin')

Encore
  .setOutputPath('public/build/')
  .setPublicPath('/build')
  .cleanupOutputBeforeBuild()
  .addEntry('js/start', './assets/js/start.js')
  .addEntry('js/packages', './assets/js/packages.js')
  .addEntry('js/mirrors', './assets/js/mirrors.js')
  .addEntry('js/package', './assets/js/package.js')
  .createSharedEntry('js/vendor', [
    'jquery',
    'popper.js',
    'bootstrap',
    'datatables.net',
    'datatables.net-bs4',
    '!./assets/js/lang-loader!datatables.net-plugins/i18n/German.lang'
  ])
  .addStyleEntry('css/app', './assets/css/app.scss')
  .addStyleEntry('images/archicon', './assets/images/archicon.svg')
  .addStyleEntry('images/archlogo', './assets/images/archlogo.svg')
  .enableSassLoader()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enablePostCssLoader()
  .addLoader({
    test: /\.lang$/,
    loader: './assets/js/lang-loader.js'
  })
  .autoProvidejQuery()
  .autoProvideVariables({
    'Popper': 'popper.js'
  })

if (Encore.isProduction()) {
  Encore.addPlugin(new BrotliPlugin())
  Encore.addPlugin(new CompressionPlugin())
}

module.exports = Encore.getWebpackConfig()
