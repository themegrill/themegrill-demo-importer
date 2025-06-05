import { MediaUpload } from '@wordpress/media-utils';
import React from 'react';

const ImportSidebar = () => {
	return (
		<div className="tg-full-overlay-sidebar relative">
			<div>
				<h4 className="text-[20px] m-0 mb-[12px] text-[#383838]">Customize Your Site</h4>
				<p className="text-[#6b6b6b] font-[350] text-[14px] m-0">
					<i>Personalize your site by exploring alternative colors and fonts.</i>
				</p>
			</div>
			<hr className="mt-[24px] border-b-[#EDEDED]" />
			<div className="my-[24px]">
				<h4 className="text-[17px] mb-[16px] text-[#383838]">Choose Builder</h4>
			</div>
			<div className="mb-[24px]">
				<h4 className="text-[17px] m-0 mb-[20px]">Change Logo</h4>
				<MediaUpload
					render={({ open }) => {
						return (
							<button
								type="button"
								className="tg-upload-logo px-[16px] py-[32px] cursor-pointer bg-white rounded mb-[10px] border border-dashed border-[#BABABA]"
								onClick={open}
							>
								<div className="text-center">
									<h4 className="m-0 mb-[8px] text-[14px] text-[#222]">Upload Logo Here</h4>
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

				<p className="text-[#6b6b6b] font-[400] text-[12px] m-0">
					<i>Don’t have a logo yet? No problem! You can upload it later.</i>
				</p>
			</div>
			<div className="mb-[24px]">
				<h4 className="text-[17px] m-0 mb-4">Site Title</h4>
				<input
					type="text"
					className="border border-solid !border-[#E9E9E9] !px-4 !py-[10px] !rounded-[4px] w-full"
					placeholder="Enter Site Title"
				/>
			</div>
			<div className="mb-[24px]">
				<h4 className="text-[17px] m-0 mb-4">Site Tagline</h4>
				<input
					type="text"
					className="border border-solid !border-[#E9E9E9] !px-4 !py-[10px] !rounded-[4px] w-full"
					placeholder="Enter Site Tagline"
				/>
			</div>
		</div>
	);
};

export default ImportSidebar;
