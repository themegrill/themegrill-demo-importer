import React, { useEffect, useState } from 'react';
import { Page, PageWithSelection, SearchResultType } from '../../lib/types';
import ImportButton from '../import/ImportButton';
import SingleTemplate from './SingleTemplate';

type Props = {
	pages: Page[];
	demo: SearchResultType;
	initialTheme: string;
	siteTitle: string;
	siteTagline: string;
	siteLogoId: number;
};

const Template = ({ pages, demo, initialTheme, siteTitle, siteTagline, siteLogoId }: Props) => {
	const [disabled, setDisabled] = useState(true);
	const [allPages, setAllPages] = useState<PageWithSelection[]>(() => {
		return pages.map((p, index) => {
			return {
				ID: index + 1,
				post_name: p.post_name,
				post_title: p.post_title,
				content: p.content,
				featured_image: p.featured_image,
				isSelected: false,
			};
		});
	});

	useEffect(() => {
		const hasSelectedPages = allPages.some((page) => page.isSelected);
		setDisabled(!hasSelectedPages);
	}, [allPages]);

	return (
		<div className="h-[370px] sm:h-[302px] w-full bg-white p-[25px] sm:p-[32px] shadow absolute bottom-0 ">
			<div className="mb-[24px] flex flex-wrap justify-between items-center">
				<div>
					<h4 className="text-[22px] m-0 mb-[8px] text-[#383838]">{demo.name}</h4>
					<p className="text-[#7a7a7a] text-[14px] mt-4 sm:m-0">
						6 Templates (You can select pages manually by clicking on templates.)
					</p>
				</div>
				<div className="mr-[70px] flex flex-wrap gap-[16px]">
					<ImportButton
						buttonTitle="Import All"
						initialTheme={initialTheme}
						demo={demo}
						siteTitle={siteTitle}
						siteTagline={siteTagline}
						siteLogoId={siteLogoId}
						additionalStyles="bg-white rounded-[2px] px-[16px] py-[8px] border border-solid border-[#2563EB] text-[#2563EB] font-[600] cursor-pointer"
						textColor="#2563EB"
					/>
					<ImportButton
						buttonTitle="Import Selected Pages"
						pages={allPages}
						initialTheme={initialTheme}
						demo={demo}
						siteTitle={siteTitle}
						siteTagline={siteTagline}
						siteLogoId={siteLogoId}
						disabled={disabled}
					/>
				</div>
			</div>
			<div className="flex gap-[16px] w-full overflow-x-auto tg-overlay-template pb-[20px]">
				{allPages.map((page, index) => (
					<SingleTemplate key={index} page={page} setAllPages={setAllPages} />
				))}
			</div>
		</div>
	);
};

export default Template;
