import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Demo, Page, PageWithSelection, TDIDashboardType } from '../../lib/types';
import ImportButton from '../import/ImportButton';
import SingleTemplate from './SingleTemplate';

type Props = {
	pages: Page[];
	demo: Demo;
	siteTitle: string;
	siteTagline: string;
	siteLogoId: number;
	// currentTheme: string;
	data: TDIDashboardType;
	setData: (value: TDIDashboardType) => void;
};

const Template = ({ pages, demo, siteTitle, siteTagline, siteLogoId, data, setData }: Props) => {
	const { pagebuilder = '' } = useParams();
	const [disabled, setDisabled] = useState(true);
	const [allPages, setAllPages] = useState<PageWithSelection[]>(() => {
		return pages.map((p, index) => {
			return {
				ID: index + 1,
				post_name: p.post_name,
				post_title: p.post_title,
				content: p.content,
				screenshot: p.screenshot,
				isSelected: false,
			};
		});
	});

	const count = allPages.length;

	useEffect(() => {
		const hasSelectedPages = allPages.some((page) => page.isSelected);
		setDisabled(!hasSelectedPages);
	}, [allPages]);

	useEffect(() => {
		const updatedPages = pages.map((p, index) => ({
			ID: p.ID ?? index + 1,
			post_name: p.post_name,
			post_title: p.post_title,
			content: p.content,
			screenshot: p.screenshot,
			isSelected: false,
		}));

		setAllPages(updatedPages);
	}, [pagebuilder, pages]);

	return (
		<div className="w-full bg-[#FAFAFA] p-[25px] sm:p-[32px] shadow absolute bottom-0 box-border border-0 border-t border-solid border-t-[#E9E9E9]">
			<div className="mb-[24px] flex flex-wrap justify-between items-center">
				<div>
					<h4 className="text-[22px] m-0 mb-[8px] text-[#383838]">{demo.name}</h4>
					<p className="text-[#7a7a7a] text-[14px] mt-4 sm:m-0">
						{sprintf(
							__(
								'%s Templates (You can select pages by clicking on templates.)',
								'themegrill-demo-importer',
							),
							count,
						)}
					</p>
				</div>
				<div className="flex flex-wrap gap-[16px]">
					<ImportButton
						buttonTitle={__('Import All', 'themegrill-demo-importer')}
						demo={demo}
						siteTitle={siteTitle}
						siteTagline={siteTagline}
						siteLogoId={siteLogoId}
						additionalStyles="bg-white rounded-[2px] px-[16px] py-[8px] border border-solid border-[#2563EB] text-[#2563EB] font-[600] cursor-pointer"
						textColor="#2563EB"
						data={data}
						setData={setData}
					/>
					<ImportButton
						buttonTitle={__('Import Selected Pages', 'themegrill-demo-importer')}
						pages={allPages}
						demo={demo}
						siteTitle={siteTitle}
						siteTagline={siteTagline}
						siteLogoId={siteLogoId}
						disabled={disabled}
						data={data}
						setData={setData}
					/>
				</div>
			</div>
			<div className="flex gap-[16px] w-full overflow-x-auto tg-overlay-template pb-[20px]">
				{allPages.map((page, index) => (
					<SingleTemplate key={index} page={page} setAllPages={setAllPages} demo={demo} />
				))}
			</div>
		</div>
	);
};

export default Template;
