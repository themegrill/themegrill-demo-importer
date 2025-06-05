import React, { useMemo } from 'react';
import { useDemoContext } from '../../context';
import { TabsContent } from '../../controls/Tabs';
import { PagebuilderCategory } from '../../lib/types';
import CategoryMenu from './CategoryMenu';
import Demos from './Demos';

type Props = {
	categories: PagebuilderCategory[];
	searchParams: URLSearchParams;
	initialTheme: string;
};

const Content = ({ categories, searchParams, initialTheme }: Props) => {
	// const location = useLocation();
	// const searchParams = new URLSearchParams(location.search);
	// const currentHeaderTab = searchParams.get('tab') || '';
	const { theme, pagebuilder, category, plan, searchResults, setCategory } = useDemoContext();
	const demos = useMemo(() => {
		let search = searchParams.get('search');
		return searchResults
			.filter((d) => ('all' !== theme ? d.theme == theme : true))
			.filter((d) =>
				'all' !== pagebuilder ? Object.keys(d.pagebuilders).some((p) => p === pagebuilder) : true,
			)
			.filter((d) =>
				'all' !== category ? Object.keys(d.categories).some((p) => p === category) : true,
			)
			.filter((d) =>
				'all' !== plan ? (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium) : true,
			)
			.filter((d) => (search ? d.name.toLowerCase().indexOf(search.toLowerCase()) !== -1 : true));
	}, [theme, category, pagebuilder, searchParams]);

	return (
		<TabsContent value={theme} className="mt-0">
			{categories && (
				<>
					<CategoryMenu categories={categories} />
					<Demos demos={demos} />
				</>
			)}
		</TabsContent>
	);
};

export default Content;
