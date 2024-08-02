import React from 'react';
import elementor from '../../assets/images/elementor.png';
import { Button } from '../../components/Button';
import {
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
	DropdownMenu as TGDropdownMenu,
} from '../../components/DropdownMenu';

const DropdownMenu = () => {
	return (
		<TGDropdownMenu>
			<DropdownMenuTrigger asChild>
				<Button
					variant="outline"
					className="text-[#383838] px-5 bg-white border-[1px] border-solid cursor-pointer border-[#f4f4f4] w-full sm:w-[172px] h-11 items-center justify-between focus-visible:ring-0"
				>
					<span>View All</span>
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
				<DropdownMenuItem className="p-[16px]">
					<img src={elementor} alt="" className="mr-2" />
					<span className="text-[14px]">Gutenberg (9)</span>
				</DropdownMenuItem>
				<DropdownMenuSeparator className="m-0" />
				<DropdownMenuItem className="p-[16px]">
					<img src={elementor} alt="" className="mr-2" />
					<span>Brizy</span>
				</DropdownMenuItem>
				<DropdownMenuSeparator className="m-0" />
				<DropdownMenuItem className="p-[16px]">
					<img src={elementor} alt="" className="mr-2" />
					<span>Elementor</span>
				</DropdownMenuItem>
			</DropdownMenuContent>
		</TGDropdownMenu>
	);
};

export default DropdownMenu;
