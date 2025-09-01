import { useRouter } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { ArrowLeft, Monitor, Smartphone, Tablet } from 'lucide-react';
import React from 'react';
import { Demo } from '../../../../../../lib/types';
import { Button } from '../../../../../ui/Button';
import {
	Tooltip,
	TooltipContent,
	TooltipProvider,
	TooltipTrigger,
} from '../../../../../ui/Tooltip';
import LogoUploader from '../uploader/LogoUploader';

type Props = {
	demo: Demo;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	setSiteLogoId: (value: number) => void;
	device: string;
	setDevice: (value: string) => void;
	pageImport: string;
	setPageImport: (value: string) => void;
	onContinue?: () => void;
	setIsPagesSelected: (value: boolean) => void;
};

const Sidebar = ({
	demo,
	iframeRef,
	setSiteLogoId,
	device,
	setDevice,
	pageImport,
	setPageImport,
	onContinue,
	setIsPagesSelected,
}: Props) => {
	const colorPalette = [
		['#FD6611', '#EA0722', '#524F51', '#8F8F8F', '#D3D3D3'],
		['#2563EB', '#00FF5D', '#524F51', '#8F8F8F', '#D3D3D3'],
		['#CA04CE', '#5EA396', '#EFDF30', '#8F8F8F', '#D3D3D3'],
		['#21587B', '#5EA396', '#57CC98', '#80EB9A', '#C6FACC'],
		['#FF7577', '#E28386', '#BD999D', '#9FC8CB', '#7BF4F3'],
		['#9481FF', '#9494FF', '#FFA3FF', '#9C84B3', '#FFDFC9'],
	];
	const typography = [
		'Inter',
		'Lato',
		'Playfair Display',
		'IBM Plex Sans Thai Looped',
		'Raleway',
		'DM Sans',
		'Nunito',
		'Courier New',
		'Quando',
	];
	const router = useRouter();

	return (
		<div className="w-[350px] min-w-[350px] flex flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] ">
			<div className="px-6 pt-6">
				<div className="pb-6 border-0 border-b border-solid border-[#E3E3E3]">
					<div className="flex justify-between items-center">
						<h3 className="text-[20px] leading-7 m-0">
							{demo.title ||
								demo.slug.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())}
						</h3>
						<Tooltip>
							<TooltipTrigger asChild>
								<ArrowLeft
									size={24}
									color="#909090"
									strokeWidth={2}
									onClick={() => router.history.back()}
									className="cursor-pointer"
								/>
							</TooltipTrigger>
							<TooltipContent side="bottom" sideOffset={-4}>
								{__('Back to Starter Templates', 'themegrill-demo-importer')}
							</TooltipContent>
						</Tooltip>
					</div>
					<p className="text-[14px] text-[#6B6B6B] leading-[21px] m-0 mt-[6px] ">
						{__('Add your branding: logo, colors & fonts.', 'themegrill-demo-importer')}
					</p>
				</div>
			</div>
			<div className="flex flex-col gap-6 box-border px-6 pt-6 pb-10 overflow-y-auto tg-scrollbar">
				<LogoUploader iframeRef={iframeRef} setSiteLogoId={setSiteLogoId} />
				<div>
					<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5">
						{__('Color Palette', 'themegrill-demo-importer')}
					</h3>
					<div className="grid grid-cols-2 gap-[14px]">
						<TooltipProvider>
							{colorPalette.map((colors, index) => (
								<div
									className="border-2 border-solid border-[#EEEFF2] bg-[#FDFDFE] rounded-md p-[6px] flex hover:border-[#5182EF]"
									key={index}
								>
									{colors.map((color, index) => (
										<Tooltip key={index}>
											<TooltipTrigger asChild>
												<div
													className={`h-[30px] w-[25px] ${
														index === 0
															? 'rounded-l-md'
															: index === colors.length - 1
																? 'rounded-r-md'
																: ''
													}`}
													style={{ backgroundColor: color }}
												/>
											</TooltipTrigger>
											<TooltipContent side="bottom" sideOffset={-15}>
												{color}
											</TooltipContent>
										</Tooltip>
									))}
								</div>
							))}
						</TooltipProvider>
					</div>
				</div>
				<div>
					<div className="mb-5">
						<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5 leading-normal">
							{__('Typography', 'themegrill-demo-importer')}
						</h3>
						<div className="grid grid-cols-3 gap-[14px]">
							<TooltipProvider>
								{typography.map((t, index) => (
									<Tooltip key={index}>
										<TooltipTrigger asChild>
											<button
												className="border-2 border-solid border-[#EEEFF2] bg-[#FDFDFE] px-6 py-[10px] rounded-md cursor-pointer w-[88px] hover:border-[#5182EF]"
												style={{ fontFamily: t }}
												key={index}
											>
												<p className="text-[15px] text-[#6B6B6B] font-bold leading-[22px] tracking-[0.15px] m-0 ">
													Ag
												</p>
											</button>
										</TooltipTrigger>
										<TooltipContent side="bottom" sideOffset={-15}>
											{t}
										</TooltipContent>
									</Tooltip>
								))}
							</TooltipProvider>
						</div>
					</div>
					<div>
						<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5">
							{__('Import', 'themegrill-demo-importer')}
						</h3>
						<div className="bg-[#fff] border-2 border-solid border-[#EDEDED] rounded-md flex p-[9px]">
							<Button
								className={`font-normal cursor-pointer flex-1 text-[14x] ${pageImport === 'all' ? 'bg-[#E9EFFD] border-2 border-solid border-[#5182EF] text-[#2563EB] hover:bg-[#E9EFFD] hover:border-2 hover:border-solid hover:border-[#5182EF] hover:text-[#2563EB]' : 'bg-transparent border-2 border-solid border-[#fff] text-[#646464] hover:bg-transparent hover:border-2 hover:border-solid hover:border-[#fff] hover:text-[#646464]'}`}
								onClick={() => setPageImport('all')}
							>
								{__('All Pages', 'themegrill-demo-importer')}
							</Button>
							<Button
								className={`font-normal cursor-pointer flex-1 text-[14x] ${pageImport === 'selected' ? 'bg-[#E9EFFD] border-2 border-solid border-[#5182EF] text-[#2563EB] hover:bg-[#E9EFFD] hover:border-2 hover:border-solid hover:border-[#5182EF] hover:text-[#2563EB]' : 'bg-transparent border-2 border-solid border-[#fff] text-[#646464] hover:bg-transparent hover:border-2 hover:border-solid hover:border-[#fff] hover:text-[#646464]'}`}
								onClick={() => {
									setPageImport('selected');
									setIsPagesSelected(true);
								}}
							>
								{__('Select Pages', 'themegrill-demo-importer')}
							</Button>
						</div>
					</div>
				</div>
			</div>
			<div className="border-0 border-t border-r border-solid border-[#E9E9E9] bg-white p-[24px] pb-[12px]">
				<Button
					className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
					onClick={onContinue}
				>
					{__('Continue', 'themegrill-demo-importer')}
				</Button>
				<div className="flex gap-[10px] mt-4 justify-center">
					<Monitor
						size={20}
						className="cursor-pointer"
						color={`${device === 'desktop' ? '#2563EB' : '#737373'}`}
						onClick={() => setDevice('desktop')}
					/>
					<Tablet
						size={20}
						className="cursor-pointer"
						color={`${device === 'tablet' ? '#2563EB' : '#737373'}`}
						onClick={() => setDevice('tablet')}
					/>
					<Smartphone
						size={20}
						className="cursor-pointer"
						color={`${device === 'mobile' ? '#2563EB' : '#737373'}`}
						onClick={() => setDevice('mobile')}
					/>
				</div>
			</div>
		</div>
	);
};

export default Sidebar;
