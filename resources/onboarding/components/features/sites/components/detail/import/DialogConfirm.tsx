import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { __ } from '@wordpress/i18n';
import { Check } from 'lucide-react';
import { useState } from 'react';
import { Button } from '../../../../../ui/Button';
import { DialogClose } from '../../../../../ui/Dialog';

type Props = {
	onConfirm: (allowContribution: boolean) => void;
};

const DialogConfirm = ({ onConfirm }: Props) => {
	const [allowContribution, setAllowContribution] = useState(false);

	return (
		<div className="pt-[50px] pb-[55px] px-[40px] text-center">
			<h2 className="text-[26px] leading-[44px] text-[#131313] mt-0 mb-[6px]">
				{__('Ready to Import?', 'themegrill-demo-importer')}
			</h2>
			<p className="text-[15px] leading-[25px] text-[#6B6B6B] mt-0 mb-[24px] px-[30px]">
				{__(
					'Importing this template adds content to your site and overwrites current theme settings.',
					'themegrill-demo-importer',
				)}
			</p>
			<div className="mb-[28px] px-[20px]">
				<label
					htmlFor="tdi-allow-contribution"
					className="flex items-center gap-3 px-4 py-3 rounded-lg border border-[#E8EAED] bg-[#F8F9FF] cursor-pointer hover:border-[#2563EB] hover:bg-[#EEF3FF] transition-colors duration-150"
				>
					<CheckboxPrimitive.Root
						id="tdi-allow-contribution"
						checked={allowContribution}
						onCheckedChange={(checked) => setAllowContribution(checked === true)}
						className="flex items-center justify-center flex-shrink-0 w-[18px] h-[18px] rounded-[4px] border-2 border-[#C8CDD6] bg-white transition-all duration-150 data-[state=checked]:bg-[#2563EB] data-[state=checked]:border-[#2563EB] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-1"
					>
						<CheckboxPrimitive.Indicator>
							<Check size={12} strokeWidth={3} className="text-white" />
						</CheckboxPrimitive.Indicator>
					</CheckboxPrimitive.Root>
					<span className="text-[13px] text-[#4B5563] leading-[20px] text-left select-none">
						{__('Allow usage data contribution to help us improve the theme.', 'themegrill-demo-importer')}{' '}
						<a
							href="https://themegrill.com/privacy-policy/"
							target="_blank"
							rel="noopener noreferrer"
							onClick={(e) => e.stopPropagation()}
							className="text-[#2563EB] no-underline hover:underline"
						>
							{__('Learn more', 'themegrill-demo-importer')}
						</a>
					</span>
				</label>
			</div>
			<div className="px-[49px]">
				<Button
					className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
					onClick={() => onConfirm(allowContribution)}
				>
					{__('Start Import', 'themegrill-demo-importer')}
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

export default DialogConfirm;
