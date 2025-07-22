import React, { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PagebuilderCategory, SearchResultType } from '../../lib/types';
import CategoryMenu from './CategoryMenu';
import Demos from './Demos';

type Props = {
	categories: PagebuilderCategory[];
	allDemos: SearchResultType[];
};

const Content = ({ categories, allDemos }: Props) => {
	// const { plan, search, searchResults } = useDemoContext();
	const [searchParams] = useSearchParams();
	const theme = searchParams.get('tab') || 'all';
	const pagebuilder = searchParams.get('pagebuilder') || 'all';
	const category = searchParams.get('category') || 'all';
	const plan = searchParams.get('plan') || 'all';
	const search = searchParams.get('search') || '';
	const demos = useMemo(() => {
		return allDemos
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
	}, [theme, category, pagebuilder, plan, search, allDemos]);

	return (
		<div className="mt-0">
			{categories && (
				<>
					<CategoryMenu categories={categories} />
					<Demos demos={demos} />
				</>
			)}
		</div>

		// <TabsContent value={theme} className="mt-0">
		// 	{categories && (
		// 		<>
		// 			<CategoryMenu categories={categories} searchParams={searchParams} />
		// 			<Demos demos={demos} />
		// 		</>
		// 	)}
		// </TabsContent>
	);
};

export default Content;
