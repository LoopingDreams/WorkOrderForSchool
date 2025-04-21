module.exports = {
    content: [
        "./Registration.html",
        "./src/**/*.{html,js}"
    ],
    theme: {
        extend: {
            colors: {
                primary: '#0d6efd',
                secondary: '#6c757d',
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                }
            }
        }
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}