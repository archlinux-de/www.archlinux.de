var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    .addEntry('start', './assets/js/start.js')
    .addEntry('packages', './assets/js/packages.js')

    .addStyleEntry('app', './assets/css/app.scss')
    .addStyleEntry('rss', './assets/images/rss.png')
    .addStyleEntry('favicon', './assets/images/favicon.ico')
    .addStyleEntry('archlogo-64', './assets/images/archlogo-64.png')
    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
;

module.exports = Encore.getWebpackConfig();
