import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import Lottie from 'lottie-react';
import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import spinner from '../../assets/animation/spinner.json';
import { themes } from '../../lib/themes';
import { __TDI_DASHBOARD__, Demo, TDIDashboardType } from '../../lib/types';
import Template from '../template/Template';
import ImportButton from './ImportButton';

type Props = {
	demo: Demo;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	siteTitle: string;
	siteTagline: string;
	siteLogoId: number;
	// currentTheme: string;
	// zakraProInstalled: boolean;
	// zakraProActivated: boolean;
	data: TDIDashboardType;
	setData: (value: TDIDashboardType) => void;
	device: string;
};

const ImportContent = ({
	demo,
	iframeRef,
	siteTitle,
	siteTagline,
	siteLogoId,
	// currentTheme,
	// zakraProActivated,
	// zakraProInstalled,
	data,
	setData,
	device,
}: Props) => {
	const navigate = useNavigate();
	// const {
	// 	pagebuilder,
	// 	setPagebuilder,
	// 	theme,
	// 	setTheme,
	// 	setCategory,
	// 	currentTheme,
	// 	setCurrentTheme,
	// 	zakraProInstalled,
	// 	zakraProActivated,
	// } = useDemoContext();
	// const { data, setData } = useLocalizedData();
	// const {
	// 	current_theme: currentTheme,
	// 	zakra_pro_installed: zakraProInstalled,
	// 	zakra_pro_activated: zakraProActivated,
	// } = data || {};
	const { pagebuilder = '' } = useParams();
	const [isIframeLoading, setIsIframeLoading] = useState(true);
	const [deviceClass, setDeviceClass] = useState('');
	const [collapseTemplate, setCollapseTemplate] = useState(false);
	const [isActivating, setIsActivating] = useState(false);
	const count = demo?.pagebuilder_data[pagebuilder]?.pages.length || 0;
	const matchedTheme = themes.find((theme) => theme.slug === demo.theme_slug);

	const location = useLocation();

	const handleExitClick = (currentTheme: string) => {
		const baseTheme = currentTheme.endsWith('-pro')
			? currentTheme.replace('-pro', '')
			: currentTheme;
		const activeTheme = baseTheme === demo.theme_slug ? baseTheme : 'all';

		// setTheme(activeTheme);
		// setPagebuilder('all');
		// setCategory('all');

		const newParams = new URLSearchParams({
			theme: activeTheme,
			category: 'all',
			pagebuilder: 'all',
		});

		window.location.hash = `/?${newParams.toString()}`;
		// navigate(-1);
	};

	const handleClick = (collapse: Boolean) => {
		setCollapseTemplate(!collapse);
	};

	const checkThemeExists = (demo: Demo) => {
		const proTheme = demo.theme_slug + '-pro';
		if (demo.theme_slug === 'zakra') {
			if (data.zakra_pro_installed) {
				return true;
			}
			return false;
		}
		const themeExists = __TDI_DASHBOARD__.installed_themes.includes(proTheme);
		return themeExists;
	};

	const activatePro = async (slug: string) => {
		setIsActivating(true);
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
			const updated = await apiFetch<TDIDashboardType>({
				path: '/tg-demo-importer/v1/localized-data',
			});
			setData(updated);
			setIsActivating(false);
		}
	};

	const renderImportSection = () =>
		collapseTemplate ? (
			<>
				<button
					type="button"
					className="leading-none bg-white rounded-full px-[16px] py-[8px] border border-solid border-[#E9E9E9] cursor-pointer absolute bottom-[500px] sm:bottom-[364px] left-[50%]"
					onClick={() => handleClick(collapseTemplate)}
					style={{ zIndex: 100, boxShadow: '0px 4px 8px 0px rgba(0, 0, 0, 0.15)' }}
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
					siteTitle={siteTitle}
					siteTagline={siteTagline}
					siteLogoId={siteLogoId}
					data={data}
					setData={setData}
				/>
			</>
		) : (
			<>
				<div
					className="absolute bottom-0 box-border w-full border-0 border-t border-t-[#E1E1E1] border-solid flex flex-wrap justify-between items-center bg-white px-[32px] py-[24px] gap-[24px]"
					style={{ boxShadow: '0px -8px 25px 0px rgba(0, 0, 0, 0.04)' }}
				>
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
							demo={demo}
							siteTitle={siteTitle}
							siteTagline={siteTagline}
							siteLogoId={siteLogoId}
							data={data}
							setData={setData}
						/>
						<button
							className="bg-white rounded-[2px] px-[16px] py-[8px] border border-solid border-[#2563EB] text-[#2563EB] font-[600] cursor-pointer"
							onClick={() => handleClick(false)}
						>
							{__('Select Pages', 'themegrill-demo-importer')}
						</button>
					</div>
				</div>

				<button
					type="button"
					className="leading-none bg-[#1E1E1E] rounded-full px-[16px] py-[8px] border border-solid border-[#E1E1E1] cursor-pointer absolute bottom-20 sm:left-[50%] shadow-lg"
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
							d="M7 10.8188V3.18248"
							stroke="white"
							strokeWidth="1.09091"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
						<path
							d="M3.182 7.00049L7.00018 3.18231L10.8184 7.00049"
							stroke="white"
							strokeWidth="1.09091"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</button>
			</>
		);

	useEffect(() => {
		if (device === 'desktop') {
			setDeviceClass('w-full');
		} else if (device === 'tablet') {
			setDeviceClass('w-[768px]');
		} else if (device === 'mobile') {
			setDeviceClass('w-[420px]');
		}
	}, [device]);

	useEffect(() => {
		setIsIframeLoading(true);
	}, [pagebuilder]);

	return (
		<div className="tg-full-overlay-content bg-[#f4f4f4] w-full relative">
			<button
				type="button"
				className="bg-[#0E0E0E] rounded-full px-[18px] py-[10px] border border-solid border-[#0E0E0E] cursor-pointer absolute top-[32px] left-[32px] flex items-center gap-[8px]"
				style={{ boxShadow: '0px 8px 10px 0px rgba(0, 0, 0, 0.04)' }}
				onClick={() => handleExitClick(data.current_theme)}
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
				<span className="text-white font-[600] text-[15px]">
					{__('Exit', 'themegrill-demo-importer')}
				</span>
			</button>

			{isIframeLoading && (
				<div
					style={{
						display: 'flex',
						justifyContent: 'center',
						alignItems: 'center',
						height: '200px',
					}}
				>
					<p>Loading iframe...</p>
				</div>
			)}
			<iframe
				ref={iframeRef}
				src={demo.pagebuilder_data[pagebuilder]?.url}
				title={`${demo.name} Preview`}
				className={`h-full ml-auto mr-auto ${deviceClass}`}
				style={{ display: isIframeLoading ? 'none' : 'block' }}
				onLoad={() => setIsIframeLoading(false)}
			></iframe>
			{demo.pro || demo.premium ? (
				checkThemeExists(demo) ? (
					(
						demo.theme_slug === 'zakra'
							? data.zakra_pro_activated
							: demo.theme_slug + '-pro' === data.current_theme
					) ? (
						renderImportSection()
					) : (
						<div className="w-full bg-[#3F76ED] px-[20px] py-[11px] shadow absolute bottom-0 box-border flex flex-wrap justify-center items-center">
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="23"
								height="22"
								viewBox="0 0 23 22"
								fill="none"
							>
								<path
									d="M11.0988 2.99362C11.1384 2.92176 11.1965 2.86184 11.2671 2.8201C11.3377 2.77836 11.4183 2.75635 11.5003 2.75635C11.5823 2.75635 11.6629 2.77836 11.7335 2.8201C11.8041 2.86184 11.8622 2.92176 11.9018 2.99362L14.6078 8.13062C14.6723 8.24957 14.7624 8.35276 14.8715 8.43277C14.9807 8.51279 15.1062 8.56765 15.239 8.59341C15.3719 8.61917 15.5088 8.6152 15.6399 8.58178C15.7711 8.54837 15.8932 8.48633 15.9975 8.40012L19.9181 5.04146C19.9933 4.98024 20.0861 4.94449 20.1829 4.93934C20.2798 4.93419 20.3758 4.95991 20.4571 5.01281C20.5385 5.0657 20.6009 5.14303 20.6355 5.23367C20.6701 5.32431 20.675 5.42359 20.6496 5.5172L18.0517 14.9094C17.9987 15.1016 17.8845 15.2712 17.7263 15.3926C17.5682 15.5141 17.3748 15.5806 17.1754 15.5822H5.82614C5.62661 15.5808 5.43299 15.5144 5.27467 15.3929C5.11634 15.2715 5.00196 15.1017 4.94889 14.9094L2.35197 5.51812C2.32654 5.4245 2.33146 5.32523 2.36604 5.23459C2.40061 5.14395 2.46306 5.06661 2.54438 5.01372C2.62571 4.96083 2.72172 4.93511 2.81859 4.94026C2.91547 4.9454 3.00821 4.98116 3.08347 5.04237L7.00314 8.40104C7.10746 8.48724 7.22956 8.54928 7.3607 8.5827C7.49183 8.61612 7.62874 8.62009 7.76159 8.59433C7.89444 8.56856 8.01994 8.5137 8.12908 8.43369C8.23821 8.35367 8.32828 8.25049 8.39281 8.13154L11.0988 2.99362Z"
									fill="#FFB81C"
								/>
								<path
									d="M5.08398 19.25H17.9173"
									stroke="#FFB81C"
									strokeWidth="1.83333"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
							<p className="text-[#fff] text-[14px] m-0 ml-[6px] mr-[12px]">
								{__(
									'This template is only available with a Pro subscription. Activate Pro to access this and other premium templates !',
									'themegrill-demo-importer',
								)}
							</p>
							{isActivating ? (
								<button className="bg-[#fff]/90 text-[#2563EB] border-0 rounded px-[8px] py-[5px] text-[13px] font-[600] no-underline capitalize flex items-center gap-[4px] cursor-not-allowed">
									<Lottie animationData={spinner} loop={true} autoplay={true} className="h-4" />
									{__('Activating...', 'themegrill-demo-importer')}
								</button>
							) : (
								<button
									className="cursor-pointer bg-[#fff] text-[#2563EB] border-0 rounded px-[8px] py-[5px] text-[13px] font-[600] no-underline capitalize flex items-center gap-[4px]"
									onClick={() => activatePro(demo.theme_slug)}
								>
									{__('Activate Pro', 'themegrill-demo-importer')}
								</button>
							)}
						</div>
					)
				) : (
					<div className="w-full bg-[#3F76ED] px-[20px] py-[11px] absolute bottom-0 box-border flex flex-wrap justify-center items-center shadow-custom-top">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="23"
							height="22"
							viewBox="0 0 23 22"
							fill="none"
						>
							<path
								d="M11.0988 2.99362C11.1384 2.92176 11.1965 2.86184 11.2671 2.8201C11.3377 2.77836 11.4183 2.75635 11.5003 2.75635C11.5823 2.75635 11.6629 2.77836 11.7335 2.8201C11.8041 2.86184 11.8622 2.92176 11.9018 2.99362L14.6078 8.13062C14.6723 8.24957 14.7624 8.35276 14.8715 8.43277C14.9807 8.51279 15.1062 8.56765 15.239 8.59341C15.3719 8.61917 15.5088 8.6152 15.6399 8.58178C15.7711 8.54837 15.8932 8.48633 15.9975 8.40012L19.9181 5.04146C19.9933 4.98024 20.0861 4.94449 20.1829 4.93934C20.2798 4.93419 20.3758 4.95991 20.4571 5.01281C20.5385 5.0657 20.6009 5.14303 20.6355 5.23367C20.6701 5.32431 20.675 5.42359 20.6496 5.5172L18.0517 14.9094C17.9987 15.1016 17.8845 15.2712 17.7263 15.3926C17.5682 15.5141 17.3748 15.5806 17.1754 15.5822H5.82614C5.62661 15.5808 5.43299 15.5144 5.27467 15.3929C5.11634 15.2715 5.00196 15.1017 4.94889 14.9094L2.35197 5.51812C2.32654 5.4245 2.33146 5.32523 2.36604 5.23459C2.40061 5.14395 2.46306 5.06661 2.54438 5.01372C2.62571 4.96083 2.72172 4.93511 2.81859 4.94026C2.91547 4.9454 3.00821 4.98116 3.08347 5.04237L7.00314 8.40104C7.10746 8.48724 7.22956 8.54928 7.3607 8.5827C7.49183 8.61612 7.62874 8.62009 7.76159 8.59433C7.89444 8.56856 8.01994 8.5137 8.12908 8.43369C8.23821 8.35367 8.32828 8.25049 8.39281 8.13154L11.0988 2.99362Z"
								fill="#FFB81C"
							/>
							<path
								d="M5.08398 19.25H17.9173"
								stroke="#FFB81C"
								strokeWidth="1.83333"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
						<p className="text-[#fff] text-[14px] m-0 ml-[6px] mr-[12px]">
							{__(
								'This template is only available with a Pro subscription. Upgrade to Pro to access this and other premium templates !',
								'themegrill-demo-importer',
							)}
						</p>
						<a
							href={matchedTheme?.pricing_link ?? '#'}
							target="_blank"
							rel="noopener noreferrer"
							className="cursor-pointer bg-[#fff] text-[#2563EB] border-0 rounded px-[8px] py-[5px] text-[13px] font-[600] no-underline capitalize flex items-center gap-[4px] visited:text-[#2563EB]"
						>
							{__('Upgrade Now', 'themegrill-demo-importer')}
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="16"
								height="16"
								viewBox="0 0 16 16"
								fill="none"
							>
								<path
									d="M4.875 4.875H11.125V11.125"
									stroke="#2563EB"
									strokeWidth="1.25"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
								<path
									d="M4.875 11.125L11.125 4.875"
									stroke="#2563EB"
									strokeWidth="1.25"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</a>
					</div>
				)
			) : (
				renderImportSection()
			)}
		</div>
	);
};

export default ImportContent;
