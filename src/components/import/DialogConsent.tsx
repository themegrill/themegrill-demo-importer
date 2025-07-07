import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { DialogFooter, DialogHeader, DialogTitle } from '../../controls/Dialog';
import { SearchResultType } from '../../lib/types';

type PluginItem = {
	slug: string;
	content: string;
	activeContent: string;
	hasDescription: boolean;
	isDescriptionActive: boolean;
	toggle: boolean;
	plugin: string;
};

export const DialogConsent = ({
	onConfirm,
	demo,
	initialTheme,
	installTheme,
	setInstallTheme,
	plugins,
	setPlugins,
}: {
	onConfirm: () => void;
	initialTheme: string;
	demo: SearchResultType;
	installTheme: boolean;
	setInstallTheme: React.Dispatch<React.SetStateAction<boolean>>;
	plugins: PluginItem[];
	setPlugins: React.Dispatch<React.SetStateAction<PluginItem[]>>;
}) => {
	const navigate = useNavigate();
	const [isConsentChecked, setIsConsentChecked] = useState(false);

	const handlePluginToggle = (index: number) => {
		setPlugins((prevItems: PluginItem[]) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, toggle: !item.toggle } : item,
			),
		);
	};

	const handleDescription = (index: number) => {
		setPlugins((prevItems: PluginItem[]) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, isDescriptionActive: !item.isDescriptionActive } : item,
			),
		);
	};
	return (
		<>
			<DialogHeader className="border-0 border-b border-solid border-[#f4f4f4] px-[40px] py-[20px]">
				<DialogTitle className="my-0 text-[18px] text-[#383838]">
					{__('Things to know', 'themegrill-demo-importer')}
				</DialogTitle>
			</DialogHeader>
			<div className="px-[40px] pt-[20px] pb-[48px] overflow-x-hidden overflow-y-scroll sm:overflow-hidden">
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
									{/* <Switch /> */}
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
							{__(
								'Importing demo content without activating the theme may lead to layout issues and missing features, causing your site to appear broken or incomplete.',
								'themegrill-demo-importer',
							)}
						</em>
						<div className="flex items-center mt-2 gap-2">
							{/* <Checkbox id="proceed" /> */}
							<input
								id="proceed"
								type="checkbox"
								className="m-0 !mt-[2px]"
								checked={isConsentChecked}
								onChange={(e) => setIsConsentChecked(e.target.checked)}
							/>
							<label htmlFor="proceed" className="font-[600] text-[#222]">
								{__(
									'I understand and agree to proceed with the import.',
									'themegrill-demo-importer',
								)}
							</label>
						</div>
					</div>
				</>
			</div>
			<DialogFooter className="border-0 border-t border-solid border-[#f4f4f4] p-[16px] sm:py-[16px] sm:px-[40px] flex items-center justify-between flex-row sm:justify-between">
				<button
					type="button"
					className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
					onClick={() => {
						navigate(-1);
					}}
				>
					{__('Cancel', 'themegrill-demo-importer')}
				</button>
				<button
					type="button"
					className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] disabled:opacity-50 disabled:cursor-not-allowed"
					disabled={!isConsentChecked}
					onClick={onConfirm}
				>
					{__('Continue', 'themegrill-demo-importer')}
				</button>
			</DialogFooter>
		</>
	);
};
