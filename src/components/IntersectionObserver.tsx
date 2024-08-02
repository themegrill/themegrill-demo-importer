import React, { useEffect, useRef, useState } from 'react';
import { useLocation, useSearchParams } from 'react-router-dom';
import { Button } from './Button';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from './DropdownMenu';

const IntersectionStyles = {
	visible: {
		order: 0,
		visibility: 'visible',
		opacity: 1,
	},
	inVisible: {
		order: 100,
		visibility: 'hidden',
		pointerEvents: 'none',
	},
	toolbarWrapper: {
		overflow: 'hidden',
		display: 'flex',
		border: '1px solid black',
		alignItem: 'center',
	},
};

interface IntersectionProps {
	children: any;
	categories: Array<{
		label: string;
		id: string;
	}>;
	activeTab: string;
	handleClick: (id: string) => void;
}

function IntersectObserver({ children, categories, activeTab, handleClick }: IntersectionProps) {
	const ref = useRef<HTMLDivElement>(null);
	const [visibleMap, setVisibleMap] = useState<Record<string, boolean>>({});
	const location = useLocation();
	const [searchParams, setSearchParams] = useSearchParams();
	const hiddenCategories = categories.filter((category) => !visibleMap[category.id]);

	useEffect(() => {
		if (!ref.current) return;
		const observer = new IntersectionObserver(
			(entries) => {
				const updatedEntries: any = {};
				entries.forEach((entry: any) => {
					const target: string | null = entry.target.dataset?.['target'];
					if (entry.isIntersecting && target) {
						updatedEntries[target] = true;
					}
					if (!entry.isIntersecting && target) {
						updatedEntries[target] = false;
					}
				});

				setVisibleMap((prev) => ({
					...prev,
					...updatedEntries,
				}));
			},
			{
				root: ref.current,
				threshold: 0.98,
			},
		);

		Array.from(ref.current.children).forEach((item) => {
			if (item.getAttribute('data-target')) {
				observer.observe(item);
			}
		});
		return () => observer.disconnect();
	}, []);

	const shouldShowMenu = Object.values(visibleMap).some((v) => v === false);
	const isCategoryActive = hiddenCategories.some(
		(category) => category.id === searchParams.get('category'),
	);

	return (
		<>
			<div
				className="flex gap-[35px] md:gap-[20px] lg:gap-[35px] overflow-hidden h-full flex-1"
				ref={ref}
			>
				{React.Children.map(children, (child: any) => {
					const otherSX = visibleMap[child.props['data-target']]
						? IntersectionStyles.visible
						: IntersectionStyles.inVisible;

					return React.cloneElement(child, {
						sx: { ...children?.props?.sx, ...otherSX },
					});
				})}
				{shouldShowMenu && (
					<DropdownMenu>
						<DropdownMenuTrigger asChild>
							<Button
								variant="outline"
								className={`border-0 px-[16px] py-[8px] h-11 rounded cursor-pointer order-[99] sticky right-0
								${isCategoryActive ? 'bg-[#2563eb] text-white' : 'bg-[#fafafa] text-[#383838]'} hover:${isCategoryActive ? 'bg-[#2563eb]' : 'bg-[#fafafa]'} hover:${isCategoryActive ? 'text-white' : 'text-[#383838]'}
								data-[state=open]:bg-[#2563eb] data-[state=open]:text-white
								`}
							>
								<span className="mr-1">More</span>
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
											fill="currentColor"
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
						<DropdownMenuContent className="w-56 p-0 " side="bottom" align="end">
							{hiddenCategories.map((category, index) => (
								<>
									<DropdownMenuItem
										key={category.id}
										className="cursor-pointer text-[14px] font-[500] p-0"
									>
										<button
											type="button"
											data-target={category.id}
											key={category.id}
											className="tg-tabs w-full text-left rounded-none border-0 bg-white"
											style={{ padding: '12px' }}
											data-state={activeTab === category.id ? 'active' : 'inactive'}
											onClick={() => handleClick(category.id)}
										>
											<span>{category.label}</span>
											<span className="tg-demo-count">100</span>
										</button>
									</DropdownMenuItem>
									{index !== hiddenCategories.length - 1 && (
										<hr className="my-0 border-[#f4f4f4]" />
									)}
								</>
							))}
						</DropdownMenuContent>
					</DropdownMenu>
				)}
			</div>
		</>
	);
}

export default IntersectObserver;
