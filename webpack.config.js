module.exports = {
    entry: {
        app: './public/js/app.js'
    },
    output: {
        filename: '[name].bundle.js',
        path: __dirname + '/public/dist'
    }
};