import { __ } from '@wordpress/i18n';
import { Info } from 'lucide-react';
import React from 'react';
import { Button } from '../../../../../ui/Button';
import { DialogClose } from '../../../../../ui/Dialog';

const DialogImportFailed = ({ handleTryAgain }: { handleTryAgain: () => void }) => {
	return (
		<div className="pt-[50px] pb-[55px] px-[40px] text-center">
			<div className="flex items-center justify-center gap-3 mb-[6px]">
				<h2 className="text-[26px] leading-[44px] text-[#131313] m-0 ">
					{__('Import Failed', 'themegrill-demo-importer')}
				</h2>
				<Info size={26} color="#E67E22" />
			</div>

			<p className="text-[15px] leading-[25px] text-[#6B6B6B] mt-0 mb-[32px] px-[30px]">
				{__(
					'Unable to import the template. If the problem continues, refer to our ',
					'themegrill-demo-importer',
				)}
				<a
					href="https://docs.themegrill.com/themegrill-demo-importer/"
					target="_blank"
					rel="noopener noreferrer"
					className="text-blue-600 hover:text-blue-800 underline"
				>
					{__('documentation', 'themegrill-demo-importer')}
				</a>
				{__('.', 'themegrill-demo-importer')}
			</p>
			<div className="px-[49px]">
				<Button
					className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
					onClick={handleTryAgain}
				>
					{__('Try Again', 'themegrill-demo-importer')}
				</Button>

				<DialogClose asChild>
					<Button className="mt-4 cursor-pointer text-[14px] text-[#6B6B6B] leading-[19px] border-0 bg-transparent font-normal w-full p-0 h-5 hover:bg-transparent hover:border-0">
						{__('Cancel', 'themegrill-demo-importer')}
					</Button>
				</DialogClose>
			</div>
		</div>
	);
};

export default DialogImportFailed;
