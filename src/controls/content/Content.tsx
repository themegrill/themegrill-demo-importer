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
};

const Content = ({ theme, category, pagebuilder, categories, setCategory, data }: Props) => {
	// const location = useLocation();
	// const searchParams = new URLSearchParams(location.search);
	// const currentHeaderTab = searchParams.get('tab') || '';
	const demos = useMemo(() => {
		return data
			.filter((d) => ('all' !== theme ? d.theme == theme : true))
			.filter((d) =>
				'all' !== pagebuilder ? Object.keys(d.pagebuilders).some((p) => p === pagebuilder) : true,
			)
			.filter((d) =>
				'all' !== category ? Object.keys(d.categories).some((p) => p === category) : true,
			);
	}, [theme, category, pagebuilder]);

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
