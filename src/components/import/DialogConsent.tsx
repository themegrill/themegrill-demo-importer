import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { DialogClose, DialogFooter, DialogHeader, DialogTitle } from '../../controls/Dialog';
import { themes } from '../../lib/themes';
import { Demo } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';

declare const require: any;

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
	installTheme,
	setInstallTheme,
	plugins,
	setPlugins,
}: {
	onConfirm: () => void;
	demo: Demo;
	installTheme: boolean;
	setInstallTheme: React.Dispatch<React.SetStateAction<boolean>>;
	plugins: PluginItem[];
	setPlugins: React.Dispatch<React.SetStateAction<PluginItem[]>>;
}) => {
	const { localizedData } = useLocalizedData();
	const currentTheme = localizedData.current_theme;
	const [isConsentChecked, setIsConsentChecked] = useState(false);
	const matchedTheme = themes.find((theme) => theme.slug === demo.theme_slug);

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

	const checkImageExists = (key: string): string => {
		try {
			return require(`../../images/${key}.png`);
		} catch {
			return '';
		}
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
							{('zakra' !== demo.theme_slug && (demo.pro || demo.premium)
								? `${demo.theme_slug}-pro`
								: demo.theme_slug) !== currentTheme && (
								<div className="flex items-center justify-between bg-[#FAFBFF] border border-solid border-[#EBEBEB] rounded px-[16px] py-[18px] mb-[16px]">
									<div className="flex items-center gap-[10px] capitalize">
										{checkImageExists(demo.theme_slug) && (
											<img src={checkImageExists(demo.theme_slug)} alt="" width="38px" />
										)}
										<div>
											<h6 className="text-[15px] text-[#111] m-0 mb-[4px]">
												{demo.theme_slug} Theme
											</h6>
											<p className="text-[13px] text-[#383838] tracking-[0.13px] m-0">
												{matchedTheme?.description}
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
								<div
									className="border border-solid border-[#EBEBEB] rounded px-[16px] py-[14px] mb-[16px]"
									key={index}
								>
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
					{!installTheme && (
						<div className="bg-[#FFF0F0] rounded px-[16px] py-[14px] text-[14px]/[23px]">
							<em>
								{__(
									'Importing demo content without activating the theme may lead to layout issues and missing features, causing your site to appear broken or incomplete.',
									'themegrill-demo-importer',
								)}
							</em>
							<div className="flex items-center mt-2 gap-2">
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
					)}
				</>
			</div>
			<DialogFooter className="border-0 border-t border-solid border-[#f4f4f4] p-[16px] sm:py-[16px] sm:px-[40px] flex items-center justify-between flex-row sm:justify-between">
				<DialogClose asChild>
					<button
						type="button"
						className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px]"
					>
						{__('Cancel', 'themegrill-demo-importer')}
					</button>
				</DialogClose>
				<button
					type="button"
					className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] disabled:opacity-50 disabled:cursor-not-allowed"
					disabled={!installTheme && !isConsentChecked}
					onClick={onConfirm}
				>
					{__('Continue', 'themegrill-demo-importer')}
				</button>
			</DialogFooter>
		</>
	);
};
