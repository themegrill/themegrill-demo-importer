import React from 'react';
import { Link } from 'react-router-dom';
import { SearchResultType } from '../../lib/types';

declare const require: any;

type DemoProps = {
	demo: SearchResultType;
};

const SingleDemo = ({ demo }: DemoProps) => {
	const checkImageExists = (key: string): string => {
		try {
			return require(`../../assets/images/${key}.jpg`);
		} catch {
			return '';
		}
	};

	return (
		<Link
			to={`/import-detail/${demo.slug}`}
			className="text-[#383838] no-underline hover:text-[#383838] tg-demo flex flex-col gap-0"
		>
			<div className="shadow flex flex-col">
				<div className="relative">
					<img src={demo.image} alt="" className="w-full h-full" />
					{demo.pro && (
						<div className="tg-demo-pro">
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="16"
								height="16"
								viewBox="0 0 16 16"
								fill="none"
							>
								<path d="M8.00073 1.3327L12.0007 11.9994H4.00073L8.00073 1.3327Z" fill="#EFEFEF" />
								<path
									fillRule="evenodd"
									clipRule="evenodd"
									d="M1.33398 3.99936L2.28637 11.9994H13.7149L14.6673 3.99936L8.00065 9.49936L1.33398 3.99936ZM13.7149 12.7613H2.28635V14.666H13.7149V12.7613Z"
									fill="white"
								/>
							</svg>
						</div>
					)}
					{demo.premium && (
						<div className="tg-demo-pro text-white">
							<p className="m-0 font-semibold">Premium</p>
						</div>
					)}
					<div className="absolute bottom-[16px] left-[16px] flex gap-[10px]">
						{Object.entries(demo?.pagebuilders || {}).map(
							([key, value]) =>
								checkImageExists(key) !== '' && (
									<div
										className="p-[4px] bg-white rounded tg-pagebuilder hidden cursor-auto"
										key={key}
									>
										<img src={require(`../../assets/images/${key}.jpg`)} alt="" className="block" />

										<span className="text-[#383838] font-[600] hidden text-[13px]">{value}</span>
									</div>
								),
						)}
					</div>
				</div>

				<div className="bg-white px-[16px] py-[15px] border-[#f4f4f4]">
					<h4 className="m-0 text-sm">
						{demo.name}
						{demo.new && (
							<span className="bg-[#27AE60] px-[4px] py-[1px] text-[10px] text-white rounded-[4px] ml-[8px]">
								New
							</span>
						)}
					</h4>
				</div>
			</div>
		</Link>
	);
};

export default SingleDemo;
