import apiFetch from '@wordpress/api-fetch';
import React, { useState } from 'react';
import { useDemoContext } from '../../context';
import { Dialog, DialogContent, DialogTrigger } from '../../controls/Dialog';
import { __TDI_DASHBOARD__, PageWithSelection, SearchResultType } from '../../lib/types';
import { DialogConsent } from './DialogConsent';
import DialogImported from './DialogImported';
import DialogImporting from './DialogImporting';

type Props = {
	buttonTitle: string;
	pages?: PageWithSelection[];
	initialTheme: string;
	demo: SearchResultType;
	siteTitle: string;
	siteTagline: string;
	siteLogoId: number;
	additionalStyles?: string;
	textColor?: string;
	disabled?: boolean;
};

const IMPORT_ACTIONS = {
	'install-theme': {
		progressWeight: 10,
		stepTitle: 'Install Theme',
		stepSubTitle: 'Installing theme...',
	},
	'install-plugins': {
		progressWeight: 15,
		stepTitle: 'Install Plugins',
		stepSubTitle: 'Installing required plugins...',
	},
	'import-content': {
		progressWeight: 50,
		stepTitle: 'Import Content',
		stepSubTitle: 'Importing content i.e. posts, pages, menus, media etc.',
	},
	'import-customizer': {
		progressWeight: 10,
		stepTitle: 'Import Customizer Settings',
		stepSubTitle: 'Importing customizer and site settings...',
	},
	'import-widgets': {
		progressWeight: 10,
		stepTitle: 'Import Widget Settings',
		stepSubTitle: 'Importing widgets...',
	},
	complete: {
		progressWeight: 100,
		stepTitle: 'Finalize Setup',
		stepSubTitle: 'Completing setup and finalizing settings... ',
	},
};

const ImportButton = ({
	buttonTitle,
	initialTheme,
	demo,
	siteTitle,
	siteTagline,
	siteLogoId,
	additionalStyles,
	textColor,
	disabled,
	pages,
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

	const pluginsList = [
		{
			slug: 'evf',
			content: 'Everest Form',
			activeContent: 'Everest Form Description',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: false,
			plugin: 'everest-forms/everest-forms.php',
		},
		{
			slug: 'woocommerce',
			content: 'Install Woocommerce',
			activeContent: 'Woocommerce Description',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: false,
			plugin: 'woocommerce/woocommerce.php',
		},
	];

	// const [step, setStep] = useState(0);
	const [plugins, setPlugins] = useState(pluginsList);
	const [installTheme, setInstallTheme] = useState(true);
	const [importProgress, setImportProgress] = useState(0);
	const [importProgressStepTitle, setImportProgressStepTitle] = useState('Start import');
	const [importProgressStepSubTitle, setImportProgressStepSubTitle] =
		useState('Initializing import...');
	const [importAction, setImportAction] = useState<null | keyof typeof IMPORT_ACTIONS>(null);

	const totalPagebuilders = Object.entries(demo?.pagebuilders)?.length ?? 0;

	// const STEP: Array<{
	// 	header: React.ReactNode;
	// 	content: React.FunctionComponent;
	// 	footer: React.FunctionComponent | null;
	// }> = [
	// 	{
	// 		header: 'Things to know',
	// 		content: () => (
	// 			<>
	// 				<p className="text-[15px]/[25px] sm:text-[15px] m-0 mb-[32px]">
	// 					Importing demo data ensures your site looks like the theme demo. You can simply modify
	// 					the content instead of starting everything from scratch.
	// 				</p>
	// 				<div>
	// 					<h3 className="text-[16px] text-[#383838]">Install and Activate</h3>
	// 					<div className="my-[20px]">
	// 						{demo.theme !== initialTheme && (
	// 							<div className="flex items-center justify-between bg-[#FAFBFF] border border-solid border-[#EBEBEB] rounded px-[16px] py-[18px] mb-[16px]">
	// 								<div className="flex items-center gap-[10px] capitalize">
	// 									<svg
	// 										xmlns="http://www.w3.org/2000/svg"
	// 										width="38"
	// 										height="38"
	// 										viewBox="0 0 38 38"
	// 										fill="none"
	// 									>
	// 										<path
	// 											d="M37.9996 18.9998C37.9996 8.5065 29.4931 0 18.9998 0C8.5065 0 0 8.5065 0 18.9998C0 29.4931 8.5065 37.9996 18.9998 37.9996C29.4931 37.9996 37.9996 29.4931 37.9996 18.9998Z"
	// 											fill="#004846"
	// 										/>
	// 										<path
	// 											d="M24.6534 11.0835C24.3563 11.0832 24.0621 11.1415 23.7877 11.2552C23.5132 11.3689 23.264 11.5358 23.0542 11.7461L11.7446 23.055C11.3204 23.4792 11.082 24.0546 11.082 24.6545C11.082 25.2544 11.3204 25.8298 11.7446 26.254C12.1688 26.6783 12.7442 26.9166 13.3441 26.9166C13.9441 26.9166 14.5195 26.6783 14.9437 26.254L26.2525 14.9444C26.5688 14.6281 26.7841 14.2251 26.8714 13.7864C26.9586 13.3477 26.9138 12.893 26.7427 12.4798C26.5715 12.0665 26.2817 11.7133 25.9098 11.4648C25.5379 11.2162 25.1007 11.0836 24.6534 11.0835Z"
	// 											fill="white"
	// 										/>
	// 										<path
	// 											d="M11.8861 15.0724C12.1086 15.2509 12.3897 15.34 12.6745 15.3223C12.9592 15.3046 13.2271 15.1813 13.4258 14.9766L17.305 11.0975V11.0833H13.4393C12.8354 11.074 12.2516 11.3002 11.8117 11.714C11.3718 12.1278 11.1102 12.6966 11.0825 13.2999C11.0755 13.6366 11.144 13.9706 11.283 14.2774C11.4221 14.5841 11.6282 14.8558 11.8861 15.0724Z"
	// 											fill="white"
	// 										/>
	// 										<path
	// 											d="M24.6432 22.951L20.6777 26.9164H24.5648C25.166 26.9247 25.747 26.6992 26.1852 26.2874C26.6234 25.8757 26.8845 25.3099 26.9136 24.7093C26.9227 24.3405 26.8412 23.9751 26.6763 23.6451C26.5114 23.3151 26.268 23.0305 25.9676 22.8164C25.7663 22.6794 25.5231 22.6178 25.2809 22.6424C25.0386 22.667 24.8128 22.7763 24.6432 22.951Z"
	// 											fill="white"
	// 										/>
	// 									</svg>
	// 									<div>
	// 										<h6 className="text-[15px] text-[#111] m-0 mb-[4px]">{demo.theme} Theme</h6>
	// 										<p className="text-[13px] text-[#383838] tracking-[0.13px] m-0">
	// 											Powerful Multipurpose WordPress Theme
	// 										</p>
	// 									</div>
	// 								</div>
	// 								<label className="flex items-center cursor-pointer relative">
	// 									<input
	// 										type="checkbox"
	// 										id="import-toggle"
	// 										className="sr-only"
	// 										value="1"
	// 										checked={installTheme}
	// 										onChange={() => setInstallTheme(!installTheme)}
	// 									/>
	// 									<div className="toggle-bg bg-white border border-solid border-[#111] h-4 w-8 rounded-full"></div>
	// 								</label>
	// 							</div>
	// 						)}
	// 						{plugins.map((item, index) => (
	// 							<div className="border border-solid border-[#EBEBEB] rounded px-[16px] py-[14px] mb-[16px]">
	// 								<div className="flex items-center justify-between" key={index}>
	// 									<div className="flex items-center gap-[7px]">
	// 										<p className="m-0 text-[14px] sm:text-[14px] text-[#111]">{item.content}</p>
	// 										{item.hasDescription && (
	// 											<button
	// 												type="button"
	// 												className="bg-transparent border-0 cursor-pointer px-0"
	// 												onClick={() => handleDescription(index)}
	// 											>
	// 												{item.isDescriptionActive ? (
	// 													<svg
	// 														xmlns="http://www.w3.org/2000/svg"
	// 														width="21"
	// 														height="21"
	// 														viewBox="0 0 21 21"
	// 														fill="none"
	// 													>
	// 														<path
	// 															d="M17.6946 10.4995C17.6946 6.43987 14.4259 3.15652 10.4076 3.15652C6.37894 3.15652 3.12061 6.43987 3.12061 10.4995C3.12061 14.5591 6.37894 17.8424 10.4076 17.8424C14.4259 17.8424 17.6946 14.5591 17.6946 10.4995ZM11.1363 12.052H9.51235V11.6009C9.51235 11.2023 9.59563 10.8666 9.76219 10.5729C9.92875 10.2792 10.241 9.97497 10.6783 9.6393C11.1051 9.33509 11.3861 9.08333 11.5215 8.89451C11.6672 8.70569 11.7297 8.48541 11.7297 8.24414C11.7297 7.98189 11.636 7.78258 11.4382 7.63572C11.2404 7.49935 10.9697 7.43641 10.6158 7.43641C10.012 7.43641 9.31456 7.63572 8.53381 8.03434L7.86757 6.69163C8.77324 6.17762 9.74137 5.91537 10.7511 5.91537C11.5943 5.91537 12.2606 6.12517 12.7499 6.52379C13.2495 6.9329 13.489 7.47837 13.489 8.14973C13.489 8.60079 13.3953 8.98892 13.1871 9.31411C12.9893 9.64979 12.5937 10.0169 12.0316 10.426C11.636 10.7198 11.3966 10.94 11.2925 11.0869C11.1884 11.2443 11.1363 11.4436 11.1363 11.6848V12.052ZM9.60604 14.9262C9.41865 14.7479 9.32497 14.4856 9.32497 14.1605C9.32497 13.8143 9.40825 13.552 9.59563 13.3737C9.78301 13.1954 10.0433 13.1115 10.3972 13.1115C10.7303 13.1115 10.9906 13.2059 11.1779 13.3842C11.3653 13.5625 11.459 13.8248 11.459 14.1605C11.459 14.4752 11.3653 14.7374 11.1779 14.9157C10.9906 15.1045 10.7303 15.199 10.3972 15.199C10.0537 15.199 9.79342 15.1045 9.60604 14.9262Z"
	// 															fill="#2563EB"
	// 														/>
	// 													</svg>
	// 												) : (
	// 													<svg
	// 														xmlns="http://www.w3.org/2000/svg"
	// 														width="21"
	// 														height="21"
	// 														viewBox="0 0 21 21"
	// 														fill="none"
	// 													>
	// 														<g opacity="0.3">
	// 															<path
	// 																d="M17.6946 10.4995C17.6946 6.43987 14.4259 3.15652 10.4076 3.15652C6.37894 3.15652 3.12061 6.43987 3.12061 10.4995C3.12061 14.5591 6.37894 17.8424 10.4076 17.8424C14.4259 17.8424 17.6946 14.5591 17.6946 10.4995ZM11.1363 12.052H9.51235V11.6009C9.51235 11.2023 9.59563 10.8666 9.76219 10.5729C9.92875 10.2792 10.241 9.97497 10.6783 9.6393C11.1051 9.33509 11.3861 9.08333 11.5215 8.89451C11.6672 8.70569 11.7297 8.48541 11.7297 8.24414C11.7297 7.98189 11.636 7.78258 11.4382 7.63572C11.2404 7.49935 10.9697 7.43641 10.6158 7.43641C10.012 7.43641 9.31456 7.63572 8.53381 8.03434L7.86757 6.69163C8.77324 6.17762 9.74137 5.91537 10.7511 5.91537C11.5943 5.91537 12.2606 6.12517 12.7499 6.52379C13.2495 6.9329 13.489 7.47837 13.489 8.14973C13.489 8.60079 13.3953 8.98892 13.1871 9.31411C12.9893 9.64979 12.5937 10.0169 12.0316 10.426C11.636 10.7198 11.3966 10.94 11.2925 11.0869C11.1884 11.2443 11.1363 11.4436 11.1363 11.6848V12.052ZM9.60604 14.9262C9.41865 14.7479 9.32497 14.4856 9.32497 14.1605C9.32497 13.8143 9.40825 13.552 9.59563 13.3737C9.78301 13.1954 10.0433 13.1115 10.3972 13.1115C10.7303 13.1115 10.9906 13.2059 11.1779 13.3842C11.3653 13.5625 11.459 13.8248 11.459 14.1605C11.459 14.4752 11.3653 14.7374 11.1779 14.9157C10.9906 15.1045 10.7303 15.199 10.3972 15.199C10.0537 15.199 9.79342 15.1045 9.60604 14.9262Z"
	// 																fill="#111111"
	// 															/>
	// 														</g>
	// 													</svg>
	// 												)}
	// 											</button>
	// 										)}
	// 									</div>

	// 									<label className="flex items-center cursor-pointer relative">
	// 										<input
	// 											type="checkbox"
	// 											id="import-toggle"
	// 											className="sr-only"
	// 											value="1"
	// 											onChange={() => handlePluginToggle(index)}
	// 											checked={item.toggle}
	// 										/>
	// 										<div className="toggle-bg bg-white border border-solid border-[#111] h-4 w-8 rounded-full"></div>
	// 									</label>
	// 								</div>
	// 								{item.isDescriptionActive && <p className="m-0 italic">{item.activeContent}</p>}
	// 							</div>
	// 						))}
	// 					</div>
	// 				</div>
	// 				<div className="bg-[#FFF0F0] rounded px-[16px] py-[14px] text-[14px]/[23px]">
	// 					<em>
	// 						Importing demo content without activating the theme may lead to layout issues and
	// 						missing features, causing your site to appear broken or incomplete.
	// 					</em>
	// 					<div className="flex items-center mt-2 gap-2">
	// 						<input
	// 							id="proceed"
	// 							type="checkbox"
	// 							className="m-0 !mt-[2px]"
	// 							checked={isChecked}
	// 							onChange={(e) => setIsChecked(e.target.checked)}
	// 						/>
	// 						<label htmlFor="proceed" className="font-[600] text-[#222]">
	// 							I understand and agree to proceed with the import.
	// 						</label>
	// 					</div>
	// 				</div>
	// 			</>
	// 		),
	// 		footer: () => (
	// 			<>
	// 				<button
	// 					type="button"
	// 					className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
	// 					onClick={() => {
	// 						navigate(-1);
	// 					}}
	// 				>
	// 					Cancel
	// 				</button>
	// 				<button
	// 					type="button"
	// 					className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] disabled:opacity-50 disabled:cursor-not-allowed"
	// 					disabled={!isChecked}
	// 					onClick={handleInstallation}
	// 				>
	// 					Continue
	// 				</button>
	// 			</>
	// 		),
	// 	},
	// 	// {
	// 	// 	header: 'Install Theme',
	// 	// 	content: () => (
	// 	// 		<>
	// 	// 			<div className="text-center mt-16 mb-12">
	// 	// 				{/* <div className="border-[25px] border-solid border-[#FDFDFD] rounded-full inline-block ">
	// 	// 					<div className="border-[22px] border-solid border-[#f4f4f4] rounded-full block">
	// 	// 						<svg
	// 	// 							xmlns="http://www.w3.org/2000/svg"
	// 	// 							width="100"
	// 	// 							height="100"
	// 	// 							viewBox="0 0 128 127"
	// 	// 							fill="none"
	// 	// 							className="block"
	// 	// 						>
	// 	// 							<path
	// 	// 								d="M127.5 63.5C127.5 28.4299 99.0701 0 64 0C28.9299 0 0.5 28.4299 0.5 63.5C0.5 98.5701 28.9299 127 64 127C99.0701 127 127.5 98.5701 127.5 63.5Z"
	// 	// 								fill="#004846"
	// 	// 							/>
	// 	// 						</svg>
	// 	// 					</div>
	// 	// 				</div> */}
	// 	// 				<img src={checkImageExists(`import-${demo.theme}`)} alt="" />
	// 	// 			</div>

	// 	// 			<p className="bg-[#E9EFFD] border border-solid border-[#81A5F3] p-[16px] m-0 rounded text-[14px]">
	// 	// 				By clicking “Install”, you’ll install the{' '}
	// 	// 				<span className="capitalize">{demo.theme}</span> theme to ensure the layout matches the
	// 	// 				preview. Skipping the installation is strictly discouraged as it may result in a
	// 	// 				different layout.
	// 	// 			</p>
	// 	// 		</>
	// 	// 	),
	// 	// 	footer: () => (
	// 	// 		<>
	// 	// 			<button
	// 	// 				type="button"
	// 	// 				className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
	// 	// 				onClick={() => {
	// 	// 					setStep(0);
	// 	// 				}}
	// 	// 			>
	// 	// 				Back
	// 	// 			</button>
	// 	// 			<div>
	// 	// 				<button
	// 	// 					type="button"
	// 	// 					className="cursor-pointer mr-[24px] bg-transparent text-[#2563EB] border-0 text-[16px]"
	// 	// 					onClick={() => {
	// 	// 						if (totalPagebuilders > 1) {
	// 	// 							setStep(3);
	// 	// 						} else {
	// 	// 							setStep(4);
	// 	// 							setSelectedPagebuilder(Object.entries(demo.pagebuilders)[0][0]);
	// 	// 						}
	// 	// 					}}
	// 	// 				>
	// 	// 					Skip
	// 	// 				</button>
	// 	// 				<button
	// 	// 					type="button"
	// 	// 					className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px]"
	// 	// 					onClick={handleThemeInstall}
	// 	// 				>
	// 	// 					Install
	// 	// 				</button>
	// 	// 			</div>
	// 	// 		</>
	// 	// 	),
	// 	// },
	// 	// {
	// 	// 	header: 'Choose Page Builder',
	// 	// 	content: () => (
	// 	// 		<>
	// 	// 			<div className="text-center my-[150px] flex gap-[32px] justify-center">
	// 	// 				{Object.entries(demo?.pagebuilders).map(([key, value]) => (
	// 	// 					<div key={key}>
	// 	// 						<div
	// 	// 							className={`border border-solid rounded-[2px] px-[44px] py-[38px] ${selectedPagebuilder === key ? 'border-[#3858E9]' : 'border-[#E0E0E0]'}`}
	// 	// 							onClick={() => setSelectedPagebuilder(key)}
	// 	// 						>
	// 	// 							<img src={checkImageExists(`max-${key}`)} alt="" />
	// 	// 						</div>
	// 	// 						<p className="text-[14px] font-[600] text-[#383838] m-0 mt-[12px]">{value}</p>
	// 	// 					</div>
	// 	// 				))}
	// 	// 			</div>
	// 	// 		</>
	// 	// 	),
	// 	// 	footer: () => (
	// 	// 		<>
	// 	// 			<button
	// 	// 				type="button"
	// 	// 				className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
	// 	// 				onClick={() => {
	// 	// 					if (demo.theme === initialTheme) {
	// 	// 						setStep(0);
	// 	// 					} else {
	// 	// 						setStep(1);
	// 	// 					}
	// 	// 				}}
	// 	// 			>
	// 	// 				Back
	// 	// 			</button>

	// 	// 			<button
	// 	// 				type="button"
	// 	// 				className={`cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] ${!selectedPagebuilder ? 'opacity-50 cursor-not-allowed' : ''}`}
	// 	// 				disabled={!selectedPagebuilder}
	// 	// 				onClick={() => {
	// 	// 					setStep(4);
	// 	// 				}}
	// 	// 			>
	// 	// 				Continue
	// 	// 			</button>
	// 	// 		</>
	// 	// 	),
	// 	// },
	// 	{
	// 		header: 'Importing...',
	// 		content: () => (
	// 			<>
	// 				<p className="m-0 text-[14px] text-[#6B6B6B]">
	// 					It might take around 5 to 10 minutes to complete the importation process. Please do not
	// 					close or refresh this page!
	// 				</p>
	// 				<div className="flex mt-5 mb-4">
	// 					<svg
	// 						xmlns="http://www.w3.org/2000/svg"
	// 						width="24"
	// 						height="24"
	// 						viewBox="0 0 24 24"
	// 						fill="none"
	// 					>
	// 						<path
	// 							d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
	// 							fill="#23AB70"
	// 						/>
	// 					</svg>
	// 					<p className="m-0 text-[14px] text-[#6B6B6B]">{importProgressStep}</p>
	// 				</div>
	// 				<Progress
	// 					value={importProgress}
	// 					className="border border-solid border-[#f4f4f4] h-[83px] rounded-[7px] overflow-visible"
	// 					indicatorClassName={`bg-[#E9EFFD] border border-solid border-[#2563EB] rounded-none rounded-l-[7px] h-[81px] ${importProgress == 100 ? 'rounded-r-[7px]' : 'border-r-0 '}`}
	// 					indicatorStyle={{ width: `${importProgress}%` }}
	// 					progressContent={
	// 						<div className="text-[#383838] p-[19px] absolute top-0">
	// 							<p className="m-0 mb-[4px] text-[14px]">
	// 								{importProgressStep} {importProgress}%
	// 							</p>
	// 							<p className="m-0 text-[#6B6B6B] text-12px]">Home page template...</p>
	// 						</div>
	// 					}
	// 				/>
	// 			</>
	// 		),
	// 		footer: null,
	// 	},
	// 	{
	// 		header: `${demo.name} is successfully imported! Thank you for your patience`,
	// 		content: () => (
	// 			<>
	// 				<div className="flex m-0 mb-4">
	// 					<svg
	// 						xmlns="http://www.w3.org/2000/svg"
	// 						width="24"
	// 						height="24"
	// 						viewBox="0 0 24 24"
	// 						fill="none"
	// 					>
	// 						<path
	// 							d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
	// 							fill="#23AB70"
	// 						/>
	// 					</svg>
	// 					<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Customizer Settings</p>
	// 				</div>
	// 				<div className="flex m-0 mb-4">
	// 					<svg
	// 						xmlns="http://www.w3.org/2000/svg"
	// 						width="24"
	// 						height="24"
	// 						viewBox="0 0 24 24"
	// 						fill="none"
	// 					>
	// 						<path
	// 							d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
	// 							fill="#23AB70"
	// 						/>
	// 					</svg>
	// 					<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Widgets</p>
	// 				</div>
	// 				<div className="flex m-0 mb-4">
	// 					<svg
	// 						xmlns="http://www.w3.org/2000/svg"
	// 						width="24"
	// 						height="24"
	// 						viewBox="0 0 24 24"
	// 						fill="none"
	// 					>
	// 						<path
	// 							d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
	// 							fill="#23AB70"
	// 						/>
	// 					</svg>
	// 					<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Content</p>
	// 				</div>
	// 				<div className="flex m-0 mb-4">
	// 					<svg
	// 						xmlns="http://www.w3.org/2000/svg"
	// 						width="24"
	// 						height="24"
	// 						viewBox="0 0 24 24"
	// 						fill="none"
	// 					>
	// 						<path
	// 							d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
	// 							fill="#23AB70"
	// 						/>
	// 					</svg>
	// 					<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">
	// 						Installed and Activated Necessary Plugins
	// 					</p>
	// 				</div>
	// 				<p className="m-0 text-[14px] text-[#6B6B6B]">
	// 					PS: We try our best to use images free from legal perspectives. However, we do not take
	// 					responsibility for any harm. We strongly advise website owners to replace the images and
	// 					any copyrighted media before publishing them online.
	// 				</p>
	// 			</>
	// 		),
	// 		footer: () => (
	// 			<>
	// 				<a
	// 					type="button"
	// 					className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px] z-[50000] no-underline"
	// 					href={`${__TDI_DASHBOARD__.siteUrl}/wp-admin/`}
	// 				>
	// 					Go to Dashboard
	// 				</a>
	// 				<div className="z-[50000] flex flex-nowrap sm:block items-center ">
	// 					<a
	// 						type="button"
	// 						className="cursor-pointer mr-[10px] sm:mr-[24px] bg-transparent text-[#2563EB] border-0 text-[16px] no-underline"
	// 						href={`${__TDI_DASHBOARD__.siteUrl}/wp-admin/customize.php`}
	// 					>
	// 						Customizer
	// 					</a>
	// 					<button
	// 						type="button"
	// 						className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[10px] sm:px-[24px] py-[10px] text-[16px] "
	// 						onClick={() => {
	// 							window.open(__TDI_DASHBOARD__.siteUrl, '_blank');
	// 						}}
	// 					>
	// 						View Website
	// 					</button>
	// 				</div>
	// 			</>
	// 		),
	// 	},
	// ];

	// const currentStep = STEP[step];

	const handleInstallation = async () => {
		const selectedAdditionalPlugins = plugins
			.filter((plugin) => plugin.toggle === true)
			.map((plugin) => plugin.plugin);

		const results: Record<keyof typeof IMPORT_ACTIONS, any> = {
			'install-theme': null,
			'install-plugins': null,
			'import-content': null,
			'import-customizer': null,
			'import-widgets': null,
			complete: null,
		};

		for (const key in IMPORT_ACTIONS) {
			const action = key as keyof typeof IMPORT_ACTIONS;
			setImportAction(action);
			try {
				const response = await apiFetch<Response>({
					path: 'tg-demo-importer/v1/install?action=' + action,
					method: 'POST',
					data: {
						demo_config: demo,
						opts: {
							pagebuilder: pagebuilder,
							additional_plugins: selectedAdditionalPlugins,
							force_install_theme: installTheme,
							blogname: siteTitle,
							blogdescription: siteTagline,
							custom_logo: siteLogoId,
							pages: pages,
						},
					},
					parse: false,
				});
				const data = await response.json();
				// update state here
				results[action] = data;
				setImportProgress((prev) => {
					let next = 0;
					if (action !== 'complete') {
						next = prev + IMPORT_ACTIONS[action].progressWeight;
					} else {
						next = 100;
					}
					return next;
				});
				setImportProgressStepTitle(IMPORT_ACTIONS[action].stepTitle);
				setImportProgressStepSubTitle(IMPORT_ACTIONS[action].stepSubTitle);
				console.log(results);
			} catch (e) {
				setImportAction(null);
				setImportProgress(0);
				break;
			}
		}
	};

	return (
		<Dialog>
			<DialogTrigger asChild>
				<button
					type="button"
					className={`bg-[#2563EB] rounded-[2px] px-[16px] py-[8px] border border-solid border-[#2563EB] cursor-pointer flex items-center disabled:opacity-50 disabled:cursor-not-allowed ${additionalStyles ? additionalStyles : ''}`}
					disabled={disabled}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="16"
						height="16"
						viewBox="0 0 16 16"
						fill="none"
					>
						<path
							d="M12.5799 12.1533C12.4313 12.1541 12.2866 12.1051 12.1689 12.0142C12.0513 11.9233 11.9674 11.7957 11.9306 11.6516C11.8938 11.5075 11.9063 11.3553 11.9659 11.2191C12.0256 11.0829 12.1291 10.9706 12.2599 10.9C12.7382 10.6379 13.0931 10.1967 13.2466 9.67334C13.3263 9.41452 13.3527 9.1422 13.324 8.8729C13.2954 8.6036 13.2123 8.34292 13.0799 8.10667C12.904 7.78115 12.643 7.50943 12.3249 7.32043C12.0067 7.13142 11.6433 7.03221 11.2733 7.03334H10.6066C10.4552 7.03813 10.3067 6.99122 10.1855 6.90034C10.0643 6.80945 9.97772 6.68002 9.93993 6.53334C9.82019 6.0624 9.6087 5.61972 9.31757 5.23066C9.02644 4.8416 8.66139 4.51382 8.24336 4.2661C7.82532 4.01837 7.36251 3.85557 6.88145 3.78703C6.40038 3.71849 5.91052 3.74555 5.43993 3.86667C4.96899 3.98641 4.52631 4.19791 4.13725 4.48904C3.7482 4.78017 3.42042 5.14521 3.17269 5.56324C2.92497 5.98128 2.76217 6.44409 2.69363 6.92516C2.62509 7.40622 2.65215 7.89608 2.77326 8.36667C2.92277 8.9302 3.19863 9.45228 3.57993 9.89334C3.69647 10.026 3.7556 10.1995 3.74435 10.3757C3.7331 10.552 3.65239 10.7165 3.51993 10.8333C3.38725 10.9499 3.21375 11.009 3.03752 10.9978C2.86129 10.9865 2.69672 10.9058 2.57993 10.7733C2.05747 10.1769 1.68088 9.46706 1.47993 8.7C1.1113 7.43628 1.25311 6.07837 1.8749 4.91808C2.49668 3.75779 3.54881 2.88771 4.80521 2.49482C6.0616 2.10192 7.42198 2.21756 8.59403 2.81689C9.76608 3.41622 10.6563 4.45141 11.0733 5.7H11.2733C12.0256 5.7019 12.7559 5.95373 13.3495 6.41592C13.943 6.87811 14.3662 7.52444 14.5524 8.25332C14.7387 8.9822 14.6774 9.75231 14.3783 10.4426C14.0791 11.1328 13.5591 11.7041 12.8999 12.0667C12.8023 12.122 12.6922 12.1519 12.5799 12.1533ZM10.6666 10.2533C10.5417 10.1292 10.3727 10.0595 10.1966 10.0595C10.0205 10.0595 9.85151 10.1292 9.7266 10.2533L8.6666 11.3333V8C8.6666 7.82319 8.59636 7.65362 8.47134 7.5286C8.34631 7.40357 8.17674 7.33334 7.99993 7.33334C7.82312 7.33334 7.65355 7.40357 7.52853 7.5286C7.4035 7.65362 7.33326 7.82319 7.33326 8V11.3333L6.29326 10.2533C6.23097 10.1915 6.15709 10.1427 6.07587 10.1095C5.99464 10.0763 5.90767 10.0595 5.81993 10.06C5.73219 10.0595 5.64522 10.0763 5.56399 10.1095C5.48277 10.1427 5.40889 10.1915 5.3466 10.2533C5.28323 10.3144 5.23259 10.3875 5.19759 10.4682C5.1626 10.549 5.14394 10.6359 5.1427 10.7239C5.14146 10.8119 5.15767 10.8993 5.19037 10.981C5.22308 11.0627 5.27164 11.1372 5.33326 11.2L7.51993 13.38C7.58137 13.44 7.6538 13.4875 7.73326 13.52C7.81398 13.5562 7.90145 13.575 7.98993 13.575C8.07841 13.575 8.16588 13.5562 8.2466 13.52C8.32606 13.4875 8.3985 13.44 8.45993 13.38L10.6666 11.2C10.7291 11.138 10.7787 11.0643 10.8125 10.9831C10.8464 10.9018 10.8638 10.8147 10.8638 10.7267C10.8638 10.6387 10.8464 10.5515 10.8125 10.4703C10.7787 10.389 10.7291 10.3153 10.6666 10.2533Z"
							fill={textColor ? textColor : 'white'}
						/>
					</svg>
					<span className={`text-${textColor ? textColor : 'white'} ml-[8px] font-[600]`}>
						{buttonTitle}
					</span>
				</button>
			</DialogTrigger>
			<DialogContent className="border-solid z-[50000] border-[#F4F4F4] px-0 py-0 gap-0 max-w-[300px] sm:max-w-[600px]">
				{!importAction ? (
					<DialogConsent
						demo={demo}
						initialTheme={initialTheme}
						installTheme={installTheme}
						setInstallTheme={setInstallTheme}
						plugins={plugins}
						setPlugins={setPlugins}
						onConfirm={handleInstallation}
					/>
				) : (
					<>
						{'complete' !== importAction ? (
							<DialogImporting
								importProgress={importProgress}
								importProgressStepTitle={importProgressStepTitle}
								importProgressStepSubTitle={importProgressStepSubTitle}
							/>
						) : (
							<DialogImported demo={demo} data={__TDI_DASHBOARD__} />
						)}
					</>
				)}
			</DialogContent>
		</Dialog>
	);
};

export default ImportButton;
