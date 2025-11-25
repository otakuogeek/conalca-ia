/** @type {import('tailwindcss').Config} */
export default {
    content: ["./resources/**/*.blade.php", "./resources/**/*.{js,jsx,ts,tsx}"],
    theme: {
        extend: {
            colors: {
                orange: {
                    25: '#fef7f0',
                    50: '#fff7ed',
                    75: '#fed4ab',
                    100: '#ffedd5',
                    200: '#fed7aa',
                    250: '#fdba74',
                    300: '#fb923c',
                    400: '#f97316',
                    500: '#ea580c',
                    600: '#dc2626',
                    700: '#c2410c',
                    800: '#9a3412',
                    900: '#7c2d12',
                },
            },
            boxShadow: {
                custom1: `12px 0px 5px 1px rgba(0,0,0,0.11);`,
                custom2: "-4px 4px 8px 0px rgba(0, 0, 0, 0.07);",
            },
        },
    },
    plugins: [require("daisyui"), require("tailwind-scrollbar-hide")],
    daisyui: {
        themes: ["light"],
    },
};
