/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './app.vue',
    './plugins/**/*.{js,ts}'
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2563EB',
          hover: '#1D4ED8',
          light: '#DBEAFE',
          subtle: '#EFF6FF'
        },
        'brand-red': {
          DEFAULT: '#2563EB',
          dark: '#1D4ED8',
          light: '#DBEAFE',
          50: '#EFF6FF'
        },
        'brand-blue': {
          DEFAULT: '#2563EB',
          dark: '#1D4ED8',
          light: '#DBEAFE',
          50: '#EFF6FF'
        },
        presence: {
          hadir: '#16A34A',
          'hadir-bg': '#DCFCE7',
          sakit: '#EA580C',
          'sakit-bg': '#FFEDD5',
          izin: '#2563EB',
          'izin-bg': '#DBEAFE',
          alpha: '#DC2626',
          'alpha-bg': '#FEE2E2'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: []
}
