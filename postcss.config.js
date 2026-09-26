// Te same wtyczki co w dawnym webpack.mix.js; autoprefixer Laravel Mix
// dokładał po cichu, Vite nie — dlatego jest tu jawnie.
module.exports = {
  plugins: [
    require('postcss-import'),
    require('postcss-nesting'),
    require('tailwindcss'),
    require('autoprefixer'),
  ],
}
