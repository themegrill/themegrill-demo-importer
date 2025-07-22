import { __ } from '@wordpress/i18n';
import Menu, { MenuItem } from 'rc-menu';
import 'rc-menu/assets/index.css';
import React from 'react';
import { useSearchParams } from 'react-router-dom';
import { PagebuilderCategory } from '../../lib/types';

type Props = {
	categories: PagebuilderCategory[];
};

const CategoryMenu = ({ categories }: Props) => {
	// const { setCategory, category } = useDemoContext();
	const [searchParams, setSearchParams] = useSearchParams();
	const $category = searchParams.get('category') || 'all';

	// const [activeTab, setActiveTab] = useState<string>(categories[0]?.slug || '');

	const handleClick = (slug: string) => {
		// setActiveTab(slug);
		setSearchParams((prev) => {
			prev.set('category', slug);
			return prev;
		});
	};

	return (
		<>
			<div className="flex items-center gap-[30px] md:gap-[35px] px-[20px] py-[20px] sm:px-[40px] sm:py-[20px] ">
				<p className="text-[#383838] uppercase font-semibold text-[14px] m-0 bg-[#FAFAFC]">
					{__('Templates', 'themegrill-demo-importer')}
				</p>

				<Menu
					className="tgdi-category-filter bg-transparent mt-0 border-0 flex-1"
					mode="horizontal"
					triggerSubMenuAction="click"
					activeKey={$category}
					overflowedIndicator={
						<>
							<span className="mr-2 text-[14px] text-[#383838]">More</span>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="10"
								height="10"
								viewBox="0 0 10 10"
								fill="current"
							>
								<path
									fillRule="evenodd"
									clipRule="evenodd"
									d="M2.20512 3.45529C2.36784 3.29257 2.63166 3.29257 2.79438 3.45529L4.99975 5.66066L7.20512 3.45529C7.36784 3.29257 7.63166 3.29257 7.79438 3.45529C7.9571 3.61801 7.9571 3.88183 7.79438 4.04455L5.29438 6.54455C5.13166 6.70726 4.86784 6.70726 4.70512 6.54455L2.20512 4.04455C2.0424 3.88183 2.0424 3.61801 2.20512 3.45529Z"
									fill="current"
								/>
							</svg>
						</>
					}
				>
					{categories.map((cat) => (
						<MenuItem
							key={cat.slug}
							onClick={() => handleClick(cat.slug)}
							className="tg-tabs tg-category-tabs"
							data-state={$category === cat.slug ? 'active' : 'inactive'}
						>
							<span className="text-[14px] leading-none mr-2">{cat.value}</span>
							<span className="tg-demo-count">{cat.count}</span>
						</MenuItem>
					))}
				</Menu>
			</div>
			<hr className="my-0 border-[#f4f4f4]" />
		</>
	);
};

export default CategoryMenu;
