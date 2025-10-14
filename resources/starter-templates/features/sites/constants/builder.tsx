import elementor from "@/starter-templates/assets/images/elementor.jpg";
import gutenberg from "@/starter-templates/assets/images/gutenberg.jpg";
import brizy from "@/starter-templates/assets/images/brizy.jpg";
import { __ } from "@wordpress/i18n";

export const BUILDERS = {
	gutenberg: {
		title: __("Gutenberg", "themegrill-demo-importer"),
		Icon: (
			props: Omit<
				React.ImgHTMLAttributes<HTMLImageElement>,
				"src" | "alt"
			>
		) => <img {...props} src={gutenberg} alt="" />,
	},
	elementor: {
		title: __("Elementor", "themegrill-demo-importer"),
		Icon: (
			props: Omit<
				React.ImgHTMLAttributes<HTMLImageElement>,
				"src" | "alt"
			>
		) => <img {...props} src={elementor} alt="" />,
	},
	brizy: {
		title: __("Brizy", "themegrill-demo-importer"),
		Icon: (
			props: Omit<
				React.ImgHTMLAttributes<HTMLImageElement>,
				"src" | "alt"
			>
		) => <img {...props} src={brizy} alt="" />,
	},
};

export const DEVICE_BREAKPOINTS = {
	desktop: {
		min: 1025,
	},
	tablet: {
		min: 768,
		max: 1024,
	},
	mobile: {
		max: 767,
	},
};
