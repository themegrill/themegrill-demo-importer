import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { useParams } from 'react-router-dom';
import { SearchResultType } from '../../lib/types';
import PagebuilderDropdownMenu from '../dropdown-menu/PagebuilderDropdownMenu';
import LogoUploader from '../uploader/LogoUploader';

type Props = {
	demo: SearchResultType;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	handleSiteTitleChange: (value: string) => void;
	setSiteTagline: (value: string) => void;
	setSiteLogoId: (value: number) => void;
	device: string;
	setDevice: (value: string) => void;
};

const ImportSidebar = ({
	demo,
	iframeRef,
	handleSiteTitleChange,
	setSiteTagline,
	setSiteLogoId,
	device,
	setDevice,
}: Props) => {
	// const {
	// 	theme,
	// 	pagebuilder,
	// 	category,
	// 	plan,
	// 	search,
	// 	searchResults,
	// 	setTheme,
	// 	setPagebuilder,
	// 	setCategory,
	// 	setPlan,
	// 	setSearchResults,
	// } = useDemoContext();
	const { pagebuilder = '' } = useParams();

	const pagebuilders = Object.entries(demo?.pagebuilders || {}).map(([key, value]) => {
		return {
			slug: key,
			value: value,
		};
	});

	const currentPagebuilder = useMemo(() => {
		const pb = pagebuilders.find((p) => p.slug === pagebuilder);
		return pb ? `${pb.value}` : '';
	}, [pagebuilders, pagebuilder]);

	// let currentPagebuilder = '';

	// if (pagebuilder !== 'all') {
	// 	currentPagebuilder = pagebuilders?.find((p) => p.slug === pagebuilder)?.value || '';
	// } else {
	// 	if (pagebuilders.length > 0) {
	// 		currentPagebuilder = pagebuilders[0].value;
	// 	}
	// }

	// useEffect(() => {
	// 	if (pagebuilder === 'all' && pagebuilders.length > 0) {
	// 		setPagebuilder(pagebuilders[0].slug);
	// 	}
	// }, [currentPagebuilder]);

	return (
		<div className="relative max-w-[300px]">
			<div className="tg-full-overlay-sidebar">
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
				{pagebuilders.length > 1 && (
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
				)}
				<LogoUploader iframeRef={iframeRef} setSiteLogoId={setSiteLogoId} />
				<div className="mb-[24px]">
					<h4 className="text-[17px] m-0 mb-4">{__('Site Title', 'themegrill-demo-importer')}</h4>
					<input
						type="text"
						className="border border-solid !border-[#E9E9E9] !px-4 !py-[10px] !rounded-[4px] w-full "
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
			<div className="sticky left-0 bottom-0 w-full p-[24px] flex justify-center gap-[10px] box-border border-0 border-t border-r border-solid border-[#E9E9E9] bg-white">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="21"
					height="20"
					viewBox="0 0 21 20"
					className={`cursor-pointer ${device === 'desktop' ? 'fill-[#2563EB]' : 'fill-[#737373]'}`}
					onClick={() => setDevice('desktop')}
				>
					<path
						d="M16.5001 2.5H4.50008C3.88555 2.49996 3.29582 2.74236 2.85894 3.17456C2.42207 3.60675 2.17333 4.19384 2.16675 4.80833V12.3083C2.16894 12.9265 2.41548 13.5187 2.85259 13.9558C3.2897 14.3929 3.88192 14.6395 4.50008 14.6417H9.66675V15.9667H7.50008C7.27907 15.9667 7.06711 16.0545 6.91083 16.2107C6.75455 16.367 6.66675 16.579 6.66675 16.8C6.66675 17.021 6.75455 17.233 6.91083 17.3893C7.06711 17.5455 7.27907 17.6333 7.50008 17.6333H13.5001C13.7211 17.6333 13.9331 17.5455 14.0893 17.3893C14.2456 17.233 14.3334 17.021 14.3334 16.8C14.3334 16.579 14.2456 16.367 14.0893 16.2107C13.9331 16.0545 13.7211 15.9667 13.5001 15.9667H11.3334V14.65H16.5001C17.1182 14.6478 17.7105 14.4013 18.1476 13.9642C18.5847 13.527 18.8312 12.9348 18.8334 12.3167V4.81667C18.829 4.20072 18.5812 3.6115 18.1441 3.17751C17.707 2.74353 17.116 2.49998 16.5001 2.5ZM17.1667 12.3333C17.1667 12.5101 17.0965 12.6797 16.9715 12.8047C16.8465 12.9298 16.6769 13 16.5001 13H4.50008C4.32327 13 4.1537 12.9298 4.02868 12.8047C3.90365 12.6797 3.83341 12.5101 3.83341 12.3333V4.83333C3.8323 4.74548 3.84877 4.65828 3.88188 4.57689C3.91499 4.4955 3.96405 4.42156 4.02618 4.35943C4.08831 4.2973 4.16225 4.24824 4.24364 4.21513C4.32503 4.18203 4.41222 4.16555 4.50008 4.16667H16.5001C16.5872 4.16556 16.6737 4.18178 16.7546 4.21437C16.8354 4.24696 16.909 4.29527 16.971 4.35652C17.033 4.41776 17.0822 4.49071 17.1159 4.57113C17.1495 4.65155 17.1668 4.73784 17.1667 4.825V12.3333Z"
						fill="current"
					/>
				</svg>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="21"
					height="20"
					viewBox="0 0 21 20"
					className={`cursor-pointer ${device === 'tablet' ? 'fill-[#2563EB]' : 'fill-[#737373]'}`}
					onClick={() => setDevice('tablet')}
				>
					<path
						d="M15.0417 18.3996H5.95835C5.65959 18.3996 5.36376 18.3407 5.08774 18.2264C4.81173 18.1121 4.56093 17.9445 4.34968 17.7333C4.13843 17.522 3.97085 17.2712 3.85652 16.9952C3.74219 16.7192 3.68335 16.4234 3.68335 16.1246V3.99959C3.68335 3.70123 3.74225 3.4058 3.85668 3.13025C3.97111 2.8547 4.13882 2.60445 4.35018 2.39386C4.56155 2.18327 4.81241 2.01649 5.08837 1.90307C5.36434 1.78965 5.65999 1.73183 5.95835 1.73293H15.0417C15.34 1.73183 15.6357 1.78965 15.9117 1.90307C16.1876 2.01649 16.4385 2.18327 16.6498 2.39386C16.8612 2.60445 17.0289 2.8547 17.1433 3.13025C17.2578 3.4058 17.3167 3.70123 17.3167 3.99959V16.1246C17.3167 16.728 17.077 17.3066 16.6504 17.7333C16.2237 18.1599 15.6451 18.3996 15.0417 18.3996ZM5.95835 3.24126C5.75723 3.24126 5.56434 3.32115 5.42213 3.46337C5.27991 3.60558 5.20002 3.79847 5.20002 3.99959V16.1246C5.20002 16.3257 5.27991 16.5186 5.42213 16.6608C5.56434 16.803 5.75723 16.8829 5.95835 16.8829H15.0417C15.2428 16.8829 15.4357 16.803 15.5779 16.6608C15.7201 16.5186 15.8 16.3257 15.8 16.1246V3.99959C15.8 3.79847 15.7201 3.60558 15.5779 3.46337C15.4357 3.32115 15.2428 3.24126 15.0417 3.24126H5.95835ZM11.2583 14.6079C11.2583 14.4068 11.1785 14.2139 11.0362 14.0717C10.894 13.9295 10.7011 13.8496 10.5 13.8496C10.3504 13.8512 10.2046 13.8971 10.081 13.9814C9.9574 14.0657 9.8615 14.1847 9.80538 14.3235C9.74926 14.4622 9.73542 14.6144 9.76562 14.7609C9.79581 14.9075 9.86869 15.0418 9.97507 15.147C10.0814 15.2522 10.2166 15.3237 10.3634 15.3522C10.5103 15.3808 10.6624 15.3653 10.8004 15.3077C10.9385 15.2501 11.0565 15.1529 11.1394 15.0284C11.2224 14.9038 11.2667 14.7576 11.2667 14.6079H11.2583Z"
						fill="current"
					/>
				</svg>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="21"
					height="20"
					viewBox="0 0 21 20"
					className={`cursor-pointer ${device === 'mobile' ? 'fill-[#2563EB]' : 'fill-[#737373]'}`}
					onClick={() => setDevice('mobile')}
				>
					<path
						d="M14.2919 18.3332H6.70858C6.10665 18.331 5.53014 18.0903 5.10529 17.6639C4.68045 17.2375 4.44191 16.6601 4.44191 16.0582V3.9415C4.44081 3.64314 4.49863 3.3475 4.61205 3.07153C4.72547 2.79556 4.89226 2.5447 5.10285 2.33334C5.31344 2.12198 5.56368 1.95427 5.83923 1.83984C6.11478 1.72541 6.41021 1.6665 6.70858 1.6665H14.2919C14.8931 1.6665 15.4696 1.90531 15.8947 2.3304C16.3198 2.75548 16.5586 3.33201 16.5586 3.93317V16.0665C16.5564 16.667 16.3169 17.2422 15.8923 17.6669C15.4677 18.0915 14.8924 18.331 14.2919 18.3332ZM6.70858 3.1915C6.50966 3.1915 6.3189 3.27052 6.17825 3.41117C6.03759 3.55183 5.95858 3.74259 5.95858 3.9415V16.0665C5.95747 16.1657 5.97606 16.2641 6.01326 16.3561C6.05045 16.448 6.10552 16.5317 6.17528 16.6022C6.24503 16.6728 6.32808 16.7287 6.41963 16.767C6.51117 16.8052 6.60938 16.8248 6.70858 16.8248H14.2919C14.3911 16.8248 14.4893 16.8052 14.5809 16.767C14.6724 16.7287 14.7555 16.6728 14.8252 16.6022C14.895 16.5317 14.95 16.448 14.9872 16.3561C15.0244 16.2641 15.043 16.1657 15.0419 16.0665V3.9415C15.0419 3.74259 14.9629 3.55183 14.8222 3.41117C14.6816 3.27052 14.4908 3.1915 14.2919 3.1915H6.70858ZM11.2669 14.5498C11.2669 14.3487 11.187 14.1558 11.0448 14.0136C10.9026 13.8714 10.7097 13.7915 10.5086 13.7915C10.359 13.7931 10.2132 13.839 10.0896 13.9233C9.96596 14.0077 9.87006 14.1267 9.81394 14.2654C9.75782 14.4041 9.74398 14.5563 9.77418 14.7028C9.80437 14.8494 9.87725 14.9837 9.98362 15.0889C10.09 15.1942 10.2251 15.2656 10.372 15.2942C10.5189 15.3227 10.6709 15.3072 10.809 15.2496C10.9471 15.192 11.065 15.0948 11.148 14.9703C11.231 14.8457 11.2752 14.6995 11.2752 14.5498H11.2669Z"
						fill="current"
					/>
				</svg>
			</div>
		</div>
	);
};

export default ImportSidebar;
