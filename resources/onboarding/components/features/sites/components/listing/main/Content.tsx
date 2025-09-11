import { useGrid, useVirtualizer } from '@virtual-grid/react';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { DemoType } from '../../../../../../lib/types';
import { Route } from '../../../../../../routes';
import Demo from '../demos/Demo';

type ContentProps = {
	demos: DemoType[];
};

export enum MediaQuerySizes {
	SM = 290,
	MD = 642,
	LG = 674,
	XL = 850,
	'2XL' = 1250,
}

// Helper function to get responsive columns
const getResponsiveColumns = (width: number): number => {
	if (width < MediaQuerySizes.MD) return 1;
	if (width < MediaQuerySizes.XL) return 2;
	return 3;
};

const getResponsiveGap = (width: number): number => {
	return width < MediaQuerySizes['2XL'] ? 32 : 40;
};

// Helper function to get responsive card dimensions
const getResponsiveCardSize = (width: number) => {
	const columns = getResponsiveColumns(width);

	// Calculate card width based on container width and columns
	// Account for padding and gaps
	const containerPadding =
		width < MediaQuerySizes.XL ? 112 : width < MediaQuerySizes['2XL'] ? 108 : 176;
	const gap = getResponsiveGap(width);
	const availableWidth = Math.max(width - containerPadding);
	const cardWidth = Math.floor((availableWidth - gap * (columns - 1)) / columns);

	const imageHeight = Math.floor(cardWidth / 0.84); // Image section aspect ratio
	const titleSectionHeight = 51; // Approximate height of title section
	const cardHeight = imageHeight + titleSectionHeight;

	return {
		width: Math.max(cardWidth),
		height: Math.max(cardHeight),
	};
};

const Content = ({ demos }: ContentProps) => {
	const searchParams = Route.useSearch();
	const search = searchParams.search || '';
	const builder = searchParams.builder || '';
	const selectedCategories = searchParams.category?.split(',').filter(Boolean) || [];

	const [containerSize, setContainerSize] = useState({ width: 0, height: 0 });

	const newDemos = useMemo(() => {
		return demos
			.filter((d) => {
				if (!builder) {
					return true;
				}
				return d.pagebuilder.toLowerCase() === builder;
			})
			.filter((d) => {
				if (selectedCategories.length === 0) {
					return true;
				}
				const normalizedCategories = d.categories.map((cat) =>
					cat.toLowerCase().replace(/\s+/g, '-'),
				);
				return selectedCategories.some((cat) => normalizedCategories.includes(cat));
			})
			.filter((d) => (search ? d.title.toLowerCase().indexOf(search.toLowerCase()) !== -1 : true));
	}, [selectedCategories, builder, search, demos]);

	const ref = useRef<HTMLDivElement>(null);

	// Responsive values based on actual content container size (not screen size)
	const columns = useMemo(() => getResponsiveColumns(containerSize.width), [containerSize.width]);
	const cardSize = useMemo(() => getResponsiveCardSize(containerSize.width), [containerSize.width]);
	const gap = useMemo(() => getResponsiveGap(containerSize.width), [containerSize.width]);

	// Resize observer to track actual content container size
	const updateSize = useCallback(() => {
		if (ref.current) {
			setContainerSize({
				width: ref.current.clientWidth,
				height: ref.current.clientHeight,
			});
		}
	}, []);

	useEffect(() => {
		// Initial size measurement
		updateSize();

		// Set up resize observer
		const resizeObserver = new ResizeObserver(updateSize);
		if (ref.current) {
			resizeObserver.observe(ref.current);
		}

		// Also listen to window resize as fallback
		window.addEventListener('resize', updateSize);

		return () => {
			resizeObserver.disconnect();
			window.removeEventListener('resize', updateSize);
		};
	}, [updateSize]);

	const grid = useGrid({
		scrollRef: ref,
		count: newDemos.length,
		columns: columns,
		gap: gap,
		size: cardSize,
	});

	const rowVirtualizer = useVirtualizer(grid.rowVirtualizer);
	const columnVirtualizer = useVirtualizer(grid.columnVirtualizer);

	useEffect(() => {
		rowVirtualizer.measure();
	}, [rowVirtualizer, grid.virtualItemHeight, columns, cardSize]);

	useEffect(() => {
		columnVirtualizer.measure();
	}, [columnVirtualizer, grid.virtualItemWidth, columns, cardSize]);

	// Reset virtualizers when layout changes
	useEffect(() => {
		if (rowVirtualizer && columnVirtualizer) {
			rowVirtualizer.measure();
			columnVirtualizer.measure();
		}
	}, [columns, cardSize, rowVirtualizer, columnVirtualizer]);

	return (
		<>
			{newDemos.length === 0 ? (
				<div className="flex-1 flex items-center justify-center bg-[#fff]">
					<div className="text-center">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							fill="none"
							viewBox="0 0 60 60"
							width={60}
							height={60}
						>
							<g fill="#D3D3D3" clipPath="url(#a)">
								<path d="M58.267 58.284a3.747 3.747 0 0 1-5.297 0L40.951 46.262l5.297-5.297 12.019 12.037a3.747 3.747 0 0 1 0 5.282Z" />
								<path d="M7.608 7.627a26.009 26.009 0 1 0 36.8 36.763 26.009 26.009 0 0 0-36.8-36.763Zm35.299 35.298A23.895 23.895 0 1 1 9.114 9.132a23.895 23.895 0 0 1 33.793 33.793Z" />
							</g>
							<defs>
								<clipPath id="a">
									<path fill="#fff" d="M0 0h60v60H0z" />
								</clipPath>
							</defs>
						</svg>
						<h3 className="mb-[7px] mt-[16px] text-[#383838] text-[20px] leading-[29px]">
							{__('Sorry, no result found', 'themegrill-demo-importer')}
						</h3>
						<p className="m-0 text-[14px] leading-[29px] text-[#7A7A7A]">
							{__('Please try another search', 'themegrill-demo-importer')}
						</p>
					</div>
				</div>
			) : (
				<div
					ref={ref}
					className="flex-1 p-14 sm:p-14 2xl:p-[88px] overflow-y-auto bg-[#fff] content-wrapper"
				>
					{containerSize.width > 0 && (
						<div
							style={{
								position: 'relative',
								height: `${rowVirtualizer.getTotalSize()}px`,
								width: `${columnVirtualizer.getTotalSize()}px`,
							}}
						>
							{rowVirtualizer.getVirtualItems().map((virtualRow) => (
								<React.Fragment key={virtualRow.key}>
									{columnVirtualizer.getVirtualItems().map((virtualColumn) => {
										const item = grid.getVirtualItem({
											row: virtualRow,
											column: virtualColumn,
										});

										if (!item) return null;

										const demoIndex = virtualRow.index * columns + virtualColumn.index;
										const demo = newDemos[demoIndex];

										if (!demo) return null;

										return (
											<div key={virtualColumn.key} style={item.style}>
												<Demo demo={demo} />
											</div>
										);
									})}
								</React.Fragment>
							))}
						</div>
					)}
				</div>
			)}
		</>
	);
};

export default Content;
