import { __ } from '@wordpress/i18n';
import React from 'react';
import { DialogHeader, DialogTitle } from '../../controls/Dialog';
import { Progress } from '../../controls/Progress';

const DialogImporting = ({
	importProgress,
	importProgressStepTitle,
	importProgressStepSubTitle,
}: {
	importProgress: number;
	importProgressStepTitle: string;
	importProgressStepSubTitle: string;
}) => {
	return (
		<>
			<DialogHeader className="border-0 border-b border-solid border-[#f4f4f4] px-[40px] py-[20px]">
				<DialogTitle className="my-0 text-[18px] text-[#383838]">
					{__('Importing...', 'themegrill-demo-importer')}
				</DialogTitle>
			</DialogHeader>
			<div className="px-[40px] pt-[20px] pb-[48px] overflow-x-hidden overflow-y-scroll sm:overflow-hidden">
				<>
					<p className="m-0 text-[14px] text-[#6B6B6B]">
						{__(
							'It might take around 5 to 10 minutes to complete the importation process. Please do not close or refresh this page!',
							'themegrill-demo-importer',
						)}
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
						<p className="m-0 text-[14px] text-[#6B6B6B]">{importProgressStepTitle}</p>
					</div>
					<Progress
						value={importProgress}
						className="border border-solid border-[#f4f4f4] h-[83px] rounded-[7px] overflow-visible  "
						indicatorClassName={`bg-[#E9EFFD] h-[81px] rounded-none  ${importProgress > 1 ? 'border border-solid border-[#2563EB] rounded-l-[7px]' : ''} ${importProgress === 100 ? 'rounded-r-[7px]' : 'border-r-0 '}`}
						indicatorStyle={{ width: `${importProgress}%` }}
						progressContent={
							<div className="text-[#383838] p-[19px] absolute top-0 rounded-l-[7px]">
								<p className="m-0 mb-[4px] text-[14px]">
									{importProgressStepTitle} {importProgress}%
								</p>
								<p className="m-0 text-[#6B6B6B] text-12px]">{importProgressStepSubTitle}</p>
							</div>
						}
					/>
				</>
			</div>
		</>
	);
};

export default DialogImporting;
