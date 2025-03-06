import React from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '../../components/Button';
import {
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
	DropdownMenu as TGDropdownMenu,
} from '../../components/DropdownMenu';
import { PagebuilderCategory } from '../../lib/types';

declare const require: any;

type Props = {
	pagebuilders: PagebuilderCategory[];
	setPagebuilder: (slug: string) => void;
	currentPagebuilder: string;
};

const PagebuilderDropdownMenu = ({ pagebuilders, setPagebuilder, currentPagebuilder }: Props) => {
	const [searchParams, setSearchParams] = useSearchParams();
	const handlePagebuilderChange = (pagebuilder: PagebuilderCategory) => {
		setPagebuilder(pagebuilder.slug);
		const newSearchParams = new URLSearchParams(searchParams.toString());
		newSearchParams.set('pagebuilder', pagebuilder.slug);
		setSearchParams(newSearchParams);
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../assets/images/${key}.jpg`);
		} catch {
			return '';
		}
	};

	return (
		<TGDropdownMenu>
			<DropdownMenuTrigger asChild>
				<Button
					variant="outline"
					className="text-[#383838] font-[400] px-5 bg-white border-[1px] border-solid cursor-pointer border-[#f4f4f4] w-full sm:w-[172px] h-11 items-center justify-between focus-visible:ring-0"
				>
					<span>{currentPagebuilder}</span>
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
			<DropdownMenuContent className="w-full sm:w-[172px] bg-white p-0">
				{pagebuilders &&
					pagebuilders.map((pagebuilder) => (
						<div key={pagebuilder.slug}>
							<DropdownMenuItem
								className="p-[16px]"
								onClick={() => handlePagebuilderChange(pagebuilder)}
							>
								{pagebuilder.slug !== 'all' && checkImageExists(pagebuilder.slug) !== '' && (
									<img
										src={require(`../../assets/images/${pagebuilder.slug}.jpg`)}
										alt=""
										className="mr-2"
									/>
								)}
								<span className="text-[14px]">
									{pagebuilder.value} ({pagebuilder.count})
								</span>
							</DropdownMenuItem>
							<DropdownMenuSeparator className="m-0" />
						</div>
					))}
			</DropdownMenuContent>
		</TGDropdownMenu>
	);
};

export default PagebuilderDropdownMenu;
