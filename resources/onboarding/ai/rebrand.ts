// Turns the final (possibly edited) generation package into what the importer
// needs: a rebranded demo config for the existing import steps, and the
// payload for the AI apply step that patches the imported pages.
import { Demo } from '../lib/types';
import { BrandKit, GenerationPackage, isImageSlot } from './types';

type SlotLocation = { path: string; block: string; attr: string; inHtml?: boolean };

export type ImportPackage = {
	version: number;
	demoSlug: string;
	pages: {
		slug: string;
		demoPageSlug: string;
		slots: Record<string, SlotLocation>;
		groups?: Record<string, string[]>; // Removed group id -> its top-level block paths.
	}[];
	demoFonts: Record<string, 'heading' | 'body'>;
};

// Mock packages carry no import data; only live ones can be imported.
export const getImportPackage = (pkg: GenerationPackage): ImportPackage | null => {
	const ip = pkg.importPackage as Partial<ImportPackage>;
	return ip && typeof ip.demoSlug === 'string' && Array.isArray(ip.pages) && !!ip.demoFonts
		? (ip as ImportPackage)
		: null;
};

// ---- Colors and fonts (the color map comes from the backend's /api/color-map) ----

const normalizeHex = (value: string): string | null => {
	const v = value.trim().toLowerCase();
	const hex = v.match(/^#([0-9a-f]{3}|[0-9a-f]{6})([0-9a-f]{2})?$/);
	if (hex?.[1]) {
		return hex[1].length === 3 ? '#' + hex[1].split('').map((c) => c + c).join('') : '#' + hex[1];
	}
	const rgb = v.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
	return rgb ? '#' + [rgb[1], rgb[2], rgb[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('') : null;
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
		removeBlocks: string[]; // Top-level blocks of groups still removed.
	}[];
};

export const buildApplyPayload = (
	pkg: GenerationPackage,
	ip: ImportPackage,
	demo: Demo,
	colorMap: Record<string, string>,
): ApplyPayload => ({
	siteTitle: pkg.brand.siteTitle,
	tagline: pkg.brand.tagline,
	colorMap,
	fontMap: buildFontMap(ip, pkg.brand.fonts),
	pages: ip.pages.map((page) => {
		const generated = pkg.pages.find((p) => p.slug === page.slug);
		const values = new Map((generated?.sections ?? []).flatMap((s) => Object.entries(s.slots)));
		return {
			demoPageId: demo.pages.find((p) => p.slug === page.demoPageSlug)?.id ?? 0,
			demoPageSlug: page.demoPageSlug,
			removeBlocks: (generated?.removed ?? []).filter((r) => !r.restored).flatMap((r) => page.groups?.[r.groupId] ?? []),
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
