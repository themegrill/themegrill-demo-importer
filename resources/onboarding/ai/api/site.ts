// Calls to this site's REST API for the AI import: demo config, theme setup,
// the existing import steps, and the AI apply step. Like the AI client, these
// go to the page's own origin so alternate hosts/ports keep the login cookie.
import { __ } from '@wordpress/i18n';
import { getFriendlyImportErrorMessage } from '../../components/features/api/import.api';
import { Demo } from '../../lib/types';
import { getAiConfig } from '../config';
import { ApplyPayload } from '../rebrand';
import { BrandPalette } from '../types';

// restUrl is ".../tg-demo-importer/v1/ai/"; the plugin namespace is one level up.
const routeUrl = (route: string, query: Record<string, string> = {}) => {
	const base = getAiConfig().restUrl.replace(/ai\/$/, '');
	const url = base + route;
	const params = new URLSearchParams(query).toString();
	return params ? url + (url.includes('?') ? '&' : '?') + params : url;
};

const call = async <T>(method: 'GET' | 'POST', route: string, body?: unknown, query?: Record<string, string>): Promise<T> => {
	const response = await fetch(routeUrl(route, query), {
		method,
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': getAiConfig().nonce },
		...(body === undefined ? {} : { body: JSON.stringify(body) }),
	});
	const text = await response.text();
	let json: any;
	try {
		json = JSON.parse(text);
	} catch {
		throw new Error(`Request failed (${response.status} ${response.statusText}): ${text.slice(0, 300)}`);
	}
	if (!response.ok) {
		throw new Error(json?.message || json?.error?.message || `Request failed (${response.status} ${response.statusText})`);
	}
	return json as T;
};

export const friendlyError = (error: unknown) => getFriendlyImportErrorMessage(error);

export const getDemoConfig = async (slug: string): Promise<Demo> => {
	const response = await call<{ success: boolean; message?: string; data?: Demo }>('GET', 'data', undefined, {
		id: slug,
		theme: 'zakra',
	});
	if (!response?.data?.slug) {
		throw new Error(response?.message || __('This design could not be loaded.', 'themegrill-demo-importer'));
	}
	return response.data;
};

export const prepareTheme = () => call<{ theme: string; installed: boolean }>('POST', 'ai/prepare-theme', {});

export type ImportAction =
	| 'install-plugins'
	| 'import-content'
	| 'import-content-posts'
	| 'import-media'
	| 'import-customizer'
	| 'import-widgets'
	| 'complete';

export const runImportAction = (action: ImportAction, demo: Demo, plugins: string[]) =>
	call<any>(
		'POST',
		'install',
		{
			demo_config: demo,
			opts: { plugins, customLogo: 0, pages: [], colorPalette: [], typography: [] },
		},
		{ action },
	);

export const applySite = (payload: ApplyPayload) => call<{ updated: number }>('POST', 'ai/apply', payload);

// Two maps: page blocks, and theme settings (contrast is checked for each).
export const getColorMaps = (demoSlug: string, palette: BrandPalette) =>
	call<{ colorMap: Record<string, string>; themeColorMap?: Record<string, string> }>('POST', 'ai/color-map', {
		demoSlug,
		palette,
	}).then((r) => ({ blocks: r.colorMap, theme: r.themeColorMap ?? r.colorMap }));
