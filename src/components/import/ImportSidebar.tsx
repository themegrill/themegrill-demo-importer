import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { useDemoContext } from '../../context';
import { SearchResultType } from '../../lib/types';
import PagebuilderDropdownMenu from '../dropdown-menu/PagebuilderDropdownMenu';
import LogoUploader from '../uploader/LogoUploader';

type Props = {
	demo: SearchResultType;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	handleSiteTitleChange: (value: string) => void;
	setSiteTagline: (value: string) => void;
	setSiteLogoId: (value: number) => void;
};

const ImportSidebar = ({
	demo,
	iframeRef,
	handleSiteTitleChange,
	setSiteTagline,
	setSiteLogoId,
}: Props) => {
	const {
		theme,
		pagebuilder,
		category,
		plan,
		search,
		searchResults,
		setTheme,
		setPagebuilder,
		setCategory,
		setPlan,
		setSearchResults,
	} = useDemoContext();

	const pagebuilders = Object.entries(demo?.pagebuilders || {}).map(([key, value]) => {
		return {
			slug: key,
			value: value,
		};
	});

	let currentPagebuilder = '';

	if (pagebuilder !== 'all') {
		currentPagebuilder = pagebuilders?.find((p) => p.slug === pagebuilder)?.value || '';
	} else {
		if (pagebuilders.length > 0) {
			currentPagebuilder = pagebuilders[0].value;
		}
	}

	useEffect(() => {
		if (pagebuilder === 'all' && pagebuilders.length > 0) {
			setPagebuilder(pagebuilders[0].slug);
		}
	}, [currentPagebuilder]);

	return (
		<div className="tg-full-overlay-sidebar relative">
			<div>
				<h4 className="text-[20px] m-0 mb-[12px] text-[#383838]">
					{__('Customize Your Site', 'themegrill-demo-importer')}
				</h4>
				<p className="text-[#6b6b6b] font-[350] text-[14px] m-0">
					<i>
						{__(
							'Personalize your site by exploring alternative colors and fonts.',
							'themegrill-demo-importer',
						)}
					</i>
				</p>
			</div>
			<hr className="mt-[24px] border-b-[#EDEDED]" />
			<div className="my-[24px]">
				<h4 className="text-[17px] mb-[16px] text-[#383838]">
					{__('Choose Builder', 'themegrill-demo-importer')}
				</h4>
				<PagebuilderDropdownMenu
					pagebuilders={pagebuilders}
					currentPagebuilder={currentPagebuilder}
					isSidebar={true}
				/>
			</div>
			<LogoUploader iframeRef={iframeRef} setSiteLogoId={setSiteLogoId} />
			<div className="mb-[24px]">
				<h4 className="text-[17px] m-0 mb-4">{__('Site Title', 'themegrill-demo-importer')}</h4>
				<input
					type="text"
					className="border border-solid !border-[#E9E9E9] !px-4 !py-[10px] !rounded-[4px] w-full"
					placeholder="Enter Site Title"
					onChange={(e) => handleSiteTitleChange(e.target.value)}
				/>
			</div>
			<div className="mb-[24px]">
				<h4 className="text-[17px] m-0 mb-4">{__('Site Tagline', 'themegrill-demo-importer')}</h4>
				<input
					type="text"
					className="border border-solid !border-[#E9E9E9] !px-4 !py-[10px] !rounded-[4px] w-full"
					placeholder="Enter Site Tagline"
					onChange={(e) => setSiteTagline(e.target.value)}
				/>
			</div>
		</div>
	);
};

export default ImportSidebar;
