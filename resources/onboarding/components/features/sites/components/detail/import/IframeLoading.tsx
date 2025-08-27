import React from 'react';

const IframeLoading = () => {
	return (
		<>
			<div className="flex items-center justify-between gap-2">
				<div className="w-[60px] h-[60px] rounded-full bg-[#E6E6E6] animate-pulse"></div>
				<div className="flex gap-8 justify-between menu-wrapper">
					<div className="w-[98px] h-[26px] bg-[#E6E6E6] rounded-sm animate-pulse"></div>
					<div className="w-[98px] h-[26px] bg-[#E6E6E6] rounded-sm animate-pulse"></div>
					<div className="w-[98px] h-[26px] bg-[#E6E6E6] rounded-sm animate-pulse"></div>
					<div className="w-[98px] h-[26px] bg-[#E6E6E6] rounded-sm animate-pulse"></div>
					<div className="w-[98px] h-[26px] bg-[#E6E6E6] rounded-sm animate-pulse"></div>
				</div>
				<div className="w-[15%] min-w-[120px] h-[55px] bg-[#E6E6E6] rounded-sm animate-pulse"></div>
			</div>

			<div className="flex justify-between items-center mt-[50px] mb-[50px] gap-4">
				<div className="flex-1">
					<div className="flex flex-col gap-9 mb-[8%]">
						<div className="w-[80%] h-[48px] bg-[#E6E6E6] animate-pulse"></div>
						<div className="w-[80%] h-[18px] bg-[#E6E6E6] animate-pulse"></div>
						<div className="w-[80%] h-[18px] bg-[#E6E6E6] animate-pulse"></div>
						<div className="w-[60%] h-[18px] bg-[#E6E6E6] animate-pulse"></div>
					</div>
					<div className="w-[26%] min-w-[120px] h-[55px] bg-[#E6E6E6] rounded-[10px] animate-pulse"></div>
				</div>

				<div className="w-[40%] min-w-[250px] h-[450px] bg-[#E6E6E6] animate-pulse"></div>
			</div>

			<div className="flex justify-between gap-9">
				<div className="w-full h-[350px] bg-[#E6E6E6] animate-pulse"></div>

				<div className="flex flex-col gap-9 w-full h-[350px] ">
					<div className="flex-1 w-full bg-[#E6E6E6] animate-pulse"></div>
					<div className="flex-1 w-full bg-[#E6E6E6] animate-pulse"></div>
				</div>

				<div className="w-full h-[350px] bg-[#E6E6E6] animate-pulse"></div>
			</div>
		</>
	);
};

export default IframeLoading;
