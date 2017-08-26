var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()

    .addEntry('start', './assets/js/start.js')
    .addEntry('packages', './assets/js/packages.js')
    .addEntry('mirrors', './assets/js/mirrors.js')
    .addEntry('packagers', './assets/js/packagers.js')
    .addEntry('app', './assets/js/app.js')

    .addStyleEntry('archicon', './assets/images/archicon.svg')
    .addStyleEntry('archlogo', './assets/images/archlogo.svg')

    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
    .enablePostCssLoader()
    .autoProvidejQuery()
    .autoProvideVariables({
        'Popper': 'popper.js'
    })
;

module.exports = Encore.getWebpackConfig();
