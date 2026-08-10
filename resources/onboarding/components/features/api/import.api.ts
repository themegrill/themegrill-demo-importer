import { queryOptions } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Demo, PageWithSelection, TDIDashboardType } from '../../../lib/types';

/**
 * Map raw fetch/JSON/API failures to a short message the failure dialog can show.
 * Keeps developer-level parse errors (e.g. HTML 200 responses) out of the UI.
 */
export function getFriendlyImportErrorMessage(error: unknown): string {
	const fallback = __(
		'The demo import failed because the server returned an unexpected response. Some content may have been partially imported.',
		'themegrill-demo-importer',
	);

	if (!(error instanceof Error) || !error.message) {
		return fallback;
	}

	const message = error.message;

	// Browser JSON.parse / Response.json() when the body is HTML or empty.
	if (
		error instanceof SyntaxError ||
		/is not valid JSON/i.test(message) ||
		/Unexpected token/i.test(message) ||
		/JSON\.parse/i.test(message)
	) {
		return fallback;
	}

	// Network / aborted requests from apiFetch or fetch().
	if (
		/Failed to fetch/i.test(message) ||
		/NetworkError/i.test(message) ||
		/Load failed/i.test(message) ||
		/network request failed/i.test(message)
	) {
		return __(
			'The demo import failed because the connection to the server was interrupted. Some content may have been partially imported.',
			'themegrill-demo-importer',
		);
	}

	// HTTP error already shaped by importDemo — strip HTML noise if present.
	const httpMatch = message.match(/^Request failed \((\d{3})[^)]*\):\s*([\s\S]*)$/);
	if (httpMatch) {
		const status = httpMatch[1] ?? '';
		const body = (httpMatch[2] ?? '').trim();
		if (!body || body.startsWith('<') || /<!DOCTYPE/i.test(body)) {
			return __(
				'The demo import failed because the server returned an error. Some content may have been partially imported.',
				'themegrill-demo-importer',
			);
		}

		// Prefer a REST API JSON error message when the body is JSON.
		try {
			const parsed = JSON.parse(body) as { message?: string };
			if (parsed?.message && typeof parsed.message === 'string') {
				return parsed.message;
			}
		} catch {
			// Not JSON — fall through with a status-based message.
		}

		return (
			__(
				'The demo import failed because the server returned an error.',
				'themegrill-demo-importer',
			) + ` (${status})`
		);
	}

	// Plugin install and other intentional Error() messages are already readable.
	if (message.length <= 280 && !message.startsWith('<')) {
		return message;
	}

	return fallback;
}

async function readResponseJson<T = unknown>(response: Response): Promise<T> {
	const text = await response.text().catch(() => '');

	if (!text) {
		throw new SyntaxError('Empty response body');
	}

	try {
		return JSON.parse(text) as T;
	} catch {
		// Re-throw as SyntaxError so getFriendlyImportErrorMessage can map it.
		throw new SyntaxError(
			text.trimStart().startsWith('<')
				? 'Unexpected token \'<\', "<!DOCTYPE "... is not valid JSON'
				: 'Response is not valid JSON',
		);
	}
}

export async function importDemo(args: {
	action: string;
	demo: Demo;
	selectedPlugins: string[];
	siteLogoId: number;
	selectedPages: PageWithSelection[];
	isPagesSelected: boolean;
	colorPalette: string[];
	typography: string[];
}) {
	const response = await apiFetch<Response>({
		path: 'tg-demo-importer/v1/install?action=' + args.action,
		method: 'POST',
		data: {
			demo_config: args.demo,
			opts: {
				plugins: args.selectedPlugins,
				customLogo: args.siteLogoId,
				pages: args.isPagesSelected ? args.selectedPages : [],
				colorPalette: args.colorPalette,
				typography: args.typography,
			},
		},
		parse: false,
	});

	// `parse: false` means `apiFetch` resolves even on a 4xx/5xx — fetch() only
	// rejects on network-level failures, not HTTP error statuses. Check this
	// before attempting to parse the body: a PHP fatal error (e.g. a 500) isn't
	// valid JSON, so response.json() would throw a generic SyntaxError that
	// hides the actual status/reason. Throwing here (rather than swallowing and
	// returning undefined, as this used to do) also avoids a confusing
	// "data is undefined" TanStack Query error masking the real failure —
	// react-query's queryFn contract requires never resolving to undefined.
	if (!response.ok) {
		const text = await response.text().catch(() => '');
		throw new Error(`Request failed (${response.status} ${response.statusText}): ${text.slice(0, 300)}`);
	}

	return await readResponseJson<any>(response);
}

export const importDataQueryOptions = (args: {
	action: string;
	demo: Demo;
	selectedPlugins: string[];
	siteLogoId: number;
	selectedPages: PageWithSelection[];
	isPagesSelected: boolean;
	colorPalette: string[];
	typography: string[];
}) =>
	queryOptions({
		queryKey: ['importDemo', args],
		queryFn: (a) => importDemo(args),
	});

export async function activatePro(args: { id: string }) {
	const response = await apiFetch<{
		success: boolean;
		message: string;
	}>({
		path: 'tg-demo-importer/v1/activate-pro',
		method: 'POST',
		data: {
			id: args.id,
		},
	});
	return response;
}

export const activateProQueryOptions = (args: { id: string }) =>
	queryOptions({
		queryKey: ['activatePro', args],
		queryFn: (a) => activatePro(args),
	});

export async function cleanup() {
	const response = await apiFetch<{
		success: boolean;
		message: string;
	}>({
		path: 'tg-demo-importer/v1/cleanup',
		method: 'POST',
	});
	return response;
}

export const cleanupQueryOptions = () =>
	queryOptions({
		queryKey: ['cleanup'],
		queryFn: () => cleanup(),
	});

export async function localizedData(args: { refetch?: boolean }) {
	const refetchParam = args?.refetch ? '?refetch=true' : '';

	const response = await apiFetch<TDIDashboardType>({
		path: `/tg-demo-importer/v1/localized-data${refetchParam}`,
		method: 'GET',
	});
	return response;
}

export const localizedDataQueryOptions = (args: { refetch?: boolean }) =>
	queryOptions({
		queryKey: ['localizedData', args],
		queryFn: () => localizedData(args),
	});

export async function saveTrackingConsent(args: { allowContribution: boolean }) {
	const response = await apiFetch<{ success: boolean }>({
		path: 'tg-demo-importer/v1/tracking-consent',
		method: 'POST',
		data: {
			allow_tracking: args.allowContribution,
		},
	});
	return response;
}
