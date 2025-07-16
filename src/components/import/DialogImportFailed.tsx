import { __ } from '@wordpress/i18n';
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { DialogClose, DialogFooter, DialogHeader, DialogTitle } from '../../controls/Dialog';

const DialogImportFailed = ({ handleReimport }: { handleReimport: () => void }) => {
	const navigate = useNavigate();

	return (
		<>
			<DialogHeader className="flex-row border-0 border-b border-solid border-[#f4f4f4] px-[40px] py-[20px] items-center gap-[8px]">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
				>
					<path
						d="M11.999 22.0005C17.5219 22.0005 21.999 17.5233 21.999 12.0005C21.999 6.47764 17.5219 2.00049 11.999 2.00049C6.47618 2.00049 1.99902 6.47764 1.99902 12.0005C1.99902 17.5233 6.47618 22.0005 11.999 22.0005Z"
						stroke="#E74C3C"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
					/>
					<path
						d="M12 8.00049V12.0005"
						stroke="#E74C3C"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
					/>
					<path
						d="M12 15.9995H12.0101"
						stroke="#E74C3C"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
					/>
				</svg>
				<DialogTitle className="my-0 text-[18px] text-[#383838] !mt-0">
					{__('Import Failed', 'themegrill-demo-importer')}
				</DialogTitle>
			</DialogHeader>
			<div className="px-[40px] pt-[20px] pb-[32px] overflow-x-hidden overflow-y-scroll sm:overflow-hidden">
				<p className="text-[15px]/[24px] text-[#383838] ">
					An error occurred while importing the demo content. This may be due to a network issue,
					server timeout, or missing files. Please check your internet connection and try again.
				</p>
				<p className="text-[15px]/[24px] text-[#383838] mb-0">
					If the issue persists, refer to our <a href="">documentation</a> or contact{' '}
					<a href="">support team</a> for further assistance.
				</p>
			</div>
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
					{__('Try Again', 'themegrill-demo-importer')}
				</button>
			</DialogFooter>
		</>
	);
};

export default DialogImportFailed;
