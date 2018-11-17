const Encore = require('@symfony/webpack-encore')
const CompressionPlugin = require('compression-webpack-plugin')

Encore
  .setOutputPath('public/build')
  .setPublicPath('/build')
  .cleanupOutputBeforeBuild()
  .addEntry('js/app', './assets/js/app.js')
  .addEntry('js/start', './assets/js/start.js')
  .addEntry('js/packages', './assets/js/packages.js')
  .addEntry('js/mirrors', './assets/js/mirrors.js')
  .addEntry('js/package', './assets/js/package.js')
  .addStyleEntry('css/app', './assets/css/app.scss')
  .copyFiles({
    from: 'assets/images',
    to: 'images/[path][name].[hash:8].[ext]'
  })
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
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
  Encore.addPlugin(new CompressionPlugin())
}

module.exports = Encore.getWebpackConfig()
