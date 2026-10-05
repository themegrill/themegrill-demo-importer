import { __ } from '@wordpress/i18n';
import { AiPageSlug, AiTone, GenerationStep } from './types';

export const TONES: { value: AiTone; label: string }[] = [
	{ value: 'friendly', label: __('Friendly', 'themegrill-demo-importer') },
	{ value: 'professional', label: __('Professional', 'themegrill-demo-importer') },
	{ value: 'bold', label: __('Bold', 'themegrill-demo-importer') },
	{ value: 'elegant', label: __('Elegant', 'themegrill-demo-importer') },
	{ value: 'playful', label: __('Playful', 'themegrill-demo-importer') },
];

export const NICHES: { value: string; label: string }[] = [
	{ value: 'auto', label: __('Auto-detect', 'themegrill-demo-importer') },
	{ value: 'food', label: __('Food, Bakery & Restaurant', 'themegrill-demo-importer') },
	{ value: 'agency', label: __('Agency & Business', 'themegrill-demo-importer') },
	{ value: 'fitness', label: __('Fitness & Health', 'themegrill-demo-importer') },
	{ value: 'portfolio', label: __('Portfolio & Creative', 'themegrill-demo-importer') },
	{ value: 'blog', label: __('Blog & Magazine', 'themegrill-demo-importer') },
	{ value: 'ecommerce', label: __('Online Store', 'themegrill-demo-importer') },
	{ value: 'education', label: __('Education', 'themegrill-demo-importer') },
];

export const LANGUAGES: { value: string; label: string }[] = [
	{ value: 'en', label: 'English' },
	{ value: 'es', label: 'Español' },
	{ value: 'fr', label: 'Français' },
	{ value: 'de', label: 'Deutsch' },
	{ value: 'it', label: 'Italiano' },
	{ value: 'pt', label: 'Português' },
	{ value: 'nl', label: 'Nederlands' },
	{ value: 'hi', label: 'हिन्दी' },
	{ value: 'ne', label: 'नेपाली' },
	{ value: 'ja', label: '日本語' },
	{ value: 'zh', label: '中文' },
	{ value: 'ar', label: 'العربية' },
];

export const PAGES: { value: AiPageSlug; label: string; required?: boolean }[] = [
	{ value: 'home', label: __('Home', 'themegrill-demo-importer'), required: true },
	{ value: 'about', label: __('About', 'themegrill-demo-importer') },
	{ value: 'services', label: __('Services', 'themegrill-demo-importer') },
	{ value: 'contact', label: __('Contact', 'themegrill-demo-importer') },
	{ value: 'blog', label: __('Blog', 'themegrill-demo-importer') },
];

export const GENERATION_STEPS: { value: GenerationStep; label: string }[] = [
	{ value: 'understanding_brand', label: __('Understanding your brand', 'themegrill-demo-importer') },
	{ value: 'picking_design', label: __('Picking a design', 'themegrill-demo-importer') },
	{ value: 'writing_copy', label: __('Writing content', 'themegrill-demo-importer') },
	{ value: 'finding_images', label: __('Finding images', 'themegrill-demo-importer') },
	{ value: 'assembling', label: __('Assembling site', 'themegrill-demo-importer') },
];

export const BRAND_NAME_MAX = 60;
export const DESCRIPTION_MIN = 20;
export const DESCRIPTION_MAX = 500;

/**
 * Two-letter code of the WordPress admin language (`<html lang="en-US">`),
 * falling back to English.
 */
export const getSiteLanguage = (): string => {
	const lang = document.documentElement.lang || 'en';
	return lang.split('-')[0]?.toLowerCase() || 'en';
};

// Google Fonts offered for the brand kit (includes every font the backend picks).
export const FONT_CHOICES = [
	'Baloo 2',
	'Cormorant Garamond',
	'DM Sans',
	'DM Serif Display',
	'Fredoka',
	'Inter',
	'Lato',
	'Lora',
	'Manrope',
	'Merriweather',
	'Montserrat',
	'Nunito',
	'Nunito Sans',
	'Open Sans',
	'Oswald',
	'Playfair Display',
	'Poppins',
	'Raleway',
	'Roboto',
	'Source Sans 3',
];
