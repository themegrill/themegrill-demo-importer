import { __ } from '@wordpress/i18n';
import Lottie from 'lottie-react';
import React, { useState } from 'react';
import { useLocalizedData } from '../../../../../../LocalizedDataContext';
import confetti from '../../../../../../assets/animation/confetti.json';
import { Demo } from '../../../../../../lib/types';
import { Button } from '../../../../../ui/Button';
import { DialogClose } from '../../../../../ui/Dialog';

const DialogImported = ({ demo }: { demo: Demo }) => {
	const { localizedData } = useLocalizedData();
	const [showConfetti, setShowConfetti] = useState(true);

	const handleComplete = () => {
		setShowConfetti(false);
	};

	return (
		<>
			<div className="pt-[50px] pb-[55px] px-[40px] text-center">
				<h2 className="text-[26px] leading-[44px] text-[#131313] mt-0 mb-[6px]">
					{__('Congratulations!', 'themegrill-demo-importer')}
				</h2>
				<p className="text-[15px] leading-[25px] text-[#6B6B6B] mt-0 mb-[32px] px-[30px]">
					{__(
						'Import complete! Your site is now live with your selected template.',
						'themegrill-demo-importer',
					)}
				</p>
				<div className="px-[49px]">
					<Button
						className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
						onClick={() => {
							window.open(localizedData.siteUrl, '_blank');
						}}
					>
						{__('View Your Site', 'themegrill-demo-importer')}
					</Button>

					<DialogClose asChild>
						<Button
							className="mt-4 cursor-pointer text-[14px] text-[#6B6B6B] leading-[19px] border-0 bg-transparent font-normal w-full p-0 h-5 hover:bg-transparent hover:border-0"
							onClick={() => {
								window.location.href = `${localizedData.siteUrl}/wp-admin/`;
							}}
						>
							{__('Back to Dashboard', 'themegrill-demo-importer')}
						</Button>
					</DialogClose>
				</div>
			</div>
			{showConfetti && (
				<Lottie
					animationData={confetti}
					loop={false}
					autoplay={true}
					onComplete={handleComplete}
					style={{ width: '100%' }}
					className="absolute top-[-100px] pointer-events-none z-10"
				/>
			)}
		</>
	);
};

export default DialogImported;
