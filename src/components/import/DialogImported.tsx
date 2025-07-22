import { __, sprintf } from '@wordpress/i18n';
import Lottie from 'lottie-react';
import React from 'react';
import confetti from '../../assets/animation/confetti.json';
import { DialogFooter, DialogHeader, DialogTitle } from '../../controls/Dialog';
import { SearchResultType, TDIDashboardType } from '../../lib/types';

const DialogImported = ({ demo, data }: { demo: SearchResultType; data: TDIDashboardType }) => {
	// const { data } = useLocalizedData();

	return (
		<>
			<p className="text-[#4CC741] absolute bottom-[480px] left-[15%] sm:bottom-[418px] sm:left-[22%] text-[30px] sm:text-[48px] lily-script-one-regular m-0 mb-[32px]">
				{__('Congratulation!!', 'themegrill-demo-importer')}
			</p>
			<DialogHeader className="border-0 border-b border-solid border-[#f4f4f4] px-[40px] py-[20px]">
				<DialogTitle className="my-0 text-[18px] text-[#383838]">
					{sprintf(
						__(
							'%s is successfully imported! Thank you for your patience.',
							'themegrill-demo-importer',
						),
						demo.name,
					)}
				</DialogTitle>
			</DialogHeader>
			<div className="px-[40px] pt-[20px] pb-[48px] overflow-x-hidden overflow-y-scroll sm:overflow-hidden">
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
						{__(
							'PS: We try our best to use images free from legal perspectives. However, we do not take responsibility for any harm. We strongly advise website owners to replace the images and any copyrighted media before publishing them online.',
							'themegrill-demo-importer',
						)}
					</p>
				</>
			</div>
			<DialogFooter className="border-0 border-t border-solid border-[#f4f4f4] p-[16px] sm:py-[16px] sm:px-[40px] flex items-center justify-between flex-row sm:justify-between">
				<a
					type="button"
					className="cursor-pointer px-0 bg-transparent text-[#2563EB] border-0 text-[16px] z-[50000] no-underline"
					href={`${data.siteUrl}/wp-admin/`}
				>
					{__('Go to Dashboard', 'themegrill-demo-importer')}
				</a>
				<div className="z-[50000] flex flex-nowrap sm:block items-center ">
					<a
						type="button"
						className="cursor-pointer mr-[10px] sm:mr-[24px] bg-transparent text-[#2563EB] border-0 text-[16px] no-underline"
						href={`${data.siteUrl}/wp-admin/customize.php`}
					>
						{__('Customizer', 'themegrill-demo-importer')}
					</a>
					<button
						type="button"
						className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[10px] sm:px-[24px] py-[10px] text-[16px] "
						onClick={() => {
							window.open(data.siteUrl, '_blank');
						}}
					>
						{__('View Website', 'themegrill-demo-importer')}
					</button>
				</div>
			</DialogFooter>
			<Lottie
				animationData={confetti}
				loop={true}
				autoplay={true}
				style={{ width: '100%' }}
				className="absolute bottom-[-100px] sm:bottom-[-270px] top-0"
			/>
		</>
	);
};

export default DialogImported;
