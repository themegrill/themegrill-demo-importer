import React from 'react';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { TabsList, TabsTrigger } from '../../components/Tabs';
import { PagebuilderCategory, Theme } from '../../lib/types';
import DropdownMenu from '../dropdown-menu/DropdownMenu';

type Props = {
	themes: Theme[];
	setTheme: (slug: string) => void;
	pagebuilders: PagebuilderCategory[];
	setPagebuilder: (slug: string) => void;
	currentPagebuilder: string;
};

const Header = ({ themes, setTheme, pagebuilders, setPagebuilder, currentPagebuilder }: Props) => {
	// const [searchParams, setSearchParams] = useSearchParams();
	const handleClick = (tab: string) => {
		// setSearchParams({ tab: tab });
		setTheme(tab);
	};

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
						onClick={() => handleClick(item.slug)}
						key={index}
					>
						{item.slug != 'all' && (
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="20"
								height="20"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									d="M24.0002 12C24.0002 5.37258 18.6277 0 12.0002 0C5.37283 0 0.000244141 5.37258 0.000244141 12C0.000244141 18.6274 5.37283 24 12.0002 24C18.6277 24 24.0002 18.6274 24.0002 12Z"
									fill="#004846"
								/>
								<path
									d="M15.5715 7.00012C15.3838 6.9999 15.198 7.03676 15.0247 7.10858C14.8514 7.1804 14.6939 7.28577 14.5615 7.41862L7.41846 14.5611C7.15052 14.8291 7 15.1925 7 15.5714C7 15.9503 7.15052 16.3137 7.41846 16.5816C7.68639 16.8496 8.04979 17.0001 8.42871 17.0001C8.80763 17.0001 9.17102 16.8496 9.43896 16.5816L16.5815 9.43862C16.7812 9.23884 16.9172 8.98433 16.9723 8.70725C17.0274 8.43018 16.9991 8.14299 16.891 7.88199C16.7829 7.62099 16.5999 7.3979 16.365 7.24093C16.1301 7.08397 15.854 7.00016 15.5715 7.00012Z"
									fill="white"
								/>
								<path
									d="M7.50781 9.51952C7.64837 9.63226 7.82592 9.68854 8.00576 9.67735C8.1856 9.66616 8.35481 9.58831 8.48031 9.45902L10.9303 7.00902V7.00002H8.48881C8.10741 6.99416 7.73871 7.13704 7.46085 7.39838C7.183 7.65971 7.01781 8.01897 7.00031 8.40002C6.99585 8.61269 7.03913 8.82365 7.12695 9.01739C7.21478 9.21113 7.34493 9.38271 7.50781 9.51952Z"
									fill="white"
								/>
								<path
									d="M15.5657 14.4955L13.0612 17H15.5162C15.8959 17.0052 16.2628 16.8628 16.5396 16.6027C16.8163 16.3427 16.9813 15.9853 16.9997 15.606C17.0054 15.373 16.9539 15.1423 16.8498 14.9338C16.7456 14.7254 16.5919 14.5457 16.4022 14.4105C16.275 14.324 16.1214 14.285 15.9684 14.3006C15.8154 14.3161 15.6728 14.3851 15.5657 14.4955Z"
									fill="white"
								/>
							</svg>
						)}
						<span className={`${item.slug == 'all' ? 'pl-0' : 'pl-2'}`}>{item.name}</span>
					</TabsTrigger>
				))}
			</TabsList>

			<DropdownMenu
				pagebuilders={pagebuilders}
				setPagebuilder={setPagebuilder}
				currentPagebuilder={currentPagebuilder}
			/>

			<div className="flex items-center flex-1 relative">
				<Input
					type="text"
					placeholder="Search awesome demos..."
					style={{ paddingRight: '30px', border: '1px solid #f4f4f4' }}
					className="h-11 placeholder:text-[#a7a7a7]   "
				/>
				<Button className="p-0 border-none bg-white absolute right-2 h-0 cursor-pointer">
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
		</div>
	);
};

export default Header;
