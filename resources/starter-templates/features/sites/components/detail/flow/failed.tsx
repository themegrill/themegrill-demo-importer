import { Button } from '@/starter-templates/components/ui/button';
import {
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/starter-templates/components/ui/dialog';
import { __ } from '@wordpress/i18n';
import { memo } from 'react';

export const Failed = memo(() => {
	return (
		<>
			<DialogHeader>
				<DialogTitle className="text-center text-[26px] font-semibold leading-[44px]">
					{__('Congratulations!', 'themegrill-demo-importer')}
				</DialogTitle>
				<DialogDescription className="text-center text-[#6B6B6B] text-[15px] leading-[25px]">
					{__(
						'Import complete! your site is now live with your selected template.',
						'themegrill-demo-importer',
					)}
				</DialogDescription>
			</DialogHeader>
			<DialogFooter className="flex flex-col w-[400px] space-x-0 space-y-1 sm:space-x-0 mx-auto justify-center sm:flex-col">
				<Button className="w-full h-[51px] py-0 px-5 text-[15px] font-semibold text-[#FAFBFF]">
					{__('View Your Site', 'themegrill-demo-importer')}
				</Button>
				<Button
					className="h-[51px] py-0 px-5 text-[15px] text-[#6B6B6B] hover:bg-gray-100"
					variant="ghost"
				>
					{__('Back to Dashboard', 'themegrill-demo-importer')}
				</Button>
			</DialogFooter>
		</>
	);
});
