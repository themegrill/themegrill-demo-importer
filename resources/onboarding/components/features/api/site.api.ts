import { queryOptions } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { Demo } from '../../../lib/types';

export async function getSiteData(args: { theme: string; id: string }) {
	try {
		const response = await apiFetch<{ success: boolean; message?: string; data?: Demo }>({
			path: `tg-demo-importer/v1/data?id=${args.id}&theme=${args.theme}`,
			method: 'GET',
		});
		return response;
	} catch (e) {
		console.error('Failed to fetch site data:', e);
	}
}

export const siteDataQueryOptions = (args: { theme: string; id: string }) =>
	queryOptions({
		queryKey: ['siteData', args],
		queryFn: (a) => getSiteData(args),
	});
