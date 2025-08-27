import { createFileRoute } from '@tanstack/react-router';
import Home from '../Home';

export const Route = createFileRoute('/')({
	component: RouteComponent,
	validateSearch: (search: Record<string, unknown>) => {
		return {
			search: ((search.search as string | undefined) ?? undefined)?.replaceAll(
				'?page=demo-importer-v2',
				'',
			),
			builder: ((search.builder as string | undefined) ?? undefined)?.replaceAll(
				'?page=demo-importer-v2',
				'',
			),
			category: ((search.category as string | undefined) ?? undefined)?.replaceAll(
				'?page=demo-importer-v2',
				'',
			),
		};
	},
});

function RouteComponent() {
	return <Home />;
}
