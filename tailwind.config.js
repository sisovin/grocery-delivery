module.exports = {
  purge: [
    './app/Views/**/*.php',
    './public/**/*.html',
    './public/assets/js/**/*.js'
  ],
  darkMode: false,
  theme: {
    extend: {
      colors: {
        nourish: {
          50: '#f0fdf4',
          500: '#22c55e',
          700: '#15803d',
          900: '#14532d'
        }
      }
    }
  },
  variants: {
    extend: {}
  },
  plugins: []
};
