import { queryOptions } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { Demo, PageWithSelection, TDIDashboardType } from '../../../lib/types';

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

	return await response.json();
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
