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
    config.plugins.push(new CopyWebpackPlugin({
      patterns: [
        { from: 'src/assets/images/arch(icon|logo).svg', to: 'img/[name].[ext]' }
      ]
    }))

    config.plugins.push(new WorkboxPlugin.GenerateSW({
      cacheId: 'app',
      exclude: [/robots\.txt$/],
      cleanupOutdatedCaches: true,
      dontCacheBustURLsMatching: /\.[a-f0-9]+\./,
      navigateFallback: '/index.html',
      navigateFallbackAllowlist: [
        new RegExp('^/download$'),
        new RegExp('^/impressum$'),
        new RegExp('^/mirrors$'),
        new RegExp('^/news$'),
        new RegExp('^/news/[^/-]+-.+$'),
        new RegExp('^/packages/[^/]+/[^/]+/[^/]+$'),
        new RegExp('^/packages$'),
        new RegExp('^/privacy-policy$'),
        new RegExp('^/releases/[^/]+$'),
        new RegExp('^/releases$'),
        new RegExp('^/$')
      ],
      runtimeCaching: [
        {
          urlPattern: new RegExp('^https?://[^/]+/api/(news|packages|mirrors|releases)\\?limit=[0-9]+(&onlyAvailable=true|&offset=0)?$'),
          handler: 'StaleWhileRevalidate',
          options: { cacheName: 'api', expiration: { maxAgeSeconds: 12 * 60 * 60 } }
        }
      ]
    }))

    if (process.env.NODE_ENV === 'production') {
      config.plugins.push(new CompressionPlugin({ filename: '[path][base].gz', algorithm: 'gzip' }))
      config.plugins.push(new CompressionPlugin({ filename: '[path][base].br', algorithm: 'brotliCompress' }))
    }
  },
  chainWebpack: config => {
    config.resolve.alias.set('bootstrap-vue$', 'bootstrap-vue/src/index.js')
  },
  transpileDependencies: ['bootstrap-vue']
}
