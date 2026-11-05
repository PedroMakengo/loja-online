/** @type {import('tailwindcss').Config} */
// Compilar:  npx tailwindcss -i ./src/app.css -o ./assets/css/app.css --minify
// Desenvolvimento: acrescentar --watch
const tom = (v) => `rgb(var(--${v}) / <alpha-value>)`;

module.exports = {
  content: ['./app/Views/**/*.php', './assets/js/**/*.js', './core/helpers.php'],
  darkMode: 'class',
  theme: {
    container: { center: true, padding: { DEFAULT: '1rem', lg: '1.5rem' }, screens: { '2xl': '1360px' } },
    extend: {
      colors: {
        fundo: tom('fundo'),
        superficie: tom('superficie'),
        elevado: tom('elevado'),
        linha: tom('linha'),
        tinta: tom('tinta'),
        suave: tom('suave'),
        marca: { DEFAULT: tom('marca'), forte: tom('marca-forte'), suave: tom('marca-suave') },
        coral: { DEFAULT: tom('coral'), suave: tom('coral-suave') },
        sucesso: tom('sucesso'),
        aviso: tom('aviso'),
        grafite: '#141A2E',
      },
      fontFamily: {
        display: ['"Bricolage Grotesque"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        sans: ['Figtree', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      borderRadius: { xl: '0.875rem', '2xl': '1.25rem' },
      boxShadow: {
        flutuante: '0 18px 40px -18px rgb(20 26 46 / 0.35)',
        cartao: '0 1px 0 rgb(var(--linha) / 1)',
      },
      keyframes: {
        entrar: { from: { opacity: '0', transform: 'translateY(6px)' }, to: { opacity: '1', transform: 'none' } },
        pulsar: { '0%,100%': { transform: 'scale(1)' }, '50%': { transform: 'scale(1.25)' } },
      },
      animation: { entrar: 'entrar .25s ease-out', pulsar: 'pulsar .4s ease-out' },
    },
  },
  plugins: [],
};
