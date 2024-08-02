import Lottie from 'lottie-react';
import React, { useState } from 'react';
import confetti from '../../assets/animation/confetti.json';
import { Progress } from '../../components/Progress';
import ImportDialogSkeleton from '../dialog/ImportDialogSkeleton';

type Props = {
	flexDivCss?: String;
	buttonTitle: string;
};

const ImportButton = ({ flexDivCss, buttonTitle }: Props) => {
	const notes = [
		'It’s highly discouraged to import the demo on the site if you’ve already added the content.',
		'Import the demo on a fresh WordPress installation for an exact replication of the theme demo.',
		'It’ll automatically install and activate the plugins required for installing the chosen theme demo within your site.',
		'Copyright images will be replaced with other placeholder images.',
		'None of your previously existing posts, pages, attachments, or any other data will be deleted or modified.',
		'It’ll take some time to import the theme’s demo. Please be patient!',
	];

	const importNotes = [
		{
			content: 'Import Customizer Settings',
			activeContent: 'Import Customizer Settings',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: false,
			disabledToggle: false,
		},
		{
			content: 'Import Widgets',
			activeContent: 'Imports all the Widgets that is needed fot the demo.',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: false,
			disabledToggle: false,
		},
		{
			content: 'Import Content',
			activeContent: 'Import Content',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: false,
			disabledToggle: false,
		},
		{
			content: 'Install and Activate Necessary Plugins',
			activeContent: 'Install and Activate Necessary Plugins',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: true,
			disabledToggle: true,
		},
		{
			content: 'Everest Form',
			activeContent: 'Everest Form',
			hasDescription: true,
			isDescriptionActive: false,
			toggle: false,
			disabledToggle: false,
		},
		{
			content: 'Install WooCommerce',
			activeContent: 'Install WooCommerce',
			hasDescription: false,
			isDescriptionActive: false,
			toggle: false,
			disabledToggle: false,
		},
	];

	const [items, setItems] = useState(importNotes);
	const [step, setStep] = useState(0);
	const [installProgress, setInstallProgress] = useState(0);
	const [importProgress, setImportProgress] = useState(0);

	const STEP: Array<{
		header: React.ReactNode;
		content: React.FunctionComponent;
		footer: React.FunctionComponent | null;
	}> = [
		{
			header: 'Things to know',
			content: () => (
				<>
					<p className="bg-[#E9EFFD] border border-solid border-[#81A5F3] p-[16px] m-0 rounded text-[13px] sm:text-[14px]">
						Importing demo data ensures your site looks like the theme demo. You can simply modify
						the content instead of starting everything from scratch. Also, we recommend considering
						the following before importing the demo.
					</p>
					<ol className="pt-[16px] sm:pt-[32px] my-0 ml-[16px]">
						{notes.map((note, index) => (
							<li className={`${index === 0 && 'text-[#2563EB]'} mb-0`} key={index}>
								{note}
								{notes.length !== index + 1 && <hr className="my-[5px] sm:my-[16px] border-b-0" />}
							</li>
						))}
					</ol>
				</>
			),
			footer: () => (
				<>
					<div></div>
					<button
						type="button"
						className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[14px]"
						onClick={() => {
							setStep((prev) => prev + 1);
						}}
					>
						Continue
					</button>
				</>
			),
		},
		{
			header: 'Install Theme',
			content: () => (
				<>
					<div className="text-center mt-16 mb-12">
						<div className="border-[25px] border-solid border-[#FDFDFD] rounded-full inline-block ">
							<div className="border-[22px] border-solid border-[#f4f4f4] rounded-full block">
								<svg
									xmlns="http://www.w3.org/2000/svg"
									width="100"
									height="100"
									viewBox="0 0 128 127"
									fill="none"
									className="block"
								>
									<path
										d="M127.5 63.5C127.5 28.4299 99.0701 0 64 0C28.9299 0 0.5 28.4299 0.5 63.5C0.5 98.5701 28.9299 127 64 127C99.0701 127 127.5 98.5701 127.5 63.5Z"
										fill="#004846"
									/>
								</svg>
							</div>
						</div>
					</div>

					<p className="bg-[#E9EFFD] border border-solid border-[#81A5F3] p-[16px] m-0 rounded text-[14px]">
						By clicking “Install”, you’ll install the Zakra theme to ensure the layout matches the
						preview. Skipping the installation is strictly discouraged as it may result in a
						different layout.
					</p>
				</>
			),
			footer: () => (
				<>
					<button
						type="button"
						className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[14px]"
						onClick={() => {
							setStep((prev) => prev - 1);
						}}
					>
						Back
					</button>
					<div>
						<button
							type="button"
							className="cursor-pointer mr-[24px] bg-transparent text-[#2563EB] border-0 text-[14px]"
							onClick={() => {
								setStep((prev) => prev + 2);
							}}
						>
							Skip
						</button>
						<button
							type="button"
							className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[14px]"
							onClick={() => {
								setStep((prev) => prev + 1);
							}}
						>
							Install
						</button>
					</div>
				</>
			),
		},
		{
			header: 'Installing',
			content: () => (
				<>
					<div className="text-center mt-16 mb-12">
						<div className="border-[25px] border-solid border-[#FDFDFD] rounded-full inline-block ">
							<div className="border-[22px] border-solid border-[#f4f4f4] rounded-full block">
								<svg
									xmlns="http://www.w3.org/2000/svg"
									width="100"
									height="100"
									viewBox="0 0 128 127"
									fill="none"
									className="block"
								>
									<path
										d="M127.5 63.5C127.5 28.4299 99.0701 0 64 0C28.9299 0 0.5 28.4299 0.5 63.5C0.5 98.5701 28.9299 127 64 127C99.0701 127 127.5 98.5701 127.5 63.5Z"
										fill="#004846"
									/>
								</svg>
							</div>
						</div>
					</div>
					<Progress
						value={installProgress}
						className="mb-32 border border-solid border-[#81A5F3] overflow-visible "
						indicatorStyle={{ width: `${installProgress}%` }}
						progressContent={
							<div
								className={`absolute bg-[#2563EB] rounded-[8px] px-[4px] py-[3px] text-white font-[600] mt-[8px]`}
								style={{ left: `${installProgress - 2}%` }}
							>
								<p className="m-0 text-[8px]">{installProgress}%</p>
							</div>
						}
					/>
				</>
			),
			footer: null,
		},
		{
			header: 'Things to know',
			content: () => (
				<>
					<p className="bg-[#E9EFFD] border border-solid border-[#81A5F3] p-[16px] m-0 rounded text-[13px] sm:text-[14px]">
						Importing demo data ensures your site looks like the theme demo. You can simply modify
						the content instead of starting everything from scratch. Also, we recommend considering
						the following before importing the demo.
					</p>
					<ul className="pt-[16px] sm:pt-[32px] my-0 mx-0 list-none">
						{items.map((item, index) => (
							<li key={index}>
								<div className="flex justify-between items-center">
									<div className="flex items-center">
										<p className="m-0 mr-[8px] text-[13px] sm:text-[14px]">{item.content}</p>
										{item.hasDescription && (
											<button
												type="button"
												className="bg-transparent border-0 cursor-pointer"
												onClick={() => handleChange(index)}
											>
												{item.isDescriptionActive ? (
													<svg
														xmlns="http://www.w3.org/2000/svg"
														width="24"
														height="24"
														viewBox="0 0 24 24"
														fill="none"
													>
														<path
															d="M20.3977 11.9999C20.3977 7.35586 16.6297 3.59986 11.9977 3.59986C7.35366 3.59986 3.59766 7.35586 3.59766 11.9999C3.59766 16.6439 7.35366 20.3999 11.9977 20.3999C16.6297 20.3999 20.3977 16.6439 20.3977 11.9999ZM12.8377 13.7759H10.9657V13.2599C10.9657 12.8039 11.0617 12.4199 11.2537 12.0839C11.4457 11.7479 11.8057 11.3999 12.3097 11.0159C12.8017 10.6679 13.1257 10.3799 13.2817 10.1639C13.4497 9.94786 13.5217 9.69586 13.5217 9.41986C13.5217 9.11986 13.4137 8.89186 13.1857 8.72386C12.9577 8.56786 12.6457 8.49586 12.2377 8.49586C11.5417 8.49586 10.7377 8.72386 9.83766 9.17986L9.06966 7.64386C10.1137 7.05586 11.2297 6.75586 12.3937 6.75586C13.3657 6.75586 14.1337 6.99586 14.6977 7.45186C15.2737 7.91986 15.5497 8.54386 15.5497 9.31186C15.5497 9.82786 15.4417 10.2719 15.2017 10.6439C14.9737 11.0279 14.5177 11.4479 13.8697 11.9159C13.4137 12.2519 13.1377 12.5039 13.0177 12.6719C12.8977 12.8519 12.8377 13.0799 12.8377 13.3559V13.7759ZM11.0737 17.0639C10.8577 16.8599 10.7497 16.5599 10.7497 16.1879C10.7497 15.7919 10.8457 15.4919 11.0617 15.2879C11.2777 15.0839 11.5777 14.9879 11.9857 14.9879C12.3697 14.9879 12.6697 15.0959 12.8857 15.2999C13.1017 15.5039 13.2097 15.8039 13.2097 16.1879C13.2097 16.5479 13.1017 16.8479 12.8857 17.0519C12.6697 17.2679 12.3697 17.3759 11.9857 17.3759C11.5897 17.3759 11.2897 17.2679 11.0737 17.0639Z"
															fill="#2563EB"
														/>
													</svg>
												) : (
													<svg
														xmlns="http://www.w3.org/2000/svg"
														width="24"
														height="24"
														viewBox="0 0 24 24"
														fill="none"
													>
														<g opacity="0.3">
															<path
																d="M20.3977 11.9999C20.3977 7.35586 16.6297 3.59986 11.9977 3.59986C7.35366 3.59986 3.59766 7.35586 3.59766 11.9999C3.59766 16.6439 7.35366 20.3999 11.9977 20.3999C16.6297 20.3999 20.3977 16.6439 20.3977 11.9999ZM12.8377 13.7759H10.9657V13.2599C10.9657 12.8039 11.0617 12.4199 11.2537 12.0839C11.4457 11.7479 11.8057 11.3999 12.3097 11.0159C12.8017 10.6679 13.1257 10.3799 13.2817 10.1639C13.4497 9.94786 13.5217 9.69586 13.5217 9.41986C13.5217 9.11986 13.4137 8.89186 13.1857 8.72386C12.9577 8.56786 12.6457 8.49586 12.2377 8.49586C11.5417 8.49586 10.7377 8.72386 9.83766 9.17986L9.06966 7.64386C10.1137 7.05586 11.2297 6.75586 12.3937 6.75586C13.3657 6.75586 14.1337 6.99586 14.6977 7.45186C15.2737 7.91986 15.5497 8.54386 15.5497 9.31186C15.5497 9.82786 15.4417 10.2719 15.2017 10.6439C14.9737 11.0279 14.5177 11.4479 13.8697 11.9159C13.4137 12.2519 13.1377 12.5039 13.0177 12.6719C12.8977 12.8519 12.8377 13.0799 12.8377 13.3559V13.7759ZM11.0737 17.0639C10.8577 16.8599 10.7497 16.5599 10.7497 16.1879C10.7497 15.7919 10.8457 15.4919 11.0617 15.2879C11.2777 15.0839 11.5777 14.9879 11.9857 14.9879C12.3697 14.9879 12.6697 15.0959 12.8857 15.2999C13.1017 15.5039 13.2097 15.8039 13.2097 16.1879C13.2097 16.5479 13.1017 16.8479 12.8857 17.0519C12.6697 17.2679 12.3697 17.3759 11.9857 17.3759C11.5897 17.3759 11.2897 17.2679 11.0737 17.0639Z"
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
											onChange={() => handleToggle(index)}
											checked={item.toggle}
											disabled={item.disabledToggle}
										/>
										<div className="toggle-bg bg-white border border-solid border-[#111] h-4 w-8 rounded-full"></div>
									</label>
								</div>
								{item.isDescriptionActive && <p className="m-0 italic">{item.activeContent}</p>}
								{notes.length !== index + 1 && <hr className="my-[5px] sm:my-[16px] border-b-0" />}
							</li>
						))}
					</ul>
				</>
			),
			footer: () => (
				<>
					<button
						type="button"
						className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[14px]"
						onClick={() => {
							setStep((prev) => prev - 2);
						}}
					>
						Back
					</button>
					<button
						type="button"
						className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[14px]"
						onClick={() => {
							setStep((prev) => prev + 1);
						}}
					>
						Import
					</button>
				</>
			),
		},
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
			header: 'Optigo is successfully imported! Thank you for your patience',
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
						<p className="m-0 ml-[8px] text-[14px] text-[#6B6B6B]">Imported Customizer Settings</p>
					</div>
					<p className="m-0 text-[14px] text-[#6B6B6B]">
						PS: We try our best to use images free from legal perspectives. However, we do not take
						responsibility for any harm. We strongly advise website owners to replace the images and
						any copyrighted media before publishing them online.{' '}
					</p>
				</>
			),
			footer: () => (
				<>
					<button
						type="button"
						className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[14px] z-[50000]"
						onClick={() => {
							setStep((prev) => prev - 1);
						}}
					>
						Go to Dashboard
					</button>
					<div className="z-[50000] flex flex-nowrap sm:block">
						<button
							type="button"
							className="cursor-pointer mr-[10px] sm:mr-[24px] bg-transparent text-[#2563EB] border-0 text-[14px]"
							onClick={() => {
								setStep((prev) => prev - 1);
							}}
						>
							Customizer
						</button>
						<button
							type="button"
							className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[10px] sm:px-[24px] py-[10px] text-[14px]"
						>
							View Website
						</button>
					</div>
				</>
			),
		},
	];

	const currentStep = STEP[step];

	if (step === 2) {
		if (installProgress < 100) {
			setTimeout(() => {
				setInstallProgress(installProgress + 20);
			}, 1000);
		} else {
			setStep(step + 1);
		}
	}

	if (step === 4) {
		if (importProgress < 100) {
			setTimeout(() => {
				setImportProgress(importProgress + 20);
			}, 1000);
		} else {
			setStep(step + 1);
		}
	}

	const handleChange = (index: number) => {
		setItems((prevItems) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, isDescriptionActive: !item.isDescriptionActive } : item,
			),
		);
	};

	const handleToggle = (index: number) => {
		setItems((prevItems) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, toggle: !item.toggle } : item,
			),
		);
	};

	return (
		<div className={`${flexDivCss}`}>
			<ImportDialogSkeleton
				buttonTitle={buttonTitle}
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
