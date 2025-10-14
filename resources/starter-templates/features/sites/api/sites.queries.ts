import { queryOptions } from '@tanstack/react-query';
import { CONFIG, siteApiService } from './sites.api';

export const queryKeys = {
	sites: () => ['sites'] as const,
	site: (id: string) => ['site', id] as const,
} as const;

export const sitesQueryOptions = () =>
	queryOptions({
		queryKey: queryKeys.sites(),
		queryFn: siteApiService.getSites,
		staleTime: CONFIG.CACHE_TIME.STALE,
		gcTime: CONFIG.CACHE_TIME.GC,
		retry: 3,
		retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
	});

export const siteQueryOptions = (source: string, id: string) =>
	queryOptions({
		queryKey: queryKeys.site(id),
		queryFn: () => siteApiService.getSite(source, id),
		enabled: Boolean(id?.trim()),
		staleTime: CONFIG.CACHE_TIME.STALE,
		gcTime: CONFIG.CACHE_TIME.GC,
		retry: 3,
		retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
	});
