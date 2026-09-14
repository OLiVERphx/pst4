module.exports = {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
    "./resources/css/**/*.css",
  ],
  theme: {
    extend: {
      colors: {
        'accent-blue': '#1D4ED8',
        'accent-teal': '#0D9488',
        'accent-green': '#059669',
        'accent-orange': '#EA580C',
        'bg-dark': '#0B0E17',
        'surface-dark': '#131720',
        'border-dark': '#2A3047',
        'bg-light': '#F1F5F9',
        'surface-light': '#FFFFFF',
        'border-light': '#E2E8F0',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'Arial'],
      },
    },
  },
  plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
  darkMode: 'class',
};
