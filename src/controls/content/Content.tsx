import React from 'react';
import { useSearchParams } from 'react-router-dom';
import { TabsContent } from '../../components/Tabs';
import CategoryMenu from './CategoryMenu';
import Demos from './Demos';

const Content = () => {
	const [searchParams] = useSearchParams();
	const currentHeaderTab = searchParams.get('tab') || 'all';
	const categories = [
		{
			label: 'All',
			id: 'All',
		},
		{
			label: 'Blog',
			id: 'Blog',
		},
		{
			label: 'eCommerce',
			id: 'eCommerce',
		},
		{
			label: 'LMS',
			id: 'LMS',
		},
		{
			label: 'Magazine',
			id: 'Magazine',
		},
		{
			label: 'Business',
			id: 'Business',
		},
		{
			label: 'Portfolio',
			id: 'Portfolio',
		},
		{
			label: 'Music',
			id: 'Music',
		},
		{
			label: 'Health',
			id: 'Health',
		},
		{
			label: 'Restaurant',
			id: 'Restaurant',
		},
		{
			label: 'Business',
			id: 'Businesfs',
		},
		{
			label: 'Portfolio',
			id: 'Portfolifo',
		},
		{
			label: 'Music',
			id: 'Musicf',
		},
		{
			label: 'Health',
			id: 'Healthd',
		},
	];

	return (
		<TabsContent value={currentHeaderTab} className="mt-0">
			{/* <Tabs defaultValue="all">
				<TabsList className="border-[1px] border-solid border-[#f4f4f4] p-0 rounded-md overflow-hidden">
					<CategoryTab categories={categories} />
				</TabsList>
			</Tabs> */}

			<CategoryMenu categories={categories} />
			<Demos currentHeaderTab={currentHeaderTab} />
		</TabsContent>
	);
};

export default Content;
