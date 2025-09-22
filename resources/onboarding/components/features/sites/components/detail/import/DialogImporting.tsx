import { __ } from '@wordpress/i18n';
import Lottie from 'lottie-react';
import React from 'react';
import spinner from '../../../../../../assets/animation/spinner.json';
import { Progress } from '../../../../../ui/Progress';

type Props = {
	importProgress: number;
	importProgressImportDetail: string;
};

const DialogImporting = ({ importProgress, importProgressImportDetail }: Props) => {
	return (
		<div className="pt-[50px] pb-[55px] px-[40px] text-center">
			<h2 className="text-[26px] leading-[44px] text-[#131313] mt-0 mb-[6px] flex justify-center items-center ">
				{__('Importing your site', 'themegrill-demo-importer')}
				<Lottie animationData={spinner} loop={true} autoplay={true} className="h-[48px]" />
			</h2>
			<p className="text-[15px] leading-[25px] text-[#6B6B6B] mt-0 mb-[32px] px-[30px]">
				{__(
					'It might take a couple of minutes. Please do not close or refresh this page.',
					'themegrill-demo-importer',
				)}
			</p>
			<div className="px-[49px]">
				<Progress
					value={importProgress}
					className="rounded-md h-[51px] bg-[#E9EFFD] overflow-visible  "
					indicatorStyle={{ width: `${importProgress}%` }}
					indicatorClassName={`bg-[#2563EB] h-[50px] rounded-none  ${importProgress > 1 ? 'border border-solid border-[#2563EB] rounded-l-md' : ''} ${importProgress === 100 ? 'rounded-r-md' : 'border-r-0 '}`}
					progressContent={
						<div
							className={`absolute inset-0 w-full flex items-center justify-center ${
								importProgress > 50 ? 'text-white' : 'text-[#6B6B6B]'
							}`}
						>
							<p className="m-0 text-[14px] font-semibold leading-[21px]">{importProgress}%</p>
						</div>
					}
				/>

				<p className="mt-4 text-[14px] text-[#6B6B6B] leading-[19px] border-0 bg-transparent font-normal w-full p-0 h-5 hover:bg-transparent hover:border-0">
					{importProgressImportDetail}
				</p>
			</div>
		</div>
	);
};

export default DialogImporting;
