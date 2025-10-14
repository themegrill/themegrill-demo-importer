import apiFetch from '@wordpress/api-fetch';
import { SiteAPIError, SiteData, SitesResponse } from './sites.types';

export const CONFIG = {
	BASE_URL: 'https://demos.dev.local',
	CACHE_TIME: {
		STALE: 5 * 60 * 1000,
		GC: 10 * 60 * 1000,
	},
} as const;

export const API_ENDPOINTS = {
	SITES: '/themegrill-starter-templates/v1/sites',
	SITE_DATA: (source: string, id: string) =>
		`/themegrill-starter-templates/v1/sites/${source}/${id}`,
} as const;

export const siteApiService = {
	getSites: async () => {
		try {
			return apiFetch<SitesResponse>({
				path: API_ENDPOINTS.SITES,
			});
		} catch (error) {
			if (error instanceof SiteAPIError) throw error;
			throw new SiteAPIError(
				`Network error: ${error instanceof Error ? error.message : 'Unknown error'}`,
			);
		}
	},
	getSite: async (source: string, id: string) => {
		try {
			let data = await apiFetch<SiteData>({
				path: API_ENDPOINTS.SITE_DATA(source, id),
				headers: {
					'Content-Type': 'Application/JSON',
				},
			});
			return data;
		} catch (error) {
			throw new SiteAPIError(
				`Failed to fetch site ${id}: ${error instanceof Error ? error.message : 'Unknown error'}`,
			);
		}
	},
};
