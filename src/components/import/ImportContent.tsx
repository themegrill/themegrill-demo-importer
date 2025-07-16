import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDemoContext } from '../../context';
import { themes } from '../../lib/themes';
import { __TDI_DASHBOARD__, SearchResultType, TDIDashboardType } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';
import Template from '../template/Template';
import ImportButton from './ImportButton';

type Props = {
	demo: SearchResultType;
	initialTheme: string;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	siteTitle: string;
	siteTagline: string;
	siteLogoId: number;
};

const ImportContent = ({
	demo,
	initialTheme,
	iframeRef,
	siteTitle,
	siteTagline,
	siteLogoId,
}: Props) => {
	const navigate = useNavigate();
	const {
		pagebuilder,
		setPagebuilder,
		theme,
		setTheme,
		setCategory,
		currentTheme,
		setCurrentTheme,
		zakraProInstalled,
		zakraProActivated,
	} = useDemoContext();
	const [collapseTemplate, setCollapseTemplate] = useState(false);
	// const [currentTheme, setCurrentTheme] = useState(__TDI_DASHBOARD__.current_theme);
	const count = demo.pagebuilder_data[pagebuilder]?.pages.length || 0;
	const matchedTheme = themes.find((theme) => theme.slug === demo.theme);
	const { setData } = useLocalizedData();

	const handleExitClick = (currentTheme: string) => {
		const baseTheme = currentTheme.endsWith('-pro')
			? currentTheme.replace('-pro', '')
			: currentTheme;
		const activeTab = baseTheme === demo.theme ? baseTheme : 'all';

		setTheme(activeTab);
		setPagebuilder('all');
		setCategory('all');

		const newParams = new URLSearchParams({
			tab: activeTab,
			category: 'all',
			pagebuilder: 'all',
		});

		window.location.hash = `/?${newParams.toString()}`;
	};

	const handleClick = (collapse: Boolean) => {
		setCollapseTemplate(!collapse);
	};

	const checkThemeExists = (demo: SearchResultType) => {
		const proTheme = demo.theme + '-pro';
		if (demo.theme === 'zakra') {
			if (zakraProInstalled) {
				return true;
			}
			return false;
		}
		const themeExists = __TDI_DASHBOARD__.installed_themes.includes(proTheme);
		return themeExists;
	};

	const activatePro = async (slug: string) => {
		const proSlug = slug + '-pro';
		const response = await apiFetch<{
			success: boolean;
			message: string;
		}>({
			path: 'tg-demo-importer/v1/activate-pro',
			method: 'POST',
			data: {
				slug: proSlug,
			},
		});
		if (response.success) {
			setCurrentTheme(proSlug);
			const updated = await apiFetch<TDIDashboardType>({
				path: '/tg-demo-importer/v1/localized-data',
			});
			setData(updated);
		}
	};

	const renderImportSection = () =>
		collapseTemplate ? (
			<>
				<button
					type="button"
					className="bg-white rounded-full px-[16px] py-[4px] border border-solid border-[#f4f4f4] cursor-pointer absolute bottom-[400px] sm:bottom-[350px] left-[50%] shadow"
					onClick={() => handleClick(collapseTemplate)}
					style={{ zIndex: 100 }}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="12"
						height="12"
						viewBox="0 0 12 12"
						fill="none"
					>
						<path d="M6 2.5V9.5" stroke="#383838" strokeLinecap="round" strokeLinejoin="round" />
						<path
							d="M9.5 6L6 9.5L2.5 6"
							stroke="#383838"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</button>
				<Template
					pages={demo.pagebuilder_data[pagebuilder]?.pages || []}
					demo={demo}
					initialTheme={initialTheme}
					siteTitle={siteTitle}
					siteTagline={siteTagline}
					siteLogoId={siteLogoId}
				/>
			</>
		) : (
			<>
				<div
					className="absolute bottom-0 w-full border-0 border-t border-t-[#E1E1E1] border-solid"
					style={{ boxShadow: '0px -8px 25px 0px rgba(0, 0, 0, 0.04)' }}
				>
					<div className="flex flex-wrap justify-between items-center bg-white px-[32px] py-[24px]">
						<div>
							<h4 className="text-[22px] m-0 mb-[8px] text-[#383838]">{demo.name}</h4>
							<p className="text-[#7a7a7a] text-[14px] mt-4 sm:m-0">
								{sprintf(
									__(
										'%s Templates (You can select pages manually by clicking on templates.)',
										'themegrill-demo-importer',
									),
									count,
								)}
							</p>
						</div>
						<div className=" flex flex-wrap gap-[16px]">
							<ImportButton
								buttonTitle="Import All"
								initialTheme={initialTheme}
								demo={demo}
								siteTitle={siteTitle}
								siteTagline={siteTagline}
								siteLogoId={siteLogoId}
							/>
							<button
								className="bg-white rounded-[2px] px-[16px] py-[8px] border border-solid border-[#2563EB] text-[#2563EB] font-[600] cursor-pointer"
								onClick={() => handleClick(false)}
							>
								{__('Select Pages', 'themegrill-demo-importer')}
							</button>
						</div>
					</div>
				</div>

				<button
					type="button"
					className="bg-[#1E1E1E] rounded-full px-[18px] py-[8px] border border-solid border-[#E1E1E1] cursor-pointer absolute bottom-20 left-[5%] sm:left-[50%] sm:translate-x-[-50%]"
					onClick={() => handleClick(collapseTemplate)}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="14"
						height="14"
						viewBox="0 0 14 14"
						fill="none"
					>
						<path
							d="M7 10.8182V3.18187"
							stroke="white"
							strokeWidth="1.09091"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
						<path
							d="M3.18187 7.00006L7.00006 3.18188L10.8182 7.00006"
							stroke="white"
							strokeWidth="1.09091"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</button>
			</>
		);

	return (
		<div className="tg-full-overlay-content bg-[#f4f4f4] w-full relative">
			<button
				type="button"
				className="bg-[#0E0E0E] rounded-full px-[16px] py-[8px] border border-solid border-[#0E0E0E] cursor-pointer absolute top-[32px] left-[32px]"
				style={{ boxShadow: '0px 8px 10px 0px rgba(0, 0, 0, 0.04)' }}
				onClick={() => handleExitClick(currentTheme)}
			>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="12"
					height="12"
					viewBox="0 0 12 12"
					fill="none"
				>
					<g clipPath="url(#clip0_3876_7854)">
						<path
							d="M11.1423 5.46664L2.23373 5.46664L6.40516 1.57864C6.45844 1.52891 6.5007 1.46988 6.52953 1.40491C6.55837 1.33994 6.57321 1.2703 6.57321 1.19997C6.57321 1.12965 6.55837 1.06001 6.52953 0.995042C6.5007 0.93007 6.45844 0.871035 6.40516 0.821308C6.35188 0.771581 6.28863 0.732135 6.21901 0.705223C6.1494 0.678311 6.07479 0.664459 5.99944 0.664459C5.84727 0.664459 5.70133 0.720879 5.59373 0.821308L0.45087 5.62131C0.397955 5.67137 0.35705 5.73136 0.33087 5.79731C0.30159 5.86143 0.286059 5.93028 0.285156 5.99997C0.286584 6.07134 0.30208 6.14185 0.33087 6.20797C0.360042 6.2693 0.400676 6.32528 0.45087 6.37331L5.59373 11.1733C5.64685 11.2233 5.71005 11.263 5.77968 11.2901C5.84932 11.3171 5.92401 11.3311 5.99944 11.3311C6.07488 11.3311 6.14957 11.3171 6.2192 11.2901C6.28883 11.263 6.35203 11.2233 6.40516 11.1733C6.45871 11.1237 6.50123 11.0647 6.53024 10.9997C6.55925 10.9348 6.57418 10.865 6.57418 10.7946C6.57418 10.7242 6.55925 10.6545 6.53024 10.5895C6.50123 10.5245 6.45871 10.4656 6.40516 10.416L2.23373 6.53331L11.1423 6.53331C11.2939 6.53331 11.4392 6.47712 11.5464 6.3771C11.6535 6.27708 11.7137 6.14142 11.7137 5.99997C11.7137 5.85853 11.6535 5.72287 11.5464 5.62285C11.4392 5.52283 11.2939 5.46664 11.1423 5.46664Z"
							fill="white"
						/>
					</g>
					<defs>
						<clipPath id="clip0_3876_7854">
							<rect width="12" height="12" fill="white" />
						</clipPath>
					</defs>
				</svg>
				<span className="ml-[8px] text-white font-[600]">
					{__('Exit', 'themegrill-demo-importer')}
				</span>
			</button>

			<iframe
				ref={iframeRef}
				src={demo.url}
				title={`${demo.name} Preview`}
				className="w-full h-full"
			></iframe>

			{demo.pro || demo.premium ? (
				checkThemeExists(demo) ? (
					(demo.theme === 'zakra' ? zakraProActivated : demo.theme + '-pro' === currentTheme) ? (
						renderImportSection()
					) : (
						<div className="h-[120px] sm:h-[120px] w-full bg-white p-[25px] sm:p-[32px] shadow absolute bottom-0 box-border">
							<div className="mb-[24px] flex flex-wrap justify-between items-center">
								<div>
									<h4 className="text-[22px] m-0 mb-[8px] text-[#383838]">{demo.name}</h4>
									<p className="text-[#7a7a7a] text-[14px] mt-4 sm:m-0">
										{__('This is pro demo.', 'themegrill-demo-importer')}
									</p>
								</div>
								<div className="flex flex-wrap gap-[16px]">
									<button
										className="cursor-pointer bg-[#2563EB] text-white border-0 rounded p-[16px] text-[16px] font-[600] no-underline capitalize hover:text-white visited:text-white"
										onClick={() => activatePro(demo.theme)}
									>
										{__('Activate Pro', 'themegrill-demo-importer')}
									</button>
								</div>
							</div>
						</div>
					)
				) : (
					<div className="h-[50px] sm:h-[50px] w-full bg-white p-[25px] sm:p-[32px] shadow absolute bottom-0 ">
						<div className="mb-[24px] flex flex-wrap justify-between items-center">
							<div>
								<h4 className="text-[22px] m-0 mb-[8px] text-[#383838]">{demo.name}</h4>
								<p className="text-[#7a7a7a] text-[14px] mt-4 sm:m-0">
									{__('This is pro demo.', 'themegrill-demo-importer')}
								</p>
							</div>
							<div className="mr-[70px] flex flex-wrap gap-[16px]">
								<a
									href={matchedTheme?.pricing_link ?? '#'}
									target="_blank"
									rel="noopener noreferrer"
									className="cursor-pointer bg-[#2563EB] text-white border-0 rounded p-[16px] text-[16px] w-[80%] font-[600] no-underline capitalize hover:text-white visited:text-white"
								>
									{__('Upgrade to Pro', 'themegrill-demo-importer')}
								</a>
							</div>
						</div>
					</div>
				)
			) : (
				renderImportSection()
			)}
		</div>
	);
};

export default ImportContent;
