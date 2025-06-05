import React, { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import IntersectObserver from '../../controls/IntersectionObserver';
import { PagebuilderCategory } from '../../lib/types';

type Props = {
	categories: PagebuilderCategory[];
	setCategory: (slug: string) => void;
};

const CategoryMenu = ({ categories, setCategory }: Props) => {
	const [activeTab, setActiveTab] = useState<string>(categories[0]?.slug || '');
	const [searchParams, setSearchParams] = useSearchParams();

	const handleClick = (slug: string) => {
		setActiveTab(slug);
		setCategory(slug);
		const newSearchParams = new URLSearchParams(searchParams.toString());
		newSearchParams.set('category', slug);
		setSearchParams(newSearchParams);
	};

	return (
		<>
			<div className="flex items-center gap-[30px] md:gap-[35px] px-[20px] py-[20px] sm:px-[40px] sm:py-[20px] ">
				<p className="text-[#383838] uppercase font-semibold text-[14px] m-0 bg-[#FAFAFC]">
					Templates
				</p>

				<IntersectObserver categories={categories} activeTab={activeTab} handleClick={handleClick}>
					{categories.map((category) => (
						<button
							type="button"
							key={category.slug}
							data-target={category.slug}
							className="tg-tabs tg-category-tabs flex items-center justify-between gap-2"
							data-state={activeTab === category.slug ? 'active' : 'inactive'}
							onClick={() => handleClick(category.slug)}
						>
							<span className="flex-1 truncate">{category.value}</span>
							<span className="tg-demo-count">{category.count}</span>
						</button>
					))}
				</IntersectObserver>
			</div>
			<hr className="my-0 border-[#f4f4f4]" />
		</>
	);
};

export default CategoryMenu;
