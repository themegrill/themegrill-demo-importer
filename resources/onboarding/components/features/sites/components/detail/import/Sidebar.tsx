import { __ } from '@wordpress/i18n';
import { Monitor, Smartphone, Tablet } from 'lucide-react';
import React, { useEffect } from 'react';
import { Demo } from '../../../../../../lib/types';
import { Button } from '../../../../../ui/Button';
import {
	Tooltip,
	TooltipContent,
	TooltipProvider,
	TooltipTrigger,
} from '../../../../../ui/Tooltip';
import LogoUploader from '../uploader/LogoUploader';
import SidebarHeader from './SidebarHeader';

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
	colorPalette: string[][];
	typography: string[][];
	selectedPaletteIndex: number;
	setSelectedPaletteIndex: (value: number) => void;
	selectedTypographyIndex: number;
	setSelectedTypographyIndex: (value: number) => void;
	isThemeSupported: boolean;
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
	colorPalette,
	typography,
	selectedPaletteIndex,
	setSelectedPaletteIndex,
	selectedTypographyIndex,
	setSelectedTypographyIndex,
	isThemeSupported,
}: Props) => {
	const handleColorPalette = (index: number) => {
		try {
			if (!iframeRef?.current?.contentWindow) {
				console.warn('Iframe not available');
				return;
			}
			setSelectedPaletteIndex(index);

			// Send message to iframe
			iframeRef.current?.contentWindow?.postMessage(
				{
					type: 'UPDATE_COLOR_PALETTE',
					theme: demo.theme_slug,
					colorPalette: colorPalette[index],
				},
				'*',
			);

			// Listen for confirmation
			const handleMessage = (event: MessageEvent) => {
				if (event.data.type === 'COLOR_PALETTE_UPDATED') {
					// console.log('Color Palette updated successfully:', event.data.success);
					window.removeEventListener('message', handleMessage);
				}
			};

			window.addEventListener('message', handleMessage);

			// Cleanup after timeout
			setTimeout(() => {
				window.removeEventListener('message', handleMessage);
			}, 5000);
		} catch (error) {
			console.error('Error sending color update message:', error);
		}
	};

	const handleTypography = (index: number) => {
		try {
			if (!iframeRef?.current?.contentWindow) {
				console.warn('Iframe not available');
				return;
			}

			setSelectedTypographyIndex(index);

			// Send message to iframe
			iframeRef.current?.contentWindow?.postMessage(
				{
					type: 'UPDATE_TYPOGRAPHY',
					typography: typography[index],
				},
				'*',
			);

			// Listen for confirmation
			const handleMessage = (event: MessageEvent) => {
				if (event.data.type === 'TYPOGRAPHY_UPDATED') {
					console.log('Typography updated successfully:', event.data.success);
					window.removeEventListener('message', handleMessage);
				}
			};

			window.addEventListener('message', handleMessage);

			// Cleanup after timeout
			setTimeout(() => {
				window.removeEventListener('message', handleMessage);
			}, 5000);
		} catch (error) {
			console.error('Error sending typography update message:', error);
		}
	};

	useEffect(() => {
		if (!iframeRef.current || selectedPaletteIndex === null || isThemeSupported === false) return;

		const iframe = iframeRef.current;
		const onLoad = () => {
			iframe.contentWindow?.postMessage(
				{
					type: 'UPDATE_COLOR_PALETTE',
					theme: demo.theme_slug,
					colorPalette: colorPalette[selectedPaletteIndex],
				},
				'*',
			);
		};

		iframe.addEventListener('load', onLoad);
		return () => iframe.removeEventListener('load', onLoad);
	}, [iframeRef, selectedPaletteIndex, demo.theme_slug, colorPalette, isThemeSupported]);

	useEffect(() => {
		if (!iframeRef.current || selectedTypographyIndex === null || isThemeSupported === false)
			return;

		const iframe = iframeRef.current;
		const onLoad = () => {
			iframe.contentWindow?.postMessage(
				{
					type: 'UPDATE_TYPOGRAPHY',
					typography: typography[selectedTypographyIndex],
				},
				'*',
			);
		};

		iframe.addEventListener('load', onLoad);
		return () => iframe.removeEventListener('load', onLoad);
	}, [iframeRef, selectedTypographyIndex, typography, isThemeSupported]);

	return (
		<div className="w-[350px] min-w-[350px] flex flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] ">
			<SidebarHeader
				title={
					demo.title || demo.slug.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
				}
				subtitle="Add your branding: logo, colors & fonts"
			/>
			<div className="flex flex-col gap-6 box-border px-6 pt-6 pb-10 overflow-y-auto tg-scrollbar flex-1">
				<LogoUploader iframeRef={iframeRef} setSiteLogoId={setSiteLogoId} />
				{isThemeSupported && (
					<>
						{Array.isArray(colorPalette?.[0]) && colorPalette[0].length > 0 && (
							<div>
								<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5">
									{__('Color Palette', 'themegrill-demo-importer')}
								</h3>
								<div className="grid grid-cols-2 gap-[14px]">
									{colorPalette.map((colors, index) => (
										<div
											className={`border-2 border-solid  bg-[#FDFDFE] rounded-md p-[6px] cursor-pointer ${index === selectedPaletteIndex ? 'border-[#5182EF]' : 'border-[#EEEFF2] hover:border-[#5182EF]'}`}
											key={index}
											onClick={() => handleColorPalette(index)}
										>
											<div className="border border-solid border-[#EEEFF2] flex rounded-md">
												{colors
													.filter((_, index) => [0, 1, 5, 6, 7].includes(index))
													.map((color, index) => (
														<div
															className={`h-[30px] w-[25px] ${
																index === 0 ? 'rounded-l-md' : index === 4 ? 'rounded-r-md' : ''
															}`}
															style={{ backgroundColor: color }}
															key={color + index}
														/>
													))}
											</div>
										</div>
									))}
								</div>
							</div>
						)}
						<div>
							<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5 leading-normal">
								{__('Typography', 'themegrill-demo-importer')}
							</h3>
							<div className="grid grid-cols-3 gap-[14px]">
								<TooltipProvider>
									{typography.map((t, index) => (
										<Tooltip key={index}>
											<TooltipTrigger asChild>
												<button
													className={`border-2 border-solid bg-[#FDFDFE] px-6 py-[10px] rounded-md cursor-pointer w-[88px] ${index === selectedTypographyIndex ? 'border-[#5182EF]' : 'border-[#EEEFF2] hover:border-[#5182EF]'}`}
													key={index}
													onClick={() => handleTypography(index)}
												>
													<p className="text-[15px] text-[#6B6B6B] font-bold leading-[22px] tracking-[0.15px] m-0 ">
														<span style={{ fontFamily: t[0] }}>A</span>
														<span style={{ fontFamily: t[1] }}>g</span>
													</p>
												</button>
											</TooltipTrigger>
											<TooltipContent side="bottom" sideOffset={-15}>
												{t[0]}/{t[1]}
											</TooltipContent>
										</Tooltip>
									))}
								</TooltipProvider>
							</div>
						</div>
					</>
				)}
				{/* <div>
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
				</div> */}
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
