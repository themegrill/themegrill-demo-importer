const plugin = require("tailwindcss/plugin");

/** @type {import('tailwindcss').Config} */
module.exports = {
	darkMode: ["class"],
	content: ["./resources/**/*.{ts,tsx}"],
	theme: {
		fontFamily: {
			sans: ["Segoe UI"],
			serif: ["Segoe UI"],
			body: ["Segoe UI"],
		},
		extend: {
			colors: {
				border: "hsl(var(--border))",
				input: "hsl(var(--input))",
				ring: "hsl(var(--ring))",
				background: "hsl(var(--background))",
				foreground: "hsl(var(--foreground))",
				primary: {
					DEFAULT: "hsl(var(--primary))",
					foreground: "hsl(var(--primary-foreground))",
				},
				secondary: {
					DEFAULT: "hsl(var(--secondary))",
					foreground: "hsl(var(--secondary-foreground))",
				},
				destructive: {
					DEFAULT: "hsl(var(--destructive))",
					foreground: "hsl(var(--destructive-foreground))",
				},
				muted: {
					DEFAULT: "hsl(var(--muted))",
					foreground: "hsl(var(--muted-foreground))",
				},
				accent: {
					DEFAULT: "hsl(var(--accent))",
					foreground: "hsl(var(--accent-foreground))",
				},
				popover: {
					DEFAULT: "hsl(var(--popover))",
					foreground: "hsl(var(--popover-foreground))",
				},
				card: {
					DEFAULT: "hsl(var(--card))",
					foreground: "hsl(var(--card-foreground))",
				},
				sidebar: {
					DEFAULT: "hsl(var(--sidebar-background))",
					foreground: "hsl(var(--sidebar-foreground))",
					primary: "hsl(var(--sidebar-primary))",
					"primary-foreground":
						"hsl(var(--sidebar-primary-foreground))",
					accent: "hsl(var(--sidebar-accent))",
					"accent-foreground":
						"hsl(var(--sidebar-accent-foreground))",
					border: "hsl(var(--sidebar-border))",
					ring: "hsl(var(--sidebar-ring))",
				},
			},
			borderRadius: {
				lg: "var(--radius)",
				md: "calc(var(--radius) - 2px)",
				sm: "calc(var(--radius) - 4px)",
			},
			keyframes: {
				"accordion-down": {
					from: {
						height: "0",
					},
					to: {
						height: "var(--radix-accordion-content-height)",
					},
				},
				"accordion-up": {
					from: {
						height: "var(--radix-accordion-content-height)",
					},
					to: {
						height: "0",
					},
				},
				"loader-jump": {
					"15%": {
						"border-bottom-right-radius": "3px",
					},
					"25%": {
						transform: "translateY(9px) rotate(22.5deg)",
					},
					"50%": {
						transform:
							"translateY(18px) scale(1, 0.9) rotate(45deg)",
						"border-bottom-right-radius": "40px",
					},
					"75%": {
						transform: "translateY(9px) rotate(67.5deg)",
					},
					"100%": {
						transform: "translateY(0) rotate(90deg)",
					},
				},
				"loader-shadow": {
					"0%, 100%": {
						transform: "scale(1, 1)",
					},
					"50%": {
						transform: "scale(1.2, 1)",
					},
				},
			},
			animation: {
				"accordion-down": "accordion-down 0.2s ease-out",
				"accordion-up": "accordion-up 0.2s ease-out",
				"loader-jump": "loader-jump 0.8s ease-in-out infinite",
				"loader-shadow": "loader-shadow 0.8s ease-in-out infinite",
			},
			screens: {
				laptop: "1024px",
				desktop: "1440px",
				ultrawide: "1920px",
			},
		},
	},
	plugins: [
		require("tailwindcss-animate"),
		require("tailwind-scrollbar"),
		function ({ matchVariant }) {
			matchVariant("has", (value) => {
				return `&:has(${value})`;
			});
		},
		plugin(function ({ addUtilities }) {
			addUtilities({});
		}),
	],
};
