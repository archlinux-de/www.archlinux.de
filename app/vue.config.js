const CompressionPlugin = require('compression-webpack-plugin')
const CopyWebpackPlugin = require('copy-webpack-plugin')
const WorkboxPlugin = require('workbox-webpack-plugin')

module.exports = {
  lintOnSave: false,
  productionSourceMap: false,
  devServer: {
    disableHostCheck: true
  },
  configureWebpack: config => {
    if (!process.env.VUE_CLI_MODERN_BUILD) {
      config.entry.app.unshift('whatwg-fetch', 'abortcontroller-polyfill')
    }

    config.plugins.push(new CopyWebpackPlugin({
      patterns: [
        { from: 'src/assets/images/arch(icon|logo).svg', to: 'img/[name].[ext]' }
      ]
    }))

    if ((process.env.VUE_CLI_MODERN_MODE && process.env.VUE_CLI_MODERN_BUILD) || !process.env.VUE_CLI_MODERN_MODE) {
      config.plugins.push(new WorkboxPlugin.GenerateSW({
        cacheId: 'app',
        exclude: [/robots\.txt$/],
        cleanupOutdatedCaches: true,
        dontCacheBustURLsMatching: /\.[a-f0-9]+\./,
        navigateFallback: '/index.html',
        navigateFallbackDenylist: [new RegExp('^https?://[^/]+/api/')],
        runtimeCaching: [
          {
            urlPattern: new RegExp('^https?://[^/]+/api/(news|packages|mirrors|releases)\\?limit=[0-9]+(&offset=0)?$'),
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'api' }
          }
        ]
      }))
    }

    if (process.env.NODE_ENV === 'production') {
      config.plugins.push(new CompressionPlugin({ filename: '[path].gz[query]', algorithm: 'gzip' }))
      config.plugins.push(new CompressionPlugin({ filename: '[path].br[query]', algorithm: 'brotliCompress' }))
    }
  },
  chainWebpack: config => {
    config.resolve.alias.set('bootstrap-vue$', 'bootstrap-vue/src/index.js')
  },
  transpileDependencies: ['bootstrap-vue']
}
