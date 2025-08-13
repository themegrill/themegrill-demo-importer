import { __ } from '@wordpress/i18n';
import { ArrowRight, Search, X } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '../../controls/Button';
import { Input } from '../../controls/Input';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../controls/Tooltip';
import { PagebuilderCategory } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';

declare const require: any;

type Props = {
	pagebuilders: PagebuilderCategory[];
	categories: PagebuilderCategory[];
	handleRefetch: () => void;
};

const Sidebar = ({ pagebuilders, categories, handleRefetch }: Props) => {
	const { localizedData } = useLocalizedData();
	const [searchParams, setSearchParams] = useSearchParams();
	const search = searchParams.get('search') || '';
	const pagebuilder = searchParams.get('pagebuilder') || '';
	const [focused, setFocused] = useState(false);

	const handleSearch = (event: React.ChangeEvent<HTMLInputElement>) => {
		const value = event.target.value;
		setSearchParams((prev) => {
			prev.set('search', value);
			return prev;
		});
	};

	const handlePagebuilder = (pagebuilder: string) => {
		setSearchParams((prev) => {
			prev.set('pagebuilder', pagebuilder);
			return prev;
		});
	};

	const handleCategory = (selectedCategory: string) => {
		setSearchParams((prev) => {
			if (selectedCategory === 'all') {
				prev.delete('category');
				return prev;
			}
			const currentCategories = prev.get('category')?.split(',').filter(Boolean) || [];
			const isSelected = currentCategories.includes(selectedCategory);
			let updatedCategories;
			if (isSelected) {
				updatedCategories = currentCategories.filter((cat) => cat !== selectedCategory);
			} else {
				updatedCategories = [...currentCategories, selectedCategory];
			}

			// const allAvailableCategories = categories
			// 	.filter((cat) => cat.slug !== 'all')
			// 	.map((cat) => cat.slug);

			// // Check if all categories are selected
			// const allCategoriesSelected = allAvailableCategories.every((cat) =>
			// 	updatedCategories.includes(cat),
			// );

			// if (updatedCategories.length === 0 || allCategoriesSelected) {
			// 	prev.delete('category');
			// } else {
			// 	prev.set('category', updatedCategories.join(','));
			// }
			if (updatedCategories.length === 0) {
				prev.delete('category');
			} else {
				prev.set('category', updatedCategories.join(','));
			}

			return prev;
		});
	};

	const isCategorySelected = (slug: string) => {
		const selectedCategories = searchParams.get('category')?.split(',') || [];
		return slug === 'all' ? selectedCategories.length === 0 : selectedCategories.includes(slug);
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../assets/images/${key}.jpg`);
		} catch {
			return '';
		}
	};

	useEffect(() => {
		if (!pagebuilder && pagebuilders.length > 0) {
			setSearchParams((prev) => {
				prev.set('pagebuilder', pagebuilders[0].slug);
				return prev;
			});
		}
	}, [pagebuilders, pagebuilder, setSearchParams]);

	return (
		<div className="w-[350px] min-w-[350px] flex flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] ">
			<div className="px-6 pt-6">
				<div className="flex justify-between items-center border-0 border-b border-solid border-[#E3E3E3] pb-6">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="50"
						height="50"
						viewBox="0 0 50 50"
						fill="none"
					>
						<path
							d="M46.9997 25C46.9997 12.8497 37.15 3 24.9997 3C12.8495 3 2.99975 12.8497 2.99975 25C2.99975 37.1503 12.8495 47 24.9997 47C37.15 47 46.9997 37.1503 46.9997 25Z"
							fill="#004846"
						/>
						<path
							d="M31.5464 15.8336C31.2024 15.8332 30.8618 15.9008 30.544 16.0324C30.2262 16.1641 29.9376 16.3573 29.6947 16.6008L16.5992 29.6954C16.108 30.1866 15.8321 30.8529 15.8321 31.5475C15.8321 32.2422 16.108 32.9085 16.5992 33.3997C17.0905 33.8909 17.7567 34.1668 18.4514 34.1668C19.146 34.1668 19.8123 33.8909 20.3035 33.3997L33.3981 20.3042C33.7643 19.9379 34.0136 19.4713 34.1146 18.9633C34.2157 18.4554 34.1638 17.9288 33.9656 17.4503C33.7674 16.9718 33.4318 16.5629 33.0012 16.2751C32.5706 15.9873 32.0643 15.8337 31.5464 15.8336Z"
							fill="white"
						/>
						<path
							d="M16.7631 20.4523C17.0207 20.659 17.3463 20.7622 17.676 20.7417C18.0057 20.7212 18.3159 20.5785 18.546 20.3414L23.0376 15.8498V15.8333H18.5616C17.8623 15.8225 17.1864 16.0845 16.677 16.5636C16.1676 17.0427 15.8647 17.7013 15.8326 18.3999C15.8245 18.7898 15.9038 19.1766 16.0648 19.5318C16.2258 19.887 16.4644 20.2015 16.7631 20.4523Z"
							fill="white"
						/>
						<path
							d="M31.5363 29.5753L26.9448 34.1669H31.4456C32.1418 34.1765 32.8145 33.9153 33.3219 33.4386C33.8292 32.9618 34.1317 32.3066 34.1653 31.6112C34.1759 31.1841 34.0815 30.761 33.8905 30.3789C33.6995 29.9968 33.4178 29.6674 33.0699 29.4194C32.8368 29.2608 32.5553 29.1895 32.2747 29.218C31.9942 29.2465 31.7328 29.373 31.5363 29.5753Z"
							fill="white"
						/>
					</svg>
					{/* {checkImageExists(localizedData.theme) && (
						<img src={checkImageExists(localizedData.theme)} alt="" width="50px" />
					)} */}
					<Tooltip>
						<TooltipTrigger asChild>
							<X
								size={20}
								color="#909090"
								strokeWidth={2}
								onClick={() => (window.location.href = '/wp-admin')}
								className="cursor-pointer"
							/>
						</TooltipTrigger>
						<TooltipContent side="bottom">
							{__('Exit Import Process', 'themegrill-demo-importer')}
						</TooltipContent>
					</Tooltip>
				</div>
			</div>
			<div className="flex flex-col gap-6 box-border px-6 pt-6 pb-10 overflow-y-auto">
				<div>
					<div className="flex gap-2 items-center mb-5">
						<h3 className="text-[16px] text-[#1F1F1F] m-0">
							{__('Select a Template you like', 'themegrill-demo-importer')}
						</h3>
						<ArrowRight size={17} color="#1F1F1F" />
					</div>
					<div className="flex items-center relative">
						<Input
							type="text"
							placeholder="Search ..."
							value={search}
							onChange={handleSearch}
							onFocus={() => setFocused(true)}
							onBlur={() => setFocused(false)}
							className={`h-[62px] placeholder:text-[#646464] placeholder:text-[16px]
						 	bg-white !px-6 !py-[18px]  !border-solid !rounded-md
							${focused ? '!border !border-[#5182EF]' : '!border-2 !border-[#EBEDEF]'}`}
						/>
						<Button className="border-none absolute right-6 h-0 p-0 cursor-pointer">
							<Search size={20} color="#909090" strokeWidth={1.7} />
						</Button>
					</div>
				</div>
				<div>
					<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5">
						{__('Choose Builder', 'themegrill-demo-importer')}
					</h3>
					<div className="flex flex-col gap-4">
						{pagebuilders.map((item) => (
							<Button
								key={item.slug}
								className={`cursor-pointer px-6 py-[18px] h-[62px] justify-start text-[#383838] text-[16px] leading-[27px] font-normal rounded-md border-2 border-solid gap-[10px] hover:bg-[#fff] hover:text-[#383838] ${
									pagebuilder === item.slug
										? 'bg-[#fff] border-[#5182EF]'
										: 'bg-[#FDFDFE] border-[#EBEDEF]'
								}`}
								onClick={() => handlePagebuilder(item.slug)}
							>
								{checkImageExists(item.slug) && item.slug && (
									<img src={require(`../../assets/images/${item.slug}.jpg`)} alt="" />
								)}
								{item.value}
							</Button>
						))}
					</div>
				</div>
				<div>
					<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5">
						{__('Categories', 'themegrill-demo-importer')}
					</h3>
					<div className="flex gap-[14px] flex-wrap">
						{categories.map((item) => (
							<Button
								key={item.slug}
								className={`cursor-pointer px-6 py-[12px] h-[48px] text-[16px] leading-[26px] font-normal rounded-md border-2 border-solid  ${
									isCategorySelected(item.slug)
										? 'bg-[#5182EF] border-[#5182EF] text-[#fff] hover:bg-[#5182EF] hover:text-[#fff]'
										: 'bg-[#FDFDFE] border-[#EEEFF2] text-[#383838] hover:bg-[#fff] hover:text-[#383838]'
								}`}
								onClick={() => handleCategory(item.slug)}
							>
								{item.value}
							</Button>
						))}
					</div>
				</div>
			</div>
		</div>
	);
};

export default Sidebar;
