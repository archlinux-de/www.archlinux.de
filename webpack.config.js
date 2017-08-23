var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    .addEntry('start', './assets/js/start.js')
    .addEntry('packages', './assets/js/packages.js')

    .addStyleEntry('app', './assets/css/app.scss')
    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
;

module.exports = Encore.getWebpackConfig();
