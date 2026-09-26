const path = require('path')
const webpack = require('webpack')

// https://stefanbauer.me/tips-and-tricks/autocompletion-for-webpack-path-aliases-in-phpstorm-when-using-laravel-mix
module.exports = {
  output: { chunkFilename: 'js/[name].js?id=[chunkhash]' },
  resolve: {
    alias: {
      '@': path.resolve('./resources/js'),
    },
    extensions: ['.js', '.vue', '.json'],
  },
  // Flaga kompilacji z Vue 3.4+, której Laravel Mix 6 nie ustawia (ustawia tylko
  // __VUE_OPTIONS_API__ i __VUE_PROD_DEVTOOLS__). Bez niej w paczce zostaje
  // diagnostyka niezgodności hydracji, a Vue ostrzega w konsoli.
  plugins: [
    new webpack.DefinePlugin({ __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false' }),
  ],
  devServer: {
    allowedHosts: 'all',
  },
}
