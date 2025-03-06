import React, { useMemo } from 'react';
import { TabsContent } from '../../components/Tabs';
import { PagebuilderCategory, SearchResultType } from '../../lib/types';
import CategoryMenu from './CategoryMenu';
import Demos from './Demos';

type Props = {
	theme: string;
	category: string;
	pagebuilder: string;
	categories: PagebuilderCategory[];
	setCategory: (slug: string) => void;
	data: SearchResultType[];
	searchParams: URLSearchParams;
	initialTheme: string;
	plan: string;
};

const Content = ({
	theme,
	category,
	pagebuilder,
	categories,
	setCategory,
	data,
	searchParams,
	initialTheme,
	plan,
}: Props) => {
	// const location = useLocation();
	// const searchParams = new URLSearchParams(location.search);
	// const currentHeaderTab = searchParams.get('tab') || '';
	const demos = useMemo(() => {
		let search = searchParams.get('search');
		return data
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
					<CategoryMenu categories={categories} setCategory={setCategory} />
					<Demos demos={demos} />
				</>
			)}
		</TabsContent>
	);
};

export default Content;
