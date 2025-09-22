import { useRouter } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import Lottie from 'lottie-react';
import React, { useState } from 'react';
import spinner from '../../../../../../assets/animation/spinner.json';
import { queryClient } from '../../../../../../lib/query-client';
import { themes } from '../../../../../../lib/themes';
import { Demo } from '../../../../../../lib/types';
import { useLocalizedData } from '../../../../../../LocalizedDataContext';
import { Button } from '../../../../../ui/Button';
import { activateProQueryOptions, localizedDataQueryOptions } from '../../../../api/import.api';

type Props = {
	demo: Demo;
	proUpgrade: boolean;
	proActivate: boolean;
	setOpen: React.Dispatch<React.SetStateAction<boolean>>;
};

const DialogPro = ({ demo, proUpgrade, proActivate, setOpen }: Props) => {
	const router = useRouter();
	const { localizedData, setLocalizedData } = useLocalizedData();
	const [isActivating, setIsActivating] = useState(false);
	const matchedTheme = themes.find((theme) => theme.slug === demo?.theme_slug);

	const activatePro = async (slug: string) => {
		setIsActivating(true);
		const proSlug = slug + '-pro';
		const response = await queryClient.ensureQueryData(activateProQueryOptions({ id: proSlug }));
		if (response.success) {
			const localizedResponse = await queryClient.ensureQueryData(
				localizedDataQueryOptions({ refetch: true }),
			);
			setLocalizedData(localizedResponse);
			setIsActivating(false);
			setOpen(false);
		}
	};
	return (
		<div className="pt-[50px] pb-[55px] px-[40px] text-center">
			<div className="flex items-center justify-center gap-3 mb-[6px]">
				<h2 className="text-[26px] leading-[44px] text-[#131313] m-0">
					{__('Upgrade to Pro', 'themegrill-demo-importer')}
				</h2>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					viewBox="0 0 22 29"
					width="22"
					height="28"
				>
					<path
						fill="#4A7EEE"
						d="M18.926 22.124h-2.42V8.957a5.622 5.622 0 1 0-11.245 0v13.167h-2.42V8.957a8.044 8.044 0 1 1 16.085 0v13.167Z"
					/>
					<path
						fill="#000"
						d="M18.926 22.123h-2.42V8.955a5.622 5.622 0 1 0-11.245 0v13.168h-2.42V8.955a8.044 8.044 0 1 1 16.085 0v13.168Z"
						opacity=".4"
					/>
					<path
						fill="#5182EF"
						d="M20.858 13H1.142C.512 13 0 13.58 0 14.294v13.412C0 28.42.511 29 1.142 29h19.716c.63 0 1.142-.58 1.142-1.294V14.294C22 13.58 21.489 13 20.858 13Z"
					/>
					<path
						fill="#000"
						d="M12.695 19.407a1.813 1.813 0 1 0-2.306 1.743v2.71h.99v-2.71a1.806 1.806 0 0 0 1.316-1.743Z"
						opacity=".5"
					/>
					<path
						fill="#FAFAFA"
						d="M1.202 26.853c-.05 0-.089-2.323-.089-5.18 0-2.856.039-5.18.09-5.18.05 0 .09 2.324.09 5.18 0 2.857-.04 5.18-.09 5.18ZM9.468 21.729s-.085-.027-.213-.12a2.177 2.177 0 0 1-.466-.477 2.538 2.538 0 0 1 .902-3.81c.2-.104.416-.176.639-.215a.581.581 0 0 1 .244-.014c0 .035-.343.068-.813.341a2.518 2.518 0 0 0-.858 3.627c.17.238.359.461.565.668Z"
					/>
				</svg>
			</div>
			<p className="text-[15px] leading-[25px] text-[#6B6B6B] mt-0 mb-[32px] px-[30px]">
				{__(
					'This is a premium template. Please upgrade to premium plans to access premium starter templates.',
					'themegrill-demo-importer',
				)}
			</p>
			<div className="px-[49px]">
				{proUpgrade && (
					<Button
						className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md border-none w-full bg-[#2563EB] hover:bg-[#2563EB] cursor-pointer no-underline"
						onClick={() => window.open(matchedTheme?.pricing_link ?? '#', '_blank')}
					>
						{__('Upgrade Now', 'themegrill-demo-importer')}
					</Button>
				)}
				{proActivate &&
					(isActivating ? (
						<Button className="flex item-center px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB]/90 border-none w-full hover:bg-[#2563EB]/90 cursor-not-allowed">
							<Lottie animationData={spinner} loop={true} autoplay={true} className="h-10" />
							{__('Activating...', 'themegrill-demo-importer')}
						</Button>
					) : (
						<Button
							className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] no-underline border-none w-full hover:bg-[#2563EB] hover:text-[#FAFBFF] cursor-pointer"
							onClick={() => activatePro(demo?.theme_slug)}
						>
							{__('Activate Now', 'themegrill-demo-importer')}
						</Button>
					))}
				<Button
					className="mt-4 cursor-pointer text-[14px] text-[#6B6B6B] leading-[19px] border-0 bg-transparent font-normal w-full p-0 h-5 hover:bg-transparent hover:border-0"
					onClick={() =>
						router.navigate({
							to: '/',
							search: {
								search: undefined,
								builder: undefined,
								category: undefined,
							},
							replace: true,
						})
					}
				>
					{__('View all Premium Templates', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	);
};

export default DialogPro;
