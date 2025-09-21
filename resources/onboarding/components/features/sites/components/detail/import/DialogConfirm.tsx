import { __ } from '@wordpress/i18n';
import { Button } from '../../../../../ui/Button';
import { DialogClose } from '../../../../../ui/Dialog';

type Props = {
	onConfirm: () => void;
};

const DialogConfirm = ({ onConfirm }: Props) => {
	return (
		<div className="pt-[50px] pb-[55px] px-[40px] text-center">
			<h2 className="text-[26px] leading-[44px] text-[#131313] mt-0 mb-[6px]">
				{__('Ready to Import?', 'themegrill-demo-importer')}
			</h2>
			<p className="text-[15px] leading-[25px] text-[#6B6B6B] mt-0 mb-[32px] px-[30px]">
				{__(
					'Importing this template adds content to your site and overwrites current theme settings.',
					'themegrill-demo-importer',
				)}
			</p>
			<div className="px-[49px]">
				<Button
					className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
					onClick={onConfirm}
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
