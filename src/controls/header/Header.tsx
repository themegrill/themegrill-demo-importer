import React, { useState } from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { TabsList, TabsTrigger } from '../../components/Tabs';
import { PagebuilderCategory, Theme } from '../../lib/types';
import PagebuilderDropdownMenu from '../dropdown-menu/PagebuilderDropdownMenu';
import PlanDropdown from '../dropdown-menu/PlanDropdown';

declare const require: any;

type Props = {
	themes: Theme[];
	setTheme: (slug: string) => void;
	pagebuilders: PagebuilderCategory[];
	setPagebuilder: (slug: string) => void;
	currentPagebuilder: string;
	searchParams: URLSearchParams;
	setSearchParams: (value: URLSearchParams) => void;
	plans: Record<string, string>;
	plan: string;
	setPlan: (key: string) => void;
};

const Header = ({
	themes,
	setTheme,
	pagebuilders,
	setPagebuilder,
	currentPagebuilder,
	searchParams,
	setSearchParams,
	plans,
	plan,
	setPlan,
}: Props) => {
	const [search, setSearch] = useState('');

	const handleThemeClick = (tab: string) => {
		// setSearchParams({ tab: tab });
		setTheme(tab);
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../assets/images/${key}.png`);
		} catch {
			return '';
		}
	};

	const removeSearchInput = () => {
		if (search) {
			setSearch('');
			searchParams.delete('search');
			setSearchParams(searchParams);
		}
	};

	const handleSearch = (event: React.ChangeEvent<HTMLInputElement>) => {
		let value = event.target.value;
		setSearch(value);
		searchParams.set('search', value);
		setSearchParams(searchParams);
	};

	// useEffect(() => {
	// 		setCategory('all');
	// 		setSearchParams({ tab: theme, category: 'all', pagebuilder: pagebuilder });
	// 	}, [theme, currentPagebuilder]);

	return (
		<div
			className="flex gap-y-4 sm:gap-x-8 items-center px-[20px] py-[20px] flex-wrap sm:px-[40px]"
			style={{ backgroundColor: '#fff' }}
		>
			<TabsList className="border-[1px] border-solid border-[#f4f4f4] p-0 rounded-md overflow-hidden">
				{themes.map((item, index) => (
					<TabsTrigger
						value={item.slug}
						className={`tg-tabs px-[20px] py-[11px] h-11 bg-white ${index === 0 ? 'border-none' : 'border-[0px] border-l-[1px] border-solid border-[#f4f4f4]'}`}
						onClick={() => handleThemeClick(item.slug)}
						key={index}
					>
						{item.slug != 'all' && checkImageExists(item.slug) !== '' && (
							<img src={require(`../../assets/images/${item.slug}.png`)} alt="" className="mr-2" />
						)}
						<span>{item.name}</span>
					</TabsTrigger>
				))}
			</TabsList>

			<PagebuilderDropdownMenu
				pagebuilders={pagebuilders}
				setPagebuilder={setPagebuilder}
				currentPagebuilder={currentPagebuilder}
			/>

			<div className="flex items-center flex-1 relative">
				<Input
					type="text"
					placeholder="Search awesome demos..."
					style={{ paddingRight: '30px', border: '1px solid #f4f4f4' }}
					value={search}
					onChange={handleSearch}
					className="h-11 placeholder:text-[#a7a7a7]"
				/>
				<Button
					className="p-0 border-none bg-white absolute right-2 h-0 cursor-pointer"
					onClick={removeSearchInput}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="22"
						height="20"
						viewBox="0 0 22 20"
						fill="none"
					>
						<g opacity="0.5">
							<path
								d="M11.7195 3C15.587 3 18.7195 6.1325 18.7195 10C18.7195 13.8675 15.587 17 11.7195 17C7.85198 17 4.71948 13.8675 4.71948 10C4.71948 6.1325 7.85198 3 11.7195 3ZM16.0945 12.625L13.4695 10L16.0945 7.375L14.3445 5.625L11.7195 8.25L9.09448 5.625L7.34448 7.375L9.96948 10L7.34448 12.625L9.09448 14.375L11.7195 11.75L14.3445 14.375L16.0945 12.625Z"
								fill="#383838"
							/>
						</g>
					</svg>
				</Button>
			</div>

			<PlanDropdown
				plans={plans}
				plan={plan}
				setPlan={setPlan}
				searchParams={searchParams}
				setSearchParams={setSearchParams}
			/>

			{/* <div className="border-[1px] border-solid border-[#f4f4f4] p-0 rounded-md overflow-hidden">
				<button
					value="free"
					className={`px-[20px] py-[11px] h-11 border-[0px] border-l-[1px] border-solid border-[#f4f4f4] cursor-pointer text-[14px] font-[500] ${searchParams.get('option') === 'free' ? 'bg-[#2563eb] text-white shadow-sm' : 'bg-white text-[#383838]'}`}
					onClick={() => handleClick('free')}
				>
					Free
				</button>
				<button
					value="pro"
					className={`px-[20px] py-[11px] h-11 border-[0px] border-l-[1px] border-solid border-[#f4f4f4] cursor-pointer text-[14px] font-[500] ${searchParams.get('option') === 'pro' ? 'bg-[#2563eb] text-white shadow-sm' : 'bg-white text-[#383838]'}`}
					onClick={() => handleClick('pro')}
				>
					Pro
				</button>
			</div> */}
		</div>
	);
};

export default Header;
