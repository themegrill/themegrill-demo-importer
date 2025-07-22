import { __ } from '@wordpress/i18n';
import React from 'react';
import { DialogClose, DialogFooter, DialogHeader, DialogTitle } from '../../controls/Dialog';

const DialogCleanup = ({
	isCleanInstallCompleted,
	handleReimport,
}: {
	isCleanInstallCompleted: boolean;
	handleReimport: () => void;
}) => {
	return (
		<>
			<DialogHeader className="flex-row border-0 border-b border-solid border-[#f4f4f4] px-[40px] py-[20px] items-center gap-[8px]">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="19"
					height="22"
					viewBox="0 0 19 22"
					fill="none"
				>
					<path
						fillRule="evenodd"
						clipRule="evenodd"
						d="M11.1296 -0.00878906L12.1951 3.74286L16.0721 4.93372L12.3115 5.99027L11.1296 9.86727L10.073 6.12458L6.18709 4.93372L9.94769 3.86821L11.1296 -0.00878906ZM3.76956 14.4695L4.5754 17.3348L7.53911 18.2301L4.67389 19.0449L3.77851 22.0086L2.96371 19.1434L0 18.2391L2.86522 17.4332L3.76956 14.4695ZM17.1376 13.5742L17.5405 14.9978L19 15.4455L17.5942 15.8395L17.1466 17.3079L16.7436 15.8932L15.2842 15.4455L16.6899 15.0426L17.1376 13.5742Z"
						fill="#3858E9"
					/>
				</svg>
				<DialogTitle className="my-0 text-[18px] text-[#383838] !mt-0">
					{__('Clean Install', 'themegrill-demo-importer')}
				</DialogTitle>
			</DialogHeader>
			<div className="px-[40px] pt-[53px] pb-[60px] overflow-x-hidden overflow-y-scroll sm:overflow-hidden text-center">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="85"
					height="86"
					viewBox="0 0 85 86"
					fill="none"
				>
					<path
						d="M8.00051 30.999L56.9998 30.499V-0.000976562H7.99976L8.00051 30.999Z"
						fill="#DEE7FC"
					/>
					<path
						d="M58.1434 37.9031C58.3698 37.2853 58.7779 36.7504 59.314 36.369C59.8502 35.9876 60.4893 35.7775 61.1472 35.7663H81.6814C82.0363 35.7531 82.3897 35.8178 82.7167 35.9561C83.0438 36.0943 83.3366 36.3026 83.5743 36.5663C83.8121 36.83 83.9891 37.1427 84.0929 37.4822C84.1966 37.8218 84.2246 38.18 84.1748 38.5316L78.87 83.0399C78.7574 83.7976 78.379 84.4905 77.8023 84.9948C77.2257 85.4991 76.4885 85.7817 75.7225 85.7923H10.5672C10.2136 85.8058 9.8613 85.7416 9.53516 85.6042C9.20902 85.4669 8.91695 85.2597 8.6795 84.9973C8.44205 84.735 8.265 84.4237 8.16081 84.0855C8.05661 83.7473 8.02781 83.3904 8.07643 83.0399L12.5244 45.6448C12.637 44.8871 13.0155 44.1942 13.5921 43.6899C14.1687 43.1856 14.9059 42.9029 15.6719 42.8924H51.1277C52.6641 42.8662 54.1565 42.3756 55.4088 41.485C56.661 40.5945 57.6144 39.3458 58.1434 37.9031Z"
						fill="#5182EF"
					/>
					<path
						d="M9.24878 79.2424H77.331L81.2095 33.4336H13.1299L9.24878 79.2424Z"
						fill="#E9EFFD"
					/>
					<path
						d="M9.24878 76.9924H77.331L80.1373 31.1836H13.1299L9.24878 76.9924Z"
						fill="#9BB7F6"
					/>
					<path
						d="M9.24878 79.2418H77.331L79.6704 31.9785H11.5908L9.24878 79.2418Z"
						fill="#E9EFFD"
					/>
					<path
						d="M9.24878 76.9918H77.331L78.257 29.7285H11.5908L9.24878 76.9918Z"
						fill="#9BB7F6"
					/>
					<path
						d="M9.24878 79.2431H77.331L77.7363 30.6973H9.65664L9.24878 79.2431Z"
						fill="#E9EFFD"
					/>
					<path
						d="M9.24905 83.7428H77.3312L75.9332 36.1152H8.29224L9.24905 83.7428Z"
						fill="#E0E0E0"
					/>
					<path
						d="M9.37476 1.2207V76.7763L71.9998 75.998L70.9998 13.998L58.1711 2.00774L9.37476 1.2207Z"
						fill="#E9EFFD"
					/>
					<path
						d="M5.41127 71.7927H76.9998L74.9998 23.999H1.99976L5.41127 71.7927Z"
						fill="#DEE7FC"
					/>
					<path
						d="M6.99976 72.999L74.6052 71.5278L71.2302 25.999H3.99976L6.99976 72.999Z"
						fill="#E9EFFD"
					/>
					<path
						d="M43.3428 40.794C43.3467 40.4964 43.4103 40.2025 43.53 39.93C43.6497 39.6575 43.823 39.4119 44.0396 39.2077C44.2562 39.0036 44.5117 38.8451 44.7908 38.7417C45.0699 38.6383 45.367 38.5921 45.6643 38.6059H66.1985C66.9935 38.6364 67.7559 38.9301 68.366 39.4408C68.976 39.9515 69.3992 40.6503 69.5692 41.4276L77.6828 82.9834C77.9855 84.5404 76.9697 85.8051 75.4126 85.8051H10.2573C8.69765 85.8051 7.17907 84.5404 6.88407 82.9834L0.19154 48.7255C-0.11115 47.1684 0.904657 45.9038 2.46172 45.9038H37.9201C40.9983 45.9038 43.2377 43.785 43.3428 40.794Z"
						fill="#5182EF"
					/>
					<path
						opacity="0.2"
						d="M43.3428 40.794C43.3467 40.4964 43.4103 40.2025 43.53 39.93C43.6497 39.6575 43.823 39.4119 44.0396 39.2077C44.2562 39.0036 44.5117 38.8451 44.7908 38.7417C45.0699 38.6383 45.367 38.5921 45.6643 38.6059H66.1985C66.9935 38.6364 67.7559 38.9301 68.366 39.4408C68.976 39.9515 69.3992 40.6503 69.5692 41.4276L77.6828 82.9834C77.9855 84.5404 76.9697 85.805 75.4126 85.805H10.2573C8.69765 85.805 7.17907 84.5404 6.88407 82.9834L0.19154 48.7255C-0.11115 47.1684 0.904657 45.9038 2.46172 45.9038H37.9201C40.9983 45.9038 43.2377 43.785 43.3428 40.794Z"
						fill="#5182EF"
					/>
					<path
						opacity="0.72"
						d="M15.7498 11.124H39.3748"
						stroke="#CCDAFA"
						strokeWidth="1.5"
						strokeLinecap="round"
					/>
					<path
						opacity="0.84"
						d="M12.3748 35.249H35.9998"
						stroke="#CCDAFA"
						strokeWidth="1.5"
						strokeLinecap="round"
					/>
					<path
						opacity="0.72"
						d="M16.1248 17.499H35.2498"
						stroke="#CCDAFA"
						strokeWidth="1.3496"
						strokeLinecap="round"
					/>
				</svg>
				<p className="text-[14px]/[21px] text-[#6B6B6B] mb-0 mt-[32px]">
					{isCleanInstallCompleted ? (
						<>
							{__(
								'Cleaned previously imported posts, pages, menus, media attachments, etc.',
								'themegrill-demo-importer',
							)}
						</>
					) : (
						<>
							{__(
								'Cleaning previously imported posts, pages, menus, media attachments, etc.',
								'themegrill-demo-importer',
							)}
						</>
					)}
				</p>
			</div>
			{isCleanInstallCompleted && (
				<DialogFooter className="border-0 border-t border-solid border-[#f4f4f4] p-[16px] sm:py-[16px] sm:px-[40px] flex items-center justify-between flex-row sm:justify-between">
					<DialogClose asChild>
						<button
							type="button"
							className="cursor-pointer px-0 bg-transparent text-[#7A7A7A] border-0 text-[16px]"
						>
							{__('Cancel', 'themegrill-demo-importer')}
						</button>
					</DialogClose>
					<button
						type="button"
						className="cursor-pointer bg-[#2563EB] text-white border-0 rounded px-[24px] py-[10px] text-[16px] disabled:opacity-50 disabled:cursor-not-allowed"
						onClick={handleReimport}
					>
						{__('Continue Import', 'themegrill-demo-importer')}
					</button>
				</DialogFooter>
			)}
		</>
	);
};

export default DialogCleanup;
