import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import Lottie from 'lottie-react';
import React, { useState } from 'react';
import spinner from '../../assets/animation/spinner.json';
import { Button } from '../../controls/Button';
import { themes } from '../../lib/themes';
import { Demo, TDIDashboardType } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';

type Props = {
	demo: Demo;
	proUpgrade: boolean;
	proActivate: boolean;
	setOpen: React.Dispatch<React.SetStateAction<boolean>>;
};

const DialogPro = ({ demo, proUpgrade, proActivate, setOpen }: Props) => {
	const { localizedData, setLocalizedData } = useLocalizedData();
	const [isActivating, setIsActivating] = useState(false);
	const matchedTheme = themes.find((theme) => theme.slug === demo.theme_slug);

	const activatePro = async (slug: string) => {
		setIsActivating(true);
		const proSlug = slug + '-pro';
		const response = await apiFetch<{
			success: boolean;
			message: string;
		}>({
			path: 'tg-demo-importer/v1/activate-pro',
			method: 'POST',
			data: {
				slug: proSlug,
			},
		});
		if (response.success) {
			const updated = await apiFetch<TDIDashboardType>({
				path: '/tg-demo-importer/v1/localized-data?refetch=true',
				method: 'GET',
			});
			setLocalizedData(updated);
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
					width="22"
					height="29"
					viewBox="0 0 22 29"
					fill="none"
				>
					<path
						d="M18.9263 22.1249H16.5058V8.95755C16.5058 7.46644 15.9135 6.03639 14.8591 4.98202C13.8047 3.92764 12.3747 3.33529 10.8835 3.33529C9.39242 3.33529 7.96238 3.92764 6.908 4.98202C5.85362 6.03639 5.26128 7.46644 5.26128 8.95755V22.1249H2.84081V8.95755C2.82426 7.89087 3.02006 6.83154 3.41682 5.84125C3.81358 4.85096 4.40336 3.94948 5.15184 3.1893C5.90032 2.42912 6.79253 1.82542 7.77655 1.41335C8.76057 1.00128 9.81672 0.789063 10.8835 0.789062C11.9504 0.789063 13.0065 1.00128 13.9905 1.41335C14.9745 1.82542 15.8668 2.42912 16.6152 3.1893C17.3637 3.94948 17.9535 4.85096 18.3503 5.84125C18.747 6.83154 18.9428 7.89087 18.9263 8.95755V22.1249Z"
						fill="#4A7EEE"
					/>
					<g opacity="0.4">
						<path
							d="M18.9263 22.1229H16.5058V8.9556C16.5058 7.46449 15.9135 6.03444 14.8591 4.98006C13.8047 3.92568 12.3747 3.33334 10.8835 3.33334C9.39242 3.33334 7.96238 3.92568 6.908 4.98006C5.85362 6.03444 5.26128 7.46449 5.26128 8.9556V22.1229H2.84081V8.9556C2.82426 7.88892 3.02006 6.82959 3.41682 5.8393C3.81358 4.84901 4.40336 3.94753 5.15184 3.18735C5.90032 2.42717 6.79253 1.82347 7.77655 1.41139C8.76057 0.999322 9.81672 0.787109 10.8835 0.787109C11.9504 0.787109 13.0065 0.999322 13.9905 1.41139C14.9745 1.82347 15.8668 2.42717 16.6152 3.18735C17.3637 3.94753 17.9535 4.84901 18.3503 5.8393C18.747 6.82959 18.9428 7.88892 18.9263 8.9556V22.1229Z"
							fill="black"
						/>
					</g>
					<path
						d="M20.8582 13H1.14176C0.511183 13 0 13.5795 0 14.2943V27.7056C0 28.4205 0.511183 29 1.14176 29H20.8582C21.4888 29 22 28.4205 22 27.7056V14.2943C22 13.5795 21.4888 13 20.8582 13Z"
						fill="#5182EF"
					/>
					<g opacity="0.5">
						<path
							d="M12.6952 19.4073C12.6954 19.0701 12.6015 18.7395 12.4241 18.4527C12.2467 18.1659 11.9928 17.9343 11.6909 17.7839C11.389 17.6335 11.0512 17.5704 10.7154 17.6015C10.3796 17.6326 10.0591 17.7569 9.79006 17.9602C9.52099 18.1635 9.31399 18.4379 9.19234 18.7524C9.07069 19.067 9.03921 19.4092 9.10144 19.7407C9.16367 20.0721 9.31715 20.3796 9.54461 20.6286C9.77207 20.8776 10.0645 21.0582 10.389 21.1501V23.861H11.3785V21.1501C11.7581 21.0431 12.0923 20.8149 12.33 20.5002C12.5678 20.1856 12.696 19.8017 12.6952 19.4073Z"
							fill="black"
						/>
					</g>
					<path
						d="M1.20235 26.8537C1.15201 26.8537 1.11328 24.5301 1.11328 21.6739C1.11328 18.8178 1.15201 16.4941 1.20235 16.4941C1.2527 16.4941 1.29336 18.8178 1.29336 21.6739C1.29336 24.5301 1.2527 26.8537 1.20235 26.8537Z"
						fill="#FAFAFA"
					/>
					<path
						d="M9.46833 21.7287C9.46833 21.7287 9.38314 21.7016 9.25534 21.6087C9.07622 21.4748 8.91881 21.3141 8.78867 21.1323C8.56266 20.834 8.40539 20.4895 8.32813 20.1233C8.25087 19.7571 8.25552 19.3784 8.34175 19.0143C8.42798 18.6501 8.59365 18.3095 8.82691 18.0169C9.06018 17.7242 9.35525 17.4868 9.69102 17.3215C9.89188 17.2179 10.1073 17.1454 10.33 17.1066C10.4095 17.0848 10.4926 17.0802 10.574 17.093C10.574 17.1279 10.2313 17.1608 9.76073 17.4338C9.4536 17.6036 9.18538 17.8358 8.97328 18.1154C8.76118 18.395 8.60988 18.7159 8.52911 19.0574C8.44833 19.3989 8.43985 19.7535 8.50423 20.0985C8.5686 20.4435 8.70441 20.7712 8.90291 21.0607C9.07286 21.2984 9.26196 21.5218 9.46833 21.7287Z"
						fill="#FAFAFA"
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
					<a
						href={matchedTheme?.pricing_link ?? '#'}
						target="_blank"
						rel="noopener noreferrer"
						className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
					>
						{__('Upgrade Now', 'themegrill-demo-importer')}
					</a>
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
							onClick={() => activatePro(demo.theme_slug)}
						>
							{__('Activate Now', 'themegrill-demo-importer')}
						</Button>
					))}
				<Button className="mt-4 cursor-pointer text-[14px] text-[#6B6B6B] leading-[19px] border-0 bg-transparent font-normal w-full p-0 h-5 hover:bg-transparent hover:border-0">
					{__('View all Premium Templates', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	);
};

export default DialogPro;
