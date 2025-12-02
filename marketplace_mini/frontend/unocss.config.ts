import {
  defineConfig,
  presetUno,
  presetAttributify,
  presetTypography,
} from 'unocss'

export default defineConfig({
  presets: [
    presetUno(),
    presetAttributify(),
    presetTypography(),
  ],
  theme: {
    colors: {
      primary: {
        50: '#f5f3ff',
        100: '#ede9fe',
        200: '#ddd6fe',
        300: '#c4b5fd',
        400: '#a78bfa',
        500: '#8b5cf6',
        600: '#7c3aed',
        700: '#6d28d9',
        800: '#5b21b6',
        900: '#4c1d95',
        DEFAULT: '#7c3aed', // violet-600
      },
      secondary: {
        50: '#f0fdfa',
        100: '#ccfbf1',
        200: '#99f6e4',
        300: '#5eead4',
        400: '#2dd4bf',
        500: '#14b8a6',
        600: '#0d9488',
        700: '#0f766e',
        800: '#115e59',
        900: '#134e4a',
        DEFAULT: '#14b8a6', // teal-500
      },
      accent: {
        50: '#fffbeb',
        100: '#fef3c7',
        200: '#fde68a',
        300: '#fcd34d',
        400: '#fbbf24',
        500: '#f59e0b',
        600: '#d97706',
        700: '#b45309',
        800: '#92400e',
        900: '#78350f',
        DEFAULT: '#f59e0b', // amber-500
      },
    },
    fontFamily: {
      sans: ['Inter', 'sans-serif'],
      display: ['Outfit', 'sans-serif'],
    },
  },
  shortcuts: {
    'btn': 'px-4 py-2 rounded-xl font-medium transition-all duration-300 flex items-center justify-center gap-2 shadow-sm hover:shadow-md active:scale-95',
    'btn-primary': 'bg-gradient-to-r from-primary-600 to-primary-500 text-white hover:from-primary-700 hover:to-primary-600 shadow-primary/25',
    'btn-secondary': 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 hover:border-gray-300',
    'btn-outline': 'border-2 border-primary-500 text-primary-600 hover:bg-primary-50',
    'card': 'bg-white/90 backdrop-blur-xl rounded-2xl shadow-xl border border-white/50',
    'input': 'w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all duration-300',
    'glass': 'bg-white/80 backdrop-blur-md border border-white/20 shadow-xl',
    'text-gradient': 'bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-secondary-600',
  }
})
