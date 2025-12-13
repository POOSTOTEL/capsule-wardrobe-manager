module.exports = {
    entry: {
        app: './public/js/app.js',
        charts: './public/js/charts.js'
    },
    output: {
        filename: '[name].bundle.js',
        path: __dirname + '/public/dist'
    }
};