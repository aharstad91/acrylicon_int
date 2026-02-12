/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './inc/**/*.php',
    './blocks/**/*.php',
    './assets/**/*.js',
    './assets/components/**/*.php',
  ],
  theme: {
    extend: {
      fontSize: {
        'xl':  ['1.25rem', '2.25rem'],
        '3xl': ['1.875rem', '2.75rem'],
        '7xl': ['5rem', '1.25'],
      },
      colors: {
        // Custom brand colors with acryl- prefix to avoid Tailwind conflicts
        'acryl-red': '#E2241C',
        'acryl-dark-blue': '#253761',
        'acryl-light-blue': '#D5EDF7',
        'acryl-beige': {
          'light': '#DEDCCD',
          'lighter': '#F2F1E8',
          'lightest': '#F9F9F5',
        },
        'acryl-black': '#2B3338',
        'acryl-gray': {
          '1': '#6E7272',
          '2': '#8D9191',
          '3': '#626262',
        },
      },
      fontFamily: {
        'sohne-buch': ['Soehne Buch', 'sans-serif'],
        'sohne-mono': ['Soehne Mono', 'monospace'],
      },
      maxWidth: {
        '420': '420px',
        'screen-3xl': '1640px',
        'max': '1920px',
      },
      screens: {
        'md': '640px',
        'lg': '960px',
      },
      width: {
        '108': '26rem',
      },
      gap: {
        '40': '10rem',
      },
      spacing: {
        '108': '26rem',
        '124': '31rem', // 496px - custom height for reference images
        '500': '500px',
        '600': '600px',
        '900': '900px',
      },
    },
  },
  plugins: [],
}
