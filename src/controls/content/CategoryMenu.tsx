import React, { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import IntersectObserver from '../../components/IntersectionObserver';

type Props = {
	categories: { label: string; id: string }[];
};

const CategoryMenu = ({ categories }: Props) => {
	const [activeTab, setActiveTab] = useState<string>(categories[0]?.id || '');
	const [searchParams, setSearchParams] = useSearchParams();

	const handleClick = (id: string) => {
		setActiveTab(id);
		const newSearchParams = new URLSearchParams(searchParams.toString());
		newSearchParams.set('category', id);
		setSearchParams(newSearchParams);
	};

	return (
		<>
			{/* <p className="text-[#383838] uppercase font-semibold text-[14px] ">Templates</p>
				<TabsTrigger
					value="all"
					className="tg-tabs tg-category-tabs"
					onClick={() => handleClick('all')}
				>
					All
					<span className="tg-demo-count">100</span>
				</TabsTrigger>
				{categories.map((category, index) => (
					<TabsTrigger
						key={index}
						value={category}
						className={`tg-tabs tg-category-tabs`}
						onClick={() => handleClick(category)}
						data-id={index}
					>
						{category}
						<span className="tg-demo-count">100</span>
					</TabsTrigger>
			))} */}
			<div className="flex items-center gap-[30px] md:gap-[35px] px-[20px] py-[20px] sm:px-[40px] sm:py-[20px] ">
				<p className="text-[#383838] uppercase font-semibold text-[14px] m-0 bg-[#FAFAFC]">
					Templates
				</p>

				<IntersectObserver categories={categories} activeTab={activeTab} handleClick={handleClick}>
					{categories.map((category) => (
						<button
							type="button"
							key={category.id}
							data-target={category.id}
							className="tg-tabs tg-category-tabs"
							data-state={activeTab === category.id ? 'active' : 'inactive'}
							onClick={() => handleClick(category.id)}
						>
							{category.label}
							<span className="tg-demo-count">100</span>
						</button>
					))}
				</IntersectObserver>
			</div>
			<hr className="my-0 border-[#f4f4f4]" />
		</>
	);
};

export default CategoryMenu;
