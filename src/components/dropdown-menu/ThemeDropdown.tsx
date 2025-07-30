import React from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '../../controls/Button';
import {
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
	DropdownMenu as ThemeDropdownMenu,
} from '../../controls/DropdownMenu';
import { Theme } from '../../lib/types';

declare const require: any;

type Props = {
	themes: Theme[];
};

const ThemeDropdown = ({ themes }: Props) => {
	const [searchParams, setSearchParams] = useSearchParams();
	const theme = searchParams.get('theme') || 'all';

	const handleThemeChange = (theme: string) => {
		setSearchParams((prev) => {
			prev.set('theme', theme);
			prev.set('category', 'all');
			return prev;
		});
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../assets/images/${key}.png`);
		} catch {
			return '';
		}
	};
	return (
		<ThemeDropdownMenu>
			<DropdownMenuTrigger asChild>
				<Button
					variant="outline"
					className={`text-[#383838] font-[400] px-5 bg-[#fff] border border-solid cursor-pointer w-full border-[#f4f4f4] sm:w-[172px] h-11 items-center justify-between focus-visible:ring-0`}
				>
					<span className="capitalize flex items-center gap-2">
						{theme !== 'all' && checkImageExists(theme) !== '' && (
							<img src={require(`../../assets/images/${theme}.png`)} alt="" />
						)}
						{theme}
					</span>

					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="10"
						height="18"
						viewBox="0 0 10 18"
						fill="none"
					>
						<g clipPath="url(#clip0_3691_14325)">
							<path
								d="M5 11.4286C4.78571 11.4286 4.64286 11.3572 4.5 11.2143L0.214286 6.92858C-0.0714286 6.64287 -0.0714286 6.21429 0.214286 5.92858C0.5 5.64287 0.928571 5.64287 1.21429 5.92858L5 9.7143L8.78572 5.92858C9.07143 5.64287 9.5 5.64287 9.78571 5.92858C10.0714 6.21429 10.0714 6.64287 9.78571 6.92858L5.5 11.2143C5.35714 11.3572 5.21429 11.4286 5 11.4286Z"
								fill="#383838"
							/>
						</g>
						<defs>
							<clipPath id="clip0_3691_14325">
								<rect width="10" height="17.1429" fill="white" />
							</clipPath>
						</defs>
					</svg>
				</Button>
			</DropdownMenuTrigger>
			<DropdownMenuContent className={`w-full sm:w-[172px] bg-white p-0`}>
				{themes &&
					themes
						.filter((t) => t.slug !== theme)
						.map((th) => (
							<div key={th.slug}>
								<DropdownMenuItem
									className="p-[16px] gap-[8px]"
									onClick={() => handleThemeChange(th.slug)}
								>
									{th.slug !== 'all' && checkImageExists(th.slug) !== '' && (
										<img src={require(`../../assets/images/${th.slug}.png`)} alt="" />
									)}
									<span className="text-[14px]">{th.name}</span>
								</DropdownMenuItem>
								<DropdownMenuSeparator className="m-0" />
							</div>
						))}
			</DropdownMenuContent>
		</ThemeDropdownMenu>
	);
};

export default ThemeDropdown;
