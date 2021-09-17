/* eslint-disable prefer-regex-literals */
const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const CompressionPlugin = require('compression-webpack-plugin')
const WorkboxPlugin = require('workbox-webpack-plugin')
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin')

module.exports = {
  mode: 'production',
  output: { path: path.resolve(__dirname, 'dist'), filename: 'js/[name].[contenthash].js' },

  resolve: {
    alias: {
      vue$: 'vue/dist/vue.runtime.esm.js',
      'bootstrap-vue$': 'bootstrap-vue/src/index.js'
    }
  },

  module: {
    rules: [
      { test: /\.js$/, loader: 'babel-loader', exclude: /node_modules\/(?!bootstrap-vue\/src\/)/ },
      { test: /\.s?css$/, use: [MiniCssExtractPlugin.loader, 'css-loader', 'postcss-loader', 'sass-loader'] }
    ]
  },

  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css',
      chunkFilename: 'css/[name].[contenthash].css'
    }),
    new WorkboxPlugin.GenerateSW({
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
        new RegExp('^/news/[0-9]+(-.+)?$'),
        new RegExp('^/packages/[^/]+/[^/]+/[^/]+$'),
        new RegExp('^/packages$'),
        new RegExp('^/privacy-policy$'),
        new RegExp('^/releases/[0-9][^/]+$'),
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
    }),
    new CompressionPlugin({ filename: '[path][base].gz', algorithm: 'gzip' }),
    new CompressionPlugin({ filename: '[path][base].br', algorithm: 'brotliCompress' })
  ],

  optimization: {
    splitChunks: { chunks: 'all' },
    minimizer: [
      new TerserPlugin({ terserOptions: { format: { comments: false } }, extractComments: false }),
      new CssMinimizerPlugin({ minimizerOptions: { preset: ['default', { discardComments: { removeAll: true } }] } })
    ]
  }
}
