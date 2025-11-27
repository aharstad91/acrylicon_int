/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './blocks/**/*.php',
    './assets/**/*.js',
    './assets/components/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        'red': '#E2241C',
        'dark-blue': '#253761',
        'light-blue': '#D5EDF7',
        'neutral-1': '#DEDCCD',
        'neutral-2': '#F2F1E8',
        'neutral-3': '#F9F9F5',
        'black': '#2B3338',
        'gray-1': '#6E7272',
        'gray-2': '#8D9191',
        'gray-3': '#626262',
        'dark': '#2B3338',
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
      },
    },
  },
  plugins: [],
}
