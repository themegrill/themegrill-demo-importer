// Turns the final (possibly edited) generation package into what the importer
// needs: a rebranded demo config for the existing import steps, and the
// payload for the AI apply step that patches the imported pages.
import { Demo } from '../lib/types';
import { BrandKit, BrandPalette, GenerationPackage, isImageSlot } from './types';

type SlotLocation = { path: string; block: string; attr: string; inHtml?: boolean };

export type ImportPackage = {
	version: number;
	demoSlug: string;
	pages: { slug: string; demoPageSlug: string; slots: Record<string, SlotLocation> }[];
	demoColors: { hex: string; role: 'primary' | 'secondary' | 'accent' }[];
	demoFonts: Record<string, 'heading' | 'body'>;
};

// Mock packages carry no import data; only live ones can be imported.
export const getImportPackage = (pkg: GenerationPackage): ImportPackage | null => {
	const ip = pkg.importPackage as Partial<ImportPackage>;
	return ip && typeof ip.demoSlug === 'string' && Array.isArray(ip.pages) && Array.isArray(ip.demoColors)
		? (ip as ImportPackage)
		: null;
};

// ---- Colors (same mapping as the backend: palette hue, demo lightness) ----

const toHsl = (hex: string) => {
	const n = parseInt(hex.slice(1, 7), 16);
	const [r, g, b] = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((v) => v / 255) as [number, number, number];
	const max = Math.max(r, g, b);
	const min = Math.min(r, g, b);
	const l = (max + min) / 2;
	const d = max - min;
	if (!d) return { h: 0, s: 0, l };
	const s = d / (1 - Math.abs(2 * l - 1));
	const h = ((max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4) * 60 + 360) % 360;
	return { h, s, l };
};

const toHex = ({ h, s, l }: { h: number; s: number; l: number }) => {
	const c = (1 - Math.abs(2 * l - 1)) * s;
	const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
	const m = l - c / 2;
	const [r, g, b] =
		h < 60 ? [c, x, 0] : h < 120 ? [x, c, 0] : h < 180 ? [0, c, x] : h < 240 ? [0, x, c] : h < 300 ? [x, 0, c] : [c, 0, x];
	return '#' + [r, g, b].map((v) => Math.round((v + m) * 255).toString(16).padStart(2, '0')).join('');
};

const normalizeHex = (value: string): string | null => {
	const v = value.trim().toLowerCase();
	const hex = v.match(/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{2})?$/);
	if (hex?.[1]) {
		return hex[1].length === 3 ? '#' + hex[1].split('').map((c) => c + c).join('') : '#' + hex[1];
	}
	const rgb = v.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
	return rgb ? '#' + [rgb[1], rgb[2], rgb[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('') : null;
};

export const buildColorMap = (ip: ImportPackage, palette: BrandPalette): Record<string, string> => {
	const map: Record<string, string> = {};
	for (const { hex, role } of ip.demoColors) {
		const { h, s } = toHsl(palette[role] ?? palette.primary);
		map[hex] = toHex({ h, s, l: toHsl(hex).l });
	}
	return map;
};

export const buildFontMap = (ip: ImportPackage, fonts: BrandKit['fonts']): Record<string, string> =>
	Object.fromEntries(Object.entries(ip.demoFonts).map(([family, role]) => [family, fonts[role]]));

// Recolor and refont theme settings (header, links, buttons, palette).
const transformValue = (value: unknown, key: string, colors: Record<string, string>, fonts: Record<string, string>): unknown => {
	if (Array.isArray(value)) return value.map((v) => transformValue(v, key, colors, fonts));
	if (value && typeof value === 'object') {
		return Object.fromEntries(
			Object.entries(value as Record<string, unknown>).map(([k, v]) => [k, transformValue(v, k, colors, fonts)]),
		);
	}
	if (typeof value !== 'string') return value;
	if ((key === 'font-family' || key === 'family') && fonts[value]) return fonts[value];
	const hex = normalizeHex(value);
	return hex && colors[hex] ? colors[hex] : value;
};

export const rebrandDemoConfig = (
	demo: Demo,
	colors: Record<string, string>,
	fonts: Record<string, string>,
	brand: BrandKit,
): Demo => {
	const themeMods = transformValue(demo.themeMods, '', colors, fonts) as Record<string, unknown>;
	const heading = themeMods.zakra_heading_typography as Record<string, unknown> | undefined;
	const body = themeMods.zakra_body_typography as Record<string, unknown> | undefined;

	return {
		...demo,
		themeMods: {
			...themeMods,
			// Theme-level fonts follow the brand even where the demo used defaults.
			...(heading ? { zakra_heading_typography: { ...heading, 'font-family': brand.fonts.heading } } : {}),
			...(body ? { zakra_body_typography: { ...body, 'font-family': brand.fonts.body } } : {}),
			// The demo's logo carries the demo's name; show the site title instead.
			custom_logo: '',
		},
		...('blockart_blocks_settings' in demo
			? { blockart_blocks_settings: transformValue((demo as any).blockart_blocks_settings, '', colors, fonts) }
			: {}),
	} as Demo;
};

// ---- Apply payload ----------------------------------------------------------

export type ApplyPayload = {
	siteTitle: string;
	tagline: string;
	colorMap: Record<string, string>;
	fontMap: Record<string, string>;
	pages: {
		demoPageId: number;
		demoPageSlug: string;
		slots: { path: string; attr: string; inHtml: boolean; text?: string; image?: string }[];
	}[];
};

export const buildApplyPayload = (pkg: GenerationPackage, ip: ImportPackage, demo: Demo): ApplyPayload => ({
	siteTitle: pkg.brand.siteTitle,
	tagline: pkg.brand.tagline,
	colorMap: buildColorMap(ip, pkg.brand.palette),
	fontMap: buildFontMap(ip, pkg.brand.fonts),
	pages: ip.pages.map((page) => {
		const values = new Map(
			(pkg.pages.find((p) => p.slug === page.slug)?.sections ?? []).flatMap((s) => Object.entries(s.slots)),
		);
		return {
			demoPageId: demo.pages.find((p) => p.slug === page.demoPageSlug)?.id ?? 0,
			demoPageSlug: page.demoPageSlug,
			slots: Object.entries(page.slots).flatMap(([id, location]) => {
				const value = values.get(id);
				if (value === undefined) return [];
				return [
					{
						path: location.path,
						attr: location.attr,
						inHtml: !!location.inHtml,
						...(isImageSlot(value) ? { image: value.url } : { text: value }),
					},
				];
			}),
		};
	}),
});
