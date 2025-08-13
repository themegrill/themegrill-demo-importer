import React, { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { DemoType } from '../../lib/types';
import Demo from '../demos/Demo';

type ContentProps = {
	demos: DemoType[];
};

const Content = ({ demos }: ContentProps) => {
	const [searchParams] = useSearchParams();
	const selectedPagebuilders = searchParams.get('pagebuilder')?.split(',').filter(Boolean) || [];
	const selectedCategories = searchParams.get('category')?.split(',').filter(Boolean) || [];
	const search = searchParams.get('search') || '';
	const pagebuilder = searchParams.get('pagebuilder') || '';
	const newDemos = useMemo(() => {
		return demos
			.filter((d) => {
				if (!pagebuilder) {
					return true;
				}
				return d.pagebuilder.toLowerCase() === pagebuilder;
			})
			.filter((d) => {
				if (selectedCategories.length === 0) {
					return true;
				}
				const normalizedCategories = d.categories.map((cat) =>
					cat.toLowerCase().replace(/\s+/g, '-'),
				);
				return selectedCategories.some((cat) => normalizedCategories.includes(cat));
			})
			.filter((d) => (search ? d.title.toLowerCase().indexOf(search.toLowerCase()) !== -1 : true));
	}, [selectedCategories, selectedPagebuilders, search, demos]);

	return (
		// <div className="flex-1 p-20 sm:p-20 lg:p-[48px] grid [grid-template-columns:repeat(auto-fill,minmax(345px,1fr))] gap-10 overflow-y-auto bg-[#fff]">
		<div className="flex-1 p-14 sm:p-14 2xl:p-[88px] grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-10 overflow-y-auto bg-[#fff] content-wrapper">
			{newDemos.map((demo) => (
				<Demo key={demo.slug} demo={demo} />
			))}
		</div>
	);
};

export default Content;
