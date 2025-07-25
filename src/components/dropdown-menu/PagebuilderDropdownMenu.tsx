import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { Button } from '../../controls/Button';
import {
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
	DropdownMenu as TGDropdownMenu,
} from '../../controls/DropdownMenu';
import { PagebuilderCategory } from '../../lib/types';

declare const require: any;

type Props = {
	// pagebuilders: FilterItem;
	pagebuilders: PagebuilderCategory[];
	currentPagebuilder: string;
	isSidebar?: boolean;
};

const PagebuilderDropdownMenu = ({
	pagebuilders,
	currentPagebuilder,
	isSidebar = false,
}: Props) => {
	const [searchParams, setSearchParams] = useSearchParams();
	const { pagebuilder: paramPagebuilder } = useParams<{ pagebuilder?: string }>();
	const queryPagebuilder = searchParams.get('pagebuilder');
	const pagebuilder = isSidebar ? paramPagebuilder || 'all' : queryPagebuilder || 'all';
	const { slug } = useParams();
	const navigate = useNavigate();

	const handlePagebuilderChange = (pagebuilder: string, isSidebar: boolean) => {
		if (isSidebar) {
			navigate(`/import-detail/${slug}/${pagebuilder}`, {
				replace: true,
			});
		} else {
			setSearchParams((prev) => {
				prev.set('pagebuilder', pagebuilder);
				return prev;
			});
		}
	};

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../assets/images/${key}.jpg`);
		} catch {
			return '';
		}
	};

	const triggerRef = useRef<HTMLButtonElement>(null);
	const [width, setWidth] = useState<number | null>(null);

	useEffect(() => {
		if (triggerRef.current) {
			setWidth(triggerRef.current.offsetWidth);
		}
	}, [triggerRef.current]);

	return (
		<TGDropdownMenu>
			<DropdownMenuTrigger asChild>
				<Button
					ref={triggerRef}
					variant="outline"
					className={`text-[#383838] font-[400] px-5 bg-[#fff] border border-solid cursor-pointer w-full ${!isSidebar ? 'border-[#f4f4f4] sm:w-[172px]' : 'border-[#E9E9E9]'} h-11 items-center justify-between focus-visible:ring-0`}
				>
					{isSidebar ? (
						<span className="flex items-center gap-[8px]">
							{checkImageExists(pagebuilder) && pagebuilder && (
								<img src={require(`../../assets/images/${pagebuilder}.jpg`)} alt="" />
							)}
							{currentPagebuilder}
						</span>
					) : (
						<span>{currentPagebuilder}</span>
					)}

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
			<DropdownMenuContent
				className={`bg-white p-0`}
				style={{
					minWidth: width ? `${width}px` : undefined,
				}}
			>
				{/* {pagebuilders &&
					Object.entries(pagebuilders)
						.filter(([key]) => key !== pagebuilder)
						.map(([slug, { name, count }]) => (
							<div key={slug}>
								<DropdownMenuItem
									className="p-[16px] gap-[8px] w-full"
									onClick={() => handlePagebuilderChange(slug, isSidebar)}
								>
									{slug !== 'all' && checkImageExists(slug) !== '' && (
										<img src={require(`../../assets/images/${slug}.jpg`)} alt="" />
									)}
									<span className="text-[14px]">
										{name} {!isSidebar && '(' + count + ')'}
									</span>
								</DropdownMenuItem>
								<DropdownMenuSeparator className="m-0" />
							</div>
						))} */}
				{pagebuilders &&
					pagebuilders
						.filter((pg) => pg.slug !== pagebuilder)
						.map((pagebuilder) => (
							<div key={pagebuilder.slug}>
								<DropdownMenuItem
									className="p-[16px] gap-[8px] w-full"
									onClick={() => handlePagebuilderChange(pagebuilder.slug, isSidebar)}
								>
									{pagebuilder.slug !== 'all' && checkImageExists(pagebuilder.slug) !== '' && (
										<img src={require(`../../assets/images/${pagebuilder.slug}.jpg`)} alt="" />
									)}
									<span className="text-[14px]">
										{pagebuilder.value} {!isSidebar && '(' + pagebuilder.count + ')'}
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
