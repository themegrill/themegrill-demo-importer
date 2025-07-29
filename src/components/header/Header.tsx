import React from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '../../controls/Button';
import { Input } from '../../controls/Input';
import { PagebuilderCategory, Theme, ThemeItem } from '../../lib/types';
import PagebuilderDropdownMenu from '../dropdown-menu/PagebuilderDropdownMenu';
import PlanDropdown from '../dropdown-menu/PlanDropdown';
import ThemeDropdown from '../dropdown-menu/ThemeDropdown';

declare const require: any;

type Props = {
	themes: Theme[];
	pagebuilders: PagebuilderCategory[];
	currentPagebuilder: string;
	plans: Record<string, string>;
	theme: string;
	data: ThemeItem[];
};

const Header = ({ themes, pagebuilders, currentPagebuilder, plans, theme, data }: Props) => {
	const [searchParams, setSearchParams] = useSearchParams();
	const search = searchParams.get('search') || '';
	let activeTheme = null;

	if (theme !== 'all') {
		activeTheme = themes.find((t) => t.slug === theme);
	}

	const removeSearchInput = () => {
		if (search) {
			setSearchParams((prev) => {
				prev.delete('search');
				return new URLSearchParams(prev);
			});
		}
	};

	const handleSearch = (event: React.ChangeEvent<HTMLInputElement>) => {
		const value = event.target.value;
		setSearchParams((prev) => {
			prev.set('search', value);
			return prev;
		});
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../images/${key}.png`);
		} catch {
			return '';
		}
	};

	return (
		<div
			className="flex gap-y-4 sm:gap-x-8 items-center px-[20px] py-[20px] flex-wrap sm:px-[40px]"
			style={{ backgroundColor: '#fff' }}
		>
			{theme !== 'all' && activeTheme ? (
				<div className="flex items-center gap-2 w-full sm:w-[132px] bg-white px-5 py-[9px] border border-solid border-[#f4f4f4] rounded-md">
					{checkImageExists(theme) !== '' && (
						<img src={require(`../../images/${theme}.png`)} alt="" width="24px" />
					)}
					<span className="text-[14px]">{activeTheme.name}</span>
				</div>
			) : (
				<ThemeDropdown themes={themes} />
			)}

			<PagebuilderDropdownMenu
				pagebuilders={pagebuilders}
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

			<PlanDropdown plans={plans} />
		</div>
	);
};

export default Header;
