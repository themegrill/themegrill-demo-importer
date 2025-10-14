import { Button } from '@/starter-templates/components/ui/button';
import {
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/starter-templates/components/ui/dialog';
import { __ } from '@wordpress/i18n';
import { memo } from 'react';

export const Paywall = memo(() => {
	return (
		<>
			<DialogHeader>
				<DialogTitle className="text-center text-[26px] font-semibold leading-[44px]">
					<span>{__('Upgrade to Pro', 'themegrill-demo-importer')}</span>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="22"
						height="29"
						viewBox="0 0 22 29"
						fill="none"
						className=""
					>
						<path
							d="M18.926 22.124h-2.42V8.957a5.622 5.622 0 1 0-11.245 0v13.167h-2.42V8.957a8.044 8.044 0 1 1 16.085 0z"
							fill="#4A7EEE"
						/>
						<path
							d="M18.926 22.123h-2.42V8.955a5.622 5.622 0 1 0-11.245 0v13.168h-2.42V8.955a8.044 8.044 0 1 1 16.085 0z"
							fill="#000"
							opacity=".4"
						/>
						<path
							d="M20.858 13H1.142C.512 13 0 13.58 0 14.294v13.412C0 28.42.511 29 1.142 29h19.716c.63 0 1.142-.58 1.142-1.294V14.294C22 13.58 21.489 13 20.858 13"
							fill="#5182EF"
						/>
						<path
							d="M12.695 19.407a1.813 1.813 0 1 0-2.306 1.743v2.71h.99v-2.71a1.806 1.806 0 0 0 1.316-1.743"
							fill="#000"
							opacity=".5"
						/>
						<path
							d="M1.202 26.853c-.05 0-.089-2.323-.089-5.18s.039-5.18.09-5.18.09 2.324.09 5.18c0 2.857-.04 5.18-.09 5.18m8.265-5.124s-.085-.027-.213-.12a2.2 2.2 0 0 1-.466-.477 2.538 2.538 0 0 1 .902-3.81c.2-.104.416-.176.639-.215a.6.6 0 0 1 .244-.014c0 .035-.343.068-.813.341a2.52 2.52 0 0 0-.858 3.627q.255.357.565.668"
							fill="#FAFAFA"
						/>
					</svg>
				</DialogTitle>
				<DialogDescription className="text-center text-[#6B6B6B] text-[15px] leading-[25px]">
					{__(
						'This is premium template. Please upgrade to premium plans to access premium starter templates',
						'themegrill-demo-importer',
					)}
				</DialogDescription>
			</DialogHeader>
			<DialogFooter className="flex flex-col w-[400px] space-x-0 space-y-1 sm:space-x-0 mx-auto justify-center sm:flex-col">
				<Button className="w-full h-[51px] py-0 px-5 text-[15px] font-semibold text-[#FAFBFF]">
					{__('Upgrade Now', 'themegrill-demo-importer')}
				</Button>
				<Button
					className="h-[51px] py-0 px-5 text-[15px] text-[#6B6B6B] hover:bg-gray-100"
					variant="ghost"
				>
					{__('View all Premium Templates', 'themegrill-demo-importer')}
				</Button>
			</DialogFooter>
		</>
	);
});
