import { MediaUpload } from '@wordpress/media-utils';
import React from 'react';

const ImportSidebar = () => {
	const colors = ['#064E41', '#AADBD2', '#524F51', '#8F8F8F', '#D3D3D3', '#FFF'];
	return (
		<div className="tg-full-overlay-sidebar relative">
			<div>
				<h4 className="text-[22px] m-0 mb-[16px]">Style Starter</h4>
				<p className="text-[#6b6b6b] font-[350] text-[14px] m-0">
					<i>
						Pave the customization process by exploring alternative colors and fonts that you want
						to implement on your site.
					</i>
				</p>
			</div>
			<hr className="mt-[16px] mb-[24px] border-b-[#F4F4F4]" />
			<div className="mb-[24px]">
				<h4 className="text-[18px] m-0 mb-[20px]">Change Logo</h4>
				<MediaUpload
					render={({ open }) => {
						return (
							<button
								type="button"
								className="tg-upload-logo px-[20px] py-[32px] cursor-pointer bg-white rounded mb-[8px] border border-dashed border-[#BABABA]"
								onClick={open}
							>
								<div className="text-center">
									<h4 className="m-0 mb-[8px] text-[#222]">Upload Logo Here</h4>
									<p className="m-0 text-[12px] text-[#6B6B6B]">
										Suggested Dimension: 190x60 pixels
									</p>
								</div>
							</button>
						);
					}}
					onSelect={(v) => {
						console.log(v);
					}}
				/>

				<p className="text-[#6b6b6b] font-[350] text-[14px] m-0">
					<i>Don’t have a logo yet? No problem! You can upload it later.</i>
				</p>
			</div>
			<div className="mb-[24px]">
				<h4 className="text-[18px] m-0">Color Palette</h4>
				<div className="my-[20px]">
					<p className="text-[#6B6B6B] uppercase text-[12px]">Default Color</p>

					<div className="border border-solid border-[#2563EB] p-2 rounded flex justify-between">
						{colors.map((color, index) => (
							<div
								className="border border-solid border-[#f4f4f4] rounded-full h-[28px] w-[28px]"
								style={{ backgroundColor: color }}
								key={index}
							></div>
						))}
					</div>
				</div>

				<button
					className="border border-solid border-[#F4F4F4] bg-white p-2 rounded w-full m-0 mt-[8px] flex justify-between cursor-pointer"
					type="button"
				>
					{colors.map((color, index) => (
						<div
							className="border border-solid border-[#f4f4f4] rounded-full h-[28px] w-[28px]"
							style={{ backgroundColor: color }}
							key={index}
						></div>
					))}
				</button>
				<button
					className="border border-solid border-[#F4F4F4] bg-white p-2 rounded w-full m-0 mt-[8px] flex justify-between cursor-pointer"
					type="button"
				>
					{colors.map((color, index) => (
						<div
							className="border border-solid border-[#f4f4f4] rounded-full h-[28px] w-[28px]"
							style={{ backgroundColor: color }}
							key={index}
						></div>
					))}
				</button>
			</div>
			<div className="mb-[24px">
				<h4 className="text-[18px] m-0">Fonts</h4>
				<div className="my-[20px]">
					<p className="text-[#6B6B6B] uppercase text-[12px]">Default Font</p>

					<div className="border border-solid border-[#2563EB] p-2 rounded flex gap-4 mt-[8px]">
						<p className="text-[12px] text-[#222] m-0">Aa</p>
						<p className="text-[12px] text-[#6B6B6B] m-0">DM Sans</p>
					</div>
				</div>
				<button className="border border-solid border-[#F4F4F4] p-2 rounded flex gap-4 mt-[8px] w-full bg-white cursor-pointer">
					<p className="text-[12px] text-[#222] m-0">Aa</p>
					<p className="text-[12px] text-[#6B6B6B] m-0">DM Sans</p>
				</button>
				<button className="border border-solid border-[#F4F4F4] p-2 rounded flex gap-4 mt-[8px] w-full bg-white cursor-pointer">
					<p className="text-[12px] text-[#222] m-0">Aa</p>
					<p className="text-[12px] text-[#6B6B6B] m-0">DM Sans</p>
				</button>
			</div>
		</div>
	);
};

export default ImportSidebar;
