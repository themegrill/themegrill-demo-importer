import { createFileRoute } from '@tanstack/react-router';
import Home from '../Home';

export const Route = createFileRoute('/')({
	component: RouteComponent,
	validateSearch: (search: Record<string, unknown>) => {
		return {
			search: ((search.search as string | undefined) ?? undefined)?.replaceAll(
				'?page=tg-starter-templates',
				'',
			),
			builder: ((search.builder as string | undefined) ?? undefined)?.replaceAll(
				'?page=tg-starter-templates',
				'',
			),
			category: ((search.category as string | undefined) ?? undefined)?.replaceAll(
				'?page=tg-starter-templates',
				'',
			),
		};
	},
});

function RouteComponent() {
	return <Home />;
}
