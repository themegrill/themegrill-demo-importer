import { QueryCache, QueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

export const queryCache = new QueryCache({
	onError(error) {
		toast.error(error?.message || 'Unknown error');
	},
});

export const queryClient = new QueryClient({
	defaultOptions: {
		queries: {
			refetchOnWindowFocus: false,
			retry: false,
		},
		mutations: {
			onError(error) {
				toast.error(error?.message || 'Unknown error');
			},
		},
	},
	queryCache: queryCache,
});
