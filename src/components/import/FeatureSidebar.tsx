import { __ } from '@wordpress/i18n';
import { ArrowLeft } from 'lucide-react';
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '../../controls/Button';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../controls/Tooltip';
import { pluginsDetails } from '../../lib/plugins';
import { Demo, PluginItem } from '../../lib/types';

declare const require: any;

type Props = {
	demo: Demo;
	plugins: PluginItem[];
	setPlugins: React.Dispatch<React.SetStateAction<PluginItem[]>>;
	onOpen: () => void;
	setShowFeatureLayout: React.Dispatch<React.SetStateAction<boolean>>;
};

const FeatureSidebar = ({ demo, plugins, setPlugins, onOpen, setShowFeatureLayout }: Props) => {
	const navigate = useNavigate();

	const selectPlugins = (index: number) => {
		setPlugins((prevItems: PluginItem[]) =>
			prevItems.map((item, itemIndex) =>
				itemIndex === index ? { ...item, isSelected: !item.isSelected } : item,
			),
		);
	};

	return (
		<div className="w-[350px] min-w-[350px] flex flex-col bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] ">
			<div className="px-6 pt-6">
				<div className="pb-6 border-0 border-b border-solid border-[#E3E3E3]">
					<div className="flex justify-between items-center">
						<h3 className="text-[20px] leading-7 m-0">
							{__('Features', 'themegrill-demo-importer')}
						</h3>
						<Tooltip>
							<TooltipTrigger asChild>
								<ArrowLeft
									size={24}
									color="#909090"
									strokeWidth={2}
									onClick={() => setShowFeatureLayout(false)}
									className="cursor-pointer"
								/>
							</TooltipTrigger>
							<TooltipContent side="bottom">
								{__('Go Back', 'themegrill-demo-importer')}
							</TooltipContent>
						</Tooltip>
					</div>
					<p className="text-[14px] text-[#6B6B6B] leading-[21px] m-0 mt-[6px] ">
						{__('Select features that you need for your site', 'themegrill-demo-importer')}
					</p>
				</div>
			</div>
			<div className="px-6 pt-6 pb-10 border-0 border-r border-solid border-[#E9E9E9] flex flex-col gap-6 bg-[#FAFBFC] box-border overflow-y-auto flex-1">
				{plugins.map((item, index) => (
					<div
						className={`bg-[#fff] border-2 border-solid rounded-md p-4 ${item.isSelected ? (item.isMandatory ? 'border-[#5182EF]/65 cursor-not-allowed ' : 'border-[#5182EF] cursor-pointer ') : 'border-[#EDEDED] cursor-pointer '}`}
						key={index}
						onClick={item.isMandatory ? undefined : () => selectPlugins(index)}
					>
						<div className="flex items-center justify-between">
							<p
								className={`m-0 text-[16px] text-[#1F1F1F] leading-6 font-semibold ${item.isMandatory ? 'opacity-65' : 'opacity-100'}`}
							>
								{item.name}
							</p>
							{item.isSelected ? (
								<svg
									xmlns="http://www.w3.org/2000/svg"
									width="20"
									height="20"
									viewBox="0 0 20 20"
									fill="none"
								>
									<circle
										cx="9.77734"
										cy="10"
										r="8.75"
										fill="#5182EF"
										fillOpacity={item.isMandatory ? 0.65 : 1}
									/>
									<path
										d="M14.156 6.25L7.28101 12.7167L4.78101 10.2167"
										stroke="white"
										strokeWidth="2.5"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
							) : (
								<svg
									xmlns="http://www.w3.org/2000/svg"
									width="20"
									height="20"
									viewBox="0 0 20 20"
									fill="none"
								>
									<circle cx="9.77734" cy="10" r="8.75" fill="#D3D3D3" />
									<path
										d="M14.156 6.25L7.28101 12.7167L4.78101 10.2167"
										stroke="white"
										strokeWidth="2.5"
										strokeLinecap="round"
										strokeLinejoin="round"
									/>
								</svg>
							)}
						</div>
						{pluginsDetails[item.plugin] && (
							<p
								className={`m-0 mt-2 text-[13px] leading-[23px] text-[#545454] ${item.isMandatory ? 'opacity-65' : 'opacity-100'}`}
							>
								{pluginsDetails[item.plugin]}
							</p>
						)}
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
					onClick={() => navigate(-1)}
				>
					{__('Cancel', 'themegrill-demo-importer')}
				</Button>
			</div>
		</div>
	);
};

export default FeatureSidebar;
