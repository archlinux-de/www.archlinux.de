const Encore = require('@symfony/webpack-encore')
const CompressionPlugin = require('compression-webpack-plugin')
const path = require('path')

Encore
  .setOutputPath((process.env.PUBLIC_PATH || 'public') + '/build')
  .setPublicPath('/build')
  .addAliases({ '@': path.resolve(__dirname, 'assets') })
  .addEntry('base', './assets/js/base.js')
  .addEntry('start', './assets/js/start.js')
  .addEntry('packages', './assets/js/packages.js')
  .addEntry('mirrors', './assets/js/mirrors.js')
  .addEntry('package', './assets/js/package.js')
  .addEntry('news', './assets/js/news.js')
  .addEntry('releases', './assets/js/releases.js')
  .copyFiles({ from: 'assets/images', to: 'images/[path][name].[hash:8].[ext]' })
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .enableSassLoader()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enablePostCssLoader()
  .addLoader({ test: /\.lang$/, loader: './assets/js/_lang-loader.js' })
  .configureBabel(() => {}, { useBuiltIns: 'usage', corejs: 3 })

if (Encore.isProduction()) {
  Encore.addPlugin(new CompressionPlugin())
} else {
  Encore.cleanupOutputBeforeBuild()
}

module.exports = Encore.getWebpackConfig()
