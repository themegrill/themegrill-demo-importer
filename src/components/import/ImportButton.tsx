import apiFetch from '@wordpress/api-fetch';
import Lottie from 'lottie-react';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import confetti from '../../assets/animation/confetti.json';
import { useDemoContext } from '../../context';
import { Progress } from '../../controls/Progress';
import { __TDI_DASHBOARD__, PageWithSelection, SearchResultType } from '../../lib/types';
import ImportDialogSkeleton from '../import-dialog/ImportDialogSkeleton';

declare const require: any;

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

	const navigate = useNavigate();
	const [step, setStep] = useState(0);
	const [plugins, setPlugins] = useState(pluginsList);
	const [isChecked, setIsChecked] = useState(false);
	const [installProgress, setInstallProgress] = useState(0);
	const [importProgress, setImportProgress] = useState(0);
	const [installTheme, setInstallTheme] = useState(true);

	const totalPagebuilders = Object.entries(demo?.pagebuilders)?.length ?? 0;

	const STEP: Array<{
		header: React.ReactNode;
		content: React.FunctionComponent;
		footer: React.FunctionComponent | null;
	}> = [
		{
			header: 'Things to know',
			content: () => (
				<>
					<p className="text-[15px]/[25px] sm:text-[15px] m-0 mb-[32px]">
						Importing demo data ensures your site looks like the theme demo. You can simply modify
						the content instead of starting everything from scratch.
					</p>
					<div>
						<h3 className="text-[16px] text-[#383838]">Install and Activate</h3>
						<div className="my-[20px]">
							{demo.theme !== initialTheme && (
								<div className="flex items-center justify-between bg-[#FAFBFF] border border-solid border-[#EBEBEB] rounded px-[16px] py-[18px] mb-[16px]">
									<div className="flex items-center gap-[10px] capitalize">
										<svg
											xmlns="http://www.w3.org/2000/svg"
											width="38"
											height="38"
											viewBox="0 0 38 38"
											fill="none"
										>
											<path
												d="M37.9996 18.9998C37.9996 8.5065 29.4931 0 18.9998 0C8.5065 0 0 8.5065 0 18.9998C0 29.4931 8.5065 37.9996 18.9998 37.9996C29.4931 37.9996 37.9996 29.4931 37.9996 18.9998Z"
												fill="#004846"
											/>
											<path
												d="M24.6534 11.0835C24.3563 11.0832 24.0621 11.1415 23.7877 11.2552C23.5132 11.3689 23.264 11.5358 23.0542 11.7461L11.7446 23.055C11.3204 23.4792 11.082 24.0546 11.082 24.6545C11.082 25.2544 11.3204 25.8298 11.7446 26.254C12.1688 26.6783 12.7442 26.9166 13.3441 26.9166C13.9441 26.9166 14.5195 26.6783 14.9437 26.254L26.2525 14.9444C26.5688 14.6281 26.7841 14.2251 26.8714 13.7864C26.9586 13.3477 26.9138 12.893 26.7427 12.4798C26.5715 12.0665 26.2817 11.7133 25.9098 11.4648C25.5379 11.2162 25.1007 11.0836 24.6534 11.0835Z"
												fill="white"
											/>
											<path
												d="M11.8861 15.0724C12.1086 15.2509 12.3897 15.34 12.6745 15.3223C12.9592 15.3046 13.2271 15.1813 13.4258 14.9766L17.305 11.0975V11.0833H13.4393C12.8354 11.074 12.2516 11.3002 11.8117 11.714C11.3718 12.1278 11.1102 12.6966 11.0825 13.2999C11.0755 13.6366 11.144 13.9706 11.283 14.2774C11.4221 14.5841 11.6282 14.8558 11.8861 15.0724Z"
												fill="white"
											/>
											<path
												d="M24.6432 22.951L20.6777 26.9164H24.5648C25.166 26.9247 25.747 26.6992 26.1852 26.2874C26.6234 25.8757 26.8845 25.3099 26.9136 24.7093C26.9227 24.3405 26.8412 23.9751 26.6763 23.6451C26.5114 23.3151 26.268 23.0305 25.9676 22.8164C25.7663 22.6794 25.5231 22.6178 25.2809 22.6424C25.0386 22.667 24.8128 22.7763 24.6432 22.951Z"
												fill="white"
											/>
										</svg>
										<div>
											<h6 className="text-[15px] text-[#111] m-0 mb-[4px]">{demo.theme} Theme</h6>
											<p className="text-[13px] text-[#383838] tracking-[0.13px] m-0">
												Powerful Multipurpose WordPress Theme
											</p>
										</div>
									</div>
									<label className="flex items-center cursor-pointer relative">
										<input
											type="checkbox"
											id="import-toggle"
											className="sr-only"
											value="1"
											checked={installTheme}
											onChange={() => setInstallTheme(!installTheme)}
										/>
										<div className="toggle-bg bg-white border border-solid border-[#111] h-4 w-8 rounded-full"></div>
									</label>
								</div>
							)}
							{plugins.map((item, index) => (
								<div className="border border-solid border-[#EBEBEB] rounded px-[16px] py-[14px] mb-[16px]">
									<div className="flex items-center justify-between" key={index}>
										<div className="flex items-center gap-[7px]">
											<p className="m-0 text-[14px] sm:text-[14px] text-[#111]">{item.content}</p>
											{item.hasDescription && (
												<button
													type="button"
													className="bg-transparent border-0 cursor-pointer px-0"
													onClick={() => handleDescription(index)}
												>
													{item.isDescriptionActive ? (
														<svg
															xmlns="http://www.w3.org/2000/svg"
															width="21"
															height="21"
															viewBox="0 0 21 21"
															fill="none"
														>
															<path
																d="M17.6946 10.4995C17.6946 6.43987 14.4259 3.15652 10.4076 3.15652C6.37894 3.15652 3.12061 6.43987 3.12061 10.4995C3.12061 14.5591 6.37894 17.8424 10.4076 17.8424C14.4259 17.8424 17.6946 14.5591 17.6946 10.4995ZM11.1363 12.052H9.51235V11.6009C9.51235 11.2023 9.59563 10.8666 9.76219 10.5729C9.92875 10.2792 10.241 9.97497 10.6783 9.6393C11.1051 9.33509 11.3861 9.08333 11.5215 8.89451C11.6672 8.70569 11.7297 8.48541 11.7297 8.24414C11.7297 7.98189 11.636 7.78258 11.4382 7.63572C11.2404 7.49935 10.9697 7.43641 10.6158 7.43641C10.012 7.43641 9.31456 7.63572 8.53381 8.03434L7.86757 6.69163C8.77324 6.17762 9.74137 5.91537 10.7511 5.91537C11.5943 5.91537 12.2606 6.12517 12.7499 6.52379C13.2495 6.9329 13.489 7.47837 13.489 8.14973C13.489 8.60079 13.3953 8.98892 13.1871 9.31411C12.9893 9.64979 12.5937 10.0169 12.0316 10.426C11.636 10.7198 11.3966 10.94 11.2925 11.0869C11.1884 11.2443 11.1363 11.4436 11.1363 11.6848V12.052ZM9.60604 14.9262C9.41865 14.7479 9.32497 14.4856 9.32497 14.1605C9.32497 13.8143 9.40825 13.552 9.59563 13.3737C9.78301 13.1954 10.0433 13.1115 10.3972 13.1115C10.7303 13.1115 10.9906 13.2059 11.1779 13.3842C11.3653 13.5625 11.459 13.8248 11.459 14.1605C11.459 14.4752 11.3653 14.7374 11.1779 14.9157C10.9906 15.1045 10.7303 15.199 10.3972 15.199C10.0537 15.199 9.79342 15.1045 9.60604 14.9262Z"
																fill="#2563EB"
															/>
														</svg>
													) : (
														<svg
															xmlns="http://www.w3.org/2000/svg"
															width="21"
															height="21"
															viewBox="0 0 21 21"
															fill="none"
														>
															<g opacity="0.3">
																<path
																	d="M17.6946 10.4995C17.6946 6.43987 14.4259 3.15652 10.4076 3.15652C6.37894 3.15652 3.12061 6.43987 3.12061 10.4995C3.12061 14.5591 6.37894 17.8424 10.4076 17.8424C14.4259 17.8424 17.6946 14.5591 17.6946 10.4995ZM11.1363 12.052H9.51235V11.6009C9.51235 11.2023 9.59563 10.8666 9.76219 10.5729C9.92875 10.2792 10.241 9.97497 10.6783 9.6393C11.1051 9.33509 11.3861 9.08333 11.5215 8.89451C11.6672 8.70569 11.7297 8.48541 11.7297 8.24414C11.7297 7.98189 11.636 7.78258 11.4382 7.63572C11.2404 7.49935 10.9697 7.43641 10.6158 7.43641C10.012 7.43641 9.31456 7.63572 8.53381 8.03434L7.86757 6.69163C8.77324 6.17762 9.74137 5.91537 10.7511 5.91537C11.5943 5.91537 12.2606 6.12517 12.7499 6.52379C13.2495 6.9329 13.489 7.47837 13.489 8.14973C13.489 8.60079 13.3953 8.98892 13.1871 9.31411C12.9893 9.64979 12.5937 10.0169 12.0316 10.426C11.636 10.7198 11.3966 10.94 11.2925 11.0869C11.1884 11.2443 11.1363 11.4436 11.1363 11.6848V12.052ZM9.60604 14.9262C9.41865 14.7479 9.32497 14.4856 9.32497 14.1605C9.32497 13.8143 9.40825 13.552 9.59563 13.3737C9.78301 13.1954 10.0433 13.1115 10.3972 13.1115C10.7303 13.1115 10.9906 13.2059 11.1779 13.3842C11.3653 13.5625 11.459 13.8248 11.459 14.1605C11.459 14.4752 11.3653 14.7374 11.1779 14.9157C10.9906 15.1045 10.7303 15.199 10.3972 15.199C10.0537 15.199 9.79342 15.1045 9.60604 14.9262Z"
																	fill="#111111"
																/>
															</g>
														</svg>
													)}
												</button>
											)}
										</div>

										<label className="flex items-center cursor-pointer relative">
											<input
												type="checkbox"
												id="import-toggle"
												className="sr-only"
												value="1"
												onChange={() => handlePluginToggle(index)}
												checked={item.toggle}
											/>
											<div className="toggle-bg bg-white border border-solid border-[#111] h-4 w-8 rounded-full"></div>
										</label>
									</div>
									{item.isDescriptionActive && <p className="m-0 italic">{item.activeContent}</p>}
								</div>
							))}
						</div>
					</div>
					<div className="bg-[#FFF0F0] rounded px-[16px] py-[14px] text-[14px]/[23px]">
						<em>
							Importing demo content without activating the theme may lead to layout issues and
							missing features, causing your site to appear broken or incomplete.
						</em>
						<div className="flex items-center mt-2 gap-2">
							<input
								id="proceed"
								type="checkbox"
								className="m-0 !mt-[2px]"
								checked={isChecked}
								onChange={(e) => setIsChecked(e.target.checked)}
							/>
							<label htmlFor="proceed" className="font-[600] text-[#222]">
								I understand and agree to proceed with the import.
							</label>
						</div>
					</div>
				</>
			),
			footer: () => (
				<>
					<button
						type="button"
						className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
						onClick={() => {
							navigate(-1);
						}}
					>
						Cancel
					</button>
					<button
						type="button"
						className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] disabled:opacity-50 disabled:cursor-not-allowed"
						disabled={!isChecked}
						onClick={handleInstallation}
					>
						Continue
					</button>
				</>
			),
		},
		// {
		// 	header: 'Install Theme',
		// 	content: () => (
		// 		<>
		// 			<div className="text-center mt-16 mb-12">
		// 				{/* <div className="border-[25px] border-solid border-[#FDFDFD] rounded-full inline-block ">
		// 					<div className="border-[22px] border-solid border-[#f4f4f4] rounded-full block">
		// 						<svg
		// 							xmlns="http://www.w3.org/2000/svg"
		// 							width="100"
		// 							height="100"
		// 							viewBox="0 0 128 127"
		// 							fill="none"
		// 							className="block"
		// 						>
		// 							<path
		// 								d="M127.5 63.5C127.5 28.4299 99.0701 0 64 0C28.9299 0 0.5 28.4299 0.5 63.5C0.5 98.5701 28.9299 127 64 127C99.0701 127 127.5 98.5701 127.5 63.5Z"
		// 								fill="#004846"
		// 							/>
		// 						</svg>
		// 					</div>
		// 				</div> */}
		// 				<img src={checkImageExists(`import-${demo.theme}`)} alt="" />
		// 			</div>

		// 			<p className="bg-[#E9EFFD] border border-solid border-[#81A5F3] p-[16px] m-0 rounded text-[14px]">
		// 				By clicking “Install”, you’ll install the{' '}
		// 				<span className="capitalize">{demo.theme}</span> theme to ensure the layout matches the
		// 				preview. Skipping the installation is strictly discouraged as it may result in a
		// 				different layout.
		// 			</p>
		// 		</>
		// 	),
		// 	footer: () => (
		// 		<>
		// 			<button
		// 				type="button"
		// 				className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
		// 				onClick={() => {
		// 					setStep(0);
		// 				}}
		// 			>
		// 				Back
		// 			</button>
		// 			<div>
		// 				<button
		// 					type="button"
		// 					className="cursor-pointer mr-[24px] bg-transparent text-[#2563EB] border-0 text-[16px]"
		// 					onClick={() => {
		// 						if (totalPagebuilders > 1) {
		// 							setStep(3);
		// 						} else {
		// 							setStep(4);
		// 							setSelectedPagebuilder(Object.entries(demo.pagebuilders)[0][0]);
		// 						}
		// 					}}
		// 				>
		// 					Skip
		// 				</button>
		// 				<button
		// 					type="button"
		// 					className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px]"
		// 					onClick={handleThemeInstall}
		// 				>
		// 					Install
		// 				</button>
		// 			</div>
		// 		</>
		// 	),
		// },
		// {
		// 	header: 'Choose Page Builder',
		// 	content: () => (
		// 		<>
		// 			<div className="text-center my-[150px] flex gap-[32px] justify-center">
		// 				{Object.entries(demo?.pagebuilders).map(([key, value]) => (
		// 					<div key={key}>
		// 						<div
		// 							className={`border border-solid rounded-[2px] px-[44px] py-[38px] ${selectedPagebuilder === key ? 'border-[#3858E9]' : 'border-[#E0E0E0]'}`}
		// 							onClick={() => setSelectedPagebuilder(key)}
		// 						>
		// 							<img src={checkImageExists(`max-${key}`)} alt="" />
		// 						</div>
		// 						<p className="text-[14px] font-[600] text-[#383838] m-0 mt-[12px]">{value}</p>
		// 					</div>
		// 				))}
		// 			</div>
		// 		</>
		// 	),
		// 	footer: () => (
		// 		<>
		// 			<button
		// 				type="button"
		// 				className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
		// 				onClick={() => {
		// 					if (demo.theme === initialTheme) {
		// 						setStep(0);
		// 					} else {
		// 						setStep(1);
		// 					}
		// 				}}
		// 			>
		// 				Back
		// 			</button>

		// 			<button
		// 				type="button"
		// 				className={`cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] ${!selectedPagebuilder ? 'opacity-50 cursor-not-allowed' : ''}`}
		// 				disabled={!selectedPagebuilder}
		// 				onClick={() => {
		// 					setStep(4);
		// 				}}
		// 			>
		// 				Continue
		// 			</button>
		// 		</>
		// 	),
		// },
		{
			header: 'Importing...',
			content: () => (
				<>
					<p className="m-0 text-[14px] text-[#6B6B6B]">
						It might take around 5 to 10 minutes to complete the importation process. Please do not
						close or refresh this page!
					</p>
					<div className="flex mt-5 mb-4">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
								fill="#23AB70"
							/>
						</svg>
						<p className="m-0 text-[14px] text-[#6B6B6B]">Imported Customizer Settings</p>
					</div>
					<Progress
						value={importProgress}
						className="border border-solid border-[#f4f4f4] h-[83px] rounded-[7px] overflow-visible"
						indicatorClassName={`bg-[#E9EFFD] border border-solid border-[#2563EB] rounded-none rounded-l-[7px] h-[81px] ${importProgress == 100 ? 'rounded-r-[7px]' : 'border-r-0 '}`}
						indicatorStyle={{ width: `${importProgress}%` }}
						progressContent={
							<div className="text-[#383838] p-[19px] absolute top-0">
								<p className="m-0 mb-[4px] text-[14px]">Importing Content... {importProgress}%</p>
								<p className="m-0 text-[#6B6B6B] text-12px]">Home page template...</p>
							</div>
						}
					/>
				</>
			),
			footer: null,
		},
		{
			header: `${demo.name} is successfully imported! Thank you for your patience`,
			content: () => (
				<>
					<div className="flex m-0 mb-4">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
								fill="#23AB70"
							/>
						</svg>
						<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Customizer Settings</p>
					</div>
					<div className="flex m-0 mb-4">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
								fill="#23AB70"
							/>
						</svg>
						<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Widgets</p>
					</div>
					<div className="flex m-0 mb-4">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
								fill="#23AB70"
							/>
						</svg>
						<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Content</p>
					</div>
					<div className="flex m-0 mb-4">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M18.3563 6.36033L10.1962 14.5203L6.83625 11.1603L5.15625 12.8403L10.1962 17.8803L20.0363 8.04033L18.3563 6.36033Z"
								fill="#23AB70"
							/>
						</svg>
						<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">
							Installed and Activated Necessary Plugins
						</p>
					</div>
					<p className="m-0 text-[14px] text-[#6B6B6B]">
						PS: We try our best to use images free from legal perspectives. However, we do not take
						responsibility for any harm. We strongly advise website owners to replace the images and
						any copyrighted media before publishing them online.
					</p>
				</>
			),
			footer: () => (
				<>
					<a
						type="button"
						className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px] z-[50000] no-underline"
						href={`${__TDI_DASHBOARD__.siteUrl}/wp-admin/`}
					>
						Go to Dashboard
					</a>
					<div className="z-[50000] flex flex-nowrap sm:block items-center ">
						<a
							type="button"
							className="cursor-pointer mr-[10px] sm:mr-[24px] bg-transparent text-[#2563EB] border-0 text-[16px] no-underline"
							href={`${__TDI_DASHBOARD__.siteUrl}/wp-admin/customize.php`}
						>
							Customizer
						</a>
						<button
							type="button"
							className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[10px] sm:px-[24px] py-[10px] text-[16px] "
							onClick={() => {
								window.open(__TDI_DASHBOARD__.siteUrl, '_blank');
							}}
						>
							View Website
						</button>
					</div>
				</>
			),
		},
	];

	const currentStep = STEP[step];

	if (step === 5) {
		if (importProgress < 100) {
			setTimeout(() => {
				setImportProgress(importProgress + 20);
			}, 1000);
		} else {
			setStep(6);
		}
	}

	const handlePluginToggle = (index: number) => {
		setPlugins((prevItems) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, toggle: !item.toggle } : item,
			),
		);
	};

	const handleDescription = (index: number) => {
		setPlugins((prevItems) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, isDescriptionActive: !item.isDescriptionActive } : item,
			),
		);
	};

	const handleInstallation = async () => {
		try {
			const response = await apiFetch<{
				success: boolean;
			}>({
				path: 'tg-demo-importer/v1/install',
				method: 'POST',
				data: {
					demo: demo,
					selectedPagebuilder: pagebuilder,
					additional_plugins: plugins,
					installTheme: installTheme,
					siteTitle: siteTitle,
					siteTagline: siteTagline,
					siteLogoId: siteLogoId,
					pages: pages,
				},
			});
			if (response.success) {
				console.log(response);
				setStep(2);
			} else {
				console.log(response);
				throw new Error(JSON.stringify(response));
			}
		} catch (e) {
			console.error(e);
		}
	};

	// const handleThemeInstall = async () => {
	// 	try {
	// 		const response = await apiFetch<{
	// 			success: boolean;
	// 		}>({
	// 			path: 'tg-demo-importer/v1/install-theme',
	// 			method: 'POST',
	// 			data: { theme: demo.theme },
	// 		});
	// 		if (response.success) {
	// 			console.log(response);
	// 			setStep(2);
	// 		} else {
	// 			console.log(response);
	// 			throw new Error(JSON.stringify(response));
	// 		}
	// 	} catch (e) {
	// 		console.error(e);
	// 	}
	// };

	// const handleImport = async () => {
	// 	try {
	// 		let slugs: string[] = [];
	// 		items.map((item) => {
	// 			if (item.toggle == true && item.slug != 'plugins') {
	// 				slugs.push(item.slug);
	// 			}
	// 		});
	// 		const pluginResponse = await apiFetch<{
	// 			success: boolean;
	// 		}>({
	// 			path: `tg-demo-importer/v1/import-plugins`,
	// 			method: 'POST',
	// 			data: { demo: demo, slugs: slugs, selectedPagebuilder: selectedPagebuilder },
	// 		});
	// 		if (pluginResponse.success) {
	// 			const response = await apiFetch({
	// 				path: 'tg-demo-importer/v1/import',
	// 				method: 'POST',
	// 				data: { demo: demo, slugs: slugs, selectedPagebuilder: selectedPagebuilder },
	// 			});
	// 			console.log(response);
	// 		} else {
	// 			console.log(pluginResponse);
	// 			throw new Error(JSON.stringify(pluginResponse));
	// 		}
	// 	} catch (error) {
	// 		console.error('Error importing plugin:', error);
	// 	}
	// };

	// const checkImageExists = (key: string): string => {
	// 	try {
	// 		return require(`../../assets/images/${key}.png`);
	// 	} catch {
	// 		return '';
	// 	}
	// };

	return (
		<div>
			<ImportDialogSkeleton
				buttonTitle={buttonTitle}
				additionalStyles={additionalStyles}
				textColor={textColor}
				disabled={disabled}
				{...currentStep}
				{...(step === STEP.length - 1 && {
					notice: (
						<p className="text-[#4CC741] absolute bottom-[480px] left-[15%] sm:bottom-[370px] sm:left-[22%] text-[30px] sm:text-[48px] lily-script-one-regular">
							Congratulation!!
						</p>
					),
					extraContent: (
						<Lottie
							animationData={confetti}
							loop={true}
							autoplay={true}
							style={{ width: '100%' }}
							className="absolute bottom-[-100px] sm:bottom-[-270px] top-0"
						/>
					),
				})}
			/>
		</div>
	);
};

export default ImportButton;
