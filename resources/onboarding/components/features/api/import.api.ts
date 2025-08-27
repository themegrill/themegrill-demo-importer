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
}) {
	try {
		const response = await apiFetch<Response>({
			path: 'tg-demo-importer/v1/install?action=' + args.action,
			method: 'POST',
			data: {
				demo_config: args.demo,
				opts: {
					plugins: args.selectedPlugins,
					blogname: '',
					blogdescription: '',
					custom_logo: args.siteLogoId,
					pages: args.isPagesSelected ? args.selectedPages : [],
				},
			},
			parse: false,
		});
		const data = await response.json();
		return data;
	} catch (e) {
		console.error('Failed to import data:', e);
	}
}

export const importDataQueryOptions = (args: {
	action: string;
	demo: Demo;
	selectedPlugins: string[];
	siteLogoId: number;
	selectedPages: PageWithSelection[];
	isPagesSelected: boolean;
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
