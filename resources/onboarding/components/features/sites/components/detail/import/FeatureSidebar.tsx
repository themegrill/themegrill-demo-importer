import { __ } from '@wordpress/i18n';
import { Check } from 'lucide-react';
import React from 'react';
import { Demo, PluginItem } from '../../../../../../lib/types';
import { Button } from '../../../../../ui/Button';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../../../../ui/Tooltip';
import SidebarHeader from './SidebarHeader';

declare const require: any;

type Props = {
	demo: Demo;
	plugins: PluginItem[];
	setPlugins: React.Dispatch<React.SetStateAction<PluginItem[]>>;
	onOpen: () => void;
	setShowFeatureLayout: React.Dispatch<React.SetStateAction<boolean>>;
};

const FeatureSidebar = ({ demo, plugins, setPlugins, onOpen, setShowFeatureLayout }: Props) => {
	const selectPlugins = (index: number) => {
		setPlugins((prevItems: PluginItem[]) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, isSelected: !item.isSelected } : item,
			),
		);
	};

	return (
		<div className="w-[350px] min-w-[350px] flex flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] ">
			<SidebarHeader title="Features" subtitle="Select features that you need for your site " />
			<div className="px-6 pt-6 pb-10 border-0 border-r border-solid border-[#E9E9E9] flex flex-col gap-6 bg-[#FAFBFC] box-border overflow-y-auto flex-1 tg-scrollbar">
				{plugins.map((item, index) => (
					<div
						className={`bg-[#fff] border-2 border-solid rounded-md p-4 ${item.isSelected ? (item.isMandatory ? 'border-[#5182EF]/65 cursor-not-allowed' : 'border-[#5182EF] cursor-pointer ') : 'border-[#EDEDED] hover:border-[#5182EF] cursor-pointer'}`}
						key={index}
						{...(!item.isMandatory && { onClick: () => selectPlugins(index) })}
					>
						<div className="flex items-center justify-between">
							<p
								className={`m-0 text-[16px] text-[#1F1F1F] leading-6 font-semibold ${item.isMandatory ? 'opacity-65' : 'opacity-100'}`}
							>
								{item.name}
							</p>
							<div
								className={`relative border-2 border-solid rounded-full w-[14px] h-[14px] text-white ${item.isSelected ? (item.isMandatory ? 'border-[#D3D3D3] bg-[#D3D3D3]' : 'border-[#5182EF] bg-[#5182EF] ') : 'border-[#8c8f94]'}`}
							>
								{item.isMandatory ? (
									<Tooltip>
										<TooltipTrigger asChild>
											<Check className="absolute" size={14} strokeWidth={4} />
										</TooltipTrigger>
										<TooltipContent side="bottom" sideOffset={-4}>
											{__(
												'This plugin is required for the template to function properly and cannot be unchecked.',
												'themegrill-demo-importer',
											)}
										</TooltipContent>
									</Tooltip>
								) : (
									<Check className="absolute" size={14} strokeWidth={4} />
								)}
							</div>
						</div>
						<p
							className={`m-0 mt-2 text-[13px] leading-[23px] text-[#545454] ${item.isMandatory ? 'opacity-65' : 'opacity-100'}`}
						>
							{item.description}
						</p>
					</div>
				))}
			</div>
			<div className="border-0 border-t border-r border-solid border-[#E9E9E9] bg-white p-[24px] pb-[12px]">
				<Button
					className="px-5 py-[15px] h-[51px] text-[15px] leading-[21px] text-[#FAFBFF] font-semibold rounded-md bg-[#2563EB] border-none w-full hover:bg-[#2563EB] cursor-pointer"
					onClick={onOpen}
				>
					{__('Start Import', 'themegrill-demo-importer')}
				</Button>
				<Button
					className="mt-4 cursor-pointer text-[14px] text-[#6B6B6B] leading-[19px] border-0 bg-transparent font-normal w-full p-0 h-5 hover:bg-transparent hover:border-0"
					onClick={() => setShowFeatureLayout(false)}
				>
					{__('Cancel', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	);
};

export default FeatureSidebar;
