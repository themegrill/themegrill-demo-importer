import { useEffect } from 'react';
import IframeLoading from './IframeLoading';

const ImportSkeleton = () => {
	useEffect(() => {
		// Add the class when the component mounts
		document.body.classList.add('tg-full-overlay-active');
		document.documentElement.classList.remove('wp-toolbar');
	}, []);
	return (
		<div className="flex h-screen content-container">
			<div className="w-[350px] h-screen flex flex-col">
				<div className="p-6  border-0 border-r border-solid border-[#E9E9E9] flex flex-col gap-6 bg-[#FAFBFC] box-border flex-1">
					<div className="pb-6 border-0 border-b border-solid border-[#E3E3E3]">
						<div className="flex justify-between items-center">
							<div className="w-[123px] h-6 bg-[#E7E8E9] rounded-sm animate-pulse"></div>
							<div className="w-6 h-6 bg-[#E7E8E9] rounded animate-pulse"></div>
						</div>
						<div className="w-[212px] h-[12px] bg-[#E7E8E9] rounded-sm animate-pulse mt-2"></div>
					</div>
					<div>
						<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mb-5 "></div>
						<div className="w-[302px] h-[48px] bg-[#E7E8E9] rounded-md animate-pulse "></div>
					</div>
					<div>
						<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mb-5"></div>
						<div className="grid grid-cols-2 gap-[14px] ">
							{Array.from({ length: 4 }).map((_, index) => (
								<div
									className="h-[46px] w-[144px] bg-[#E7E8E9] rounded-md animate-pulse "
									key={index}
								></div>
							))}
						</div>
					</div>
					<div>
						<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mb-5"></div>
						<div className="grid grid-cols-3 gap-[14px] ">
							{Array.from({ length: 6 }).map((_, index) => (
								<div
									className="h-[46px] w-[92px] bg-[#E7E8E9] rounded-md animate-pulse "
									key={index}
								></div>
							))}
						</div>
						{/* <div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mt-5 mb-4"></div>
						<div className="w-[302px] h-[48px] bg-[#E7E8E9] rounded-md animate-pulse"></div> */}
					</div>
				</div>
				<div className="border-0 border-t border-r border-solid border-[#E9E9E9] bg-white p-[24px] pb-[12px]">
					<div className="w-[302px] h-[51px] bg-[#E7E8E9] rounded-md animate-pulse mb-4"></div>
					<div className="w-[74px] h-[24px] bg-[#E7E8E9] rounded-sm animate-pulse ml-auto mr-auto"></div>
				</div>
			</div>
			<div className="flex-1 p-[60px] pb-0 bg-white iframe-wrapper">
				<IframeLoading />
			</div>
		</div>
	);
};

export default ImportSkeleton;
