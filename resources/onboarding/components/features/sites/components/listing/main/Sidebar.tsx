import { __ } from '@wordpress/i18n';
import { ArrowRight, Search, X } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import logo from '../../../../../../assets/images/starter-template-logo.png';
import { PagebuilderCategory } from '../../../../../../lib/types';
import { Route } from '../../../../../../routes';
import { Button } from '../../../../../ui/Button';
import { Input } from '../../../../../ui/Input';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../../../../ui/Tooltip';

declare const require: any;

type Props = {
	builders: PagebuilderCategory[];
	categories: PagebuilderCategory[];
	handleRefetch: () => void;
};

const Sidebar = ({ builders, categories, handleRefetch }: Props) => {
	const navigate = Route.useNavigate();
	const searchParams = Route.useSearch();
	const search = searchParams.search || '';
	const builder = searchParams.builder || '';
	const [focused, setFocused] = useState(false);

	const handleSearch = (event: React.ChangeEvent<HTMLInputElement>) => {
		const value = event.target.value;
		navigate({
			search: (prev) => ({
				...prev,
				search: value || undefined,
			}),
		});
	};

	const handleBuilder = (builder: string) => {
		navigate({
			search: (prev) => ({
				...prev,
				builder: builder || undefined,
			}),
		});
	};

	const handleCategory = (selectedCategory: string) => {
		navigate({
			search: (prev) => {
				const currentCategories = prev.category?.split(',').filter(Boolean) || [];

				let updatedCategories: string[];
				if (selectedCategory === 'all') {
					updatedCategories = [];
				} else {
					const isSelected = currentCategories.includes(selectedCategory);
					updatedCategories = isSelected
						? currentCategories.filter((cat) => cat !== selectedCategory)
						: [...currentCategories, selectedCategory];
				}

				return {
					...prev,
					category: updatedCategories.length > 0 ? updatedCategories.join(',') : undefined,
				};
			},
		});
	};

	const isCategorySelected = (slug: string) => {
		const selectedCategories = searchParams.category?.split(',') || [];
		return slug === 'all' ? selectedCategories.length === 0 : selectedCategories.includes(slug);
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../../../../../assets/images/${key}.jpg`);
		} catch {
			return '';
		}
	};

	useEffect(() => {
		if (!builder && builders.length > 0) {
			navigate({
				search: {
					builder: builders?.[0]?.id,
					category: undefined,
					search: undefined,
				},
			});
		}
	}, [builders, builder, navigate]);

	return (
		<div className="w-[350px] min-w-[350px] flex flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] ">
			<div className="px-6 pt-6">
				<div className="flex justify-between items-center border-0 border-b border-solid border-[#E3E3E3] pb-6">
					<img src={logo} alt="Starter Templates and Sites Pack By ThemeGrill" width={50} />
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
						<TooltipContent side="bottom" sideOffset={-4}>
							{__('Exit Import Process', 'themegrill-demo-importer')}
						</TooltipContent>
					</Tooltip>
				</div>
			</div>
			<div className="flex flex-col gap-6 box-border px-6 pt-6 pb-10 overflow-y-auto tg-scrollbar">
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
						{builders.map((item) => (
							<Button
								key={item.id}
								className={`cursor-pointer px-6 py-[18px] h-[62px] justify-start text-[#383838] text-[16px] leading-[27px] font-normal rounded-md border-2 border-solid gap-[10px] hover:bg-[#fff] hover:text-[#383838] ${
									builder === item.id
										? 'bg-[#fff] border-[#5182EF]'
										: 'bg-[#FDFDFE] border-[#EBEDEF]'
								}`}
								onClick={() => handleBuilder(item.id)}
							>
								{checkImageExists(item.id) && item.id && (
									<img
										src={require(`../../../../../../assets/images/${item.id}.jpg`)}
										alt=""
										className="w-6"
									/>
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
								key={item.id}
								className={`cursor-pointer px-6 py-[12px] h-[48px] text-[15px] leading-[26px] font-normal rounded-md border-2 border-solid  ${
									isCategorySelected(item.id)
										? 'bg-[#5182EF] border-[#5182EF] text-[#fff] hover:bg-[#5182EF] hover:text-[#fff]'
										: 'bg-[#FDFDFE] border-[#EEEFF2] text-[#383838] hover:bg-[#fff] hover:text-[#383838]'
								}`}
								onClick={() => handleCategory(item.id)}
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
