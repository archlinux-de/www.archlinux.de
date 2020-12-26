const path = require('path')
const webpack = require('webpack')
const VueLoaderPlugin = require('vue-loader/lib/plugin')
const CopyPlugin = require('copy-webpack-plugin')
const HtmlPlugin = require('html-webpack-plugin')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const CompressionPlugin = require('compression-webpack-plugin')
const WorkboxPlugin = require('workbox-webpack-plugin')
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin')

const createConfig = isDevelopment => {
  const styleLoader = isDevelopment ? 'style-loader' : MiniCssExtractPlugin.loader

  const config = {
    entry: { app: ['./src/main.js'] },
    output: { path: path.resolve(__dirname, 'dist'), publicPath: '/', filename: 'js/[name].[contenthash].js' },

    resolve: {
      extensions: ['.js', '.vue'],
      alias: {
        vue$: 'vue/dist/vue.runtime.esm.js',
        'bootstrap-vue$': 'bootstrap-vue/src/index.js'
      }
    },

    module: {
      rules: [
        { test: /\.vue$/, loader: 'vue-loader' },
        { test: /\.js$/, loader: 'babel-loader', exclude: /node_modules\/(?!bootstrap-vue\/src\/)/ },
        { test: /\.s?css$/, use: [styleLoader, 'css-loader', 'postcss-loader', 'sass-loader'] },
        { test: /\.svg$/, use: { loader: 'file-loader', options: { name: 'img/[name].[contenthash].[ext]' } } }
      ]
    },

    plugins: [
      new VueLoaderPlugin(),
      new CopyPlugin({
        patterns: [
          { from: 'public', globOptions: { ignore: ['**/index.html'] } },
          { from: 'src/assets/images/arch(icon|logo).svg', to: 'img/[name].[ext]' }
        ]
      }),
      new HtmlPlugin({
        template: 'public/index.html',
        title: process.env.npm_package_name
      })
    ]
  }

  if (isDevelopment) {
    // Workaround for https://github.com/webpack/webpack-dev-server/issues/2758
    config.target = 'web'
    config.devtool = 'source-map'
    config.watchOptions = {
      ignored: 'node_modules'
    }
    config.devServer = {
      historyApiFallback: { disableDotRule: true }
    }
  } else {
    config.optimization = {
      splitChunks: { chunks: 'all' },
      minimizer: [
        new TerserPlugin({ terserOptions: { format: { comments: false } }, extractComments: false }),
        new CssMinimizerPlugin({ minimizerOptions: { preset: ['default', { discardComments: { removeAll: true } }] } })
      ]
    }

    config.plugins.push(new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css',
      chunkFilename: 'css/[name].[contenthash].css'
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

    config.plugins.push(new CompressionPlugin({ filename: '[path][base].gz', algorithm: 'gzip' }))
    config.plugins.push(new CompressionPlugin({ filename: '[path][base].br', algorithm: 'brotliCompress' }))
  }

  return config
}

module.exports = (env, argv) => createConfig(typeof argv.mode !== 'undefined' && argv.mode === 'development')
