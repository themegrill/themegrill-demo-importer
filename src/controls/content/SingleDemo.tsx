import React from 'react';
import { Link } from 'react-router-dom';
import elementor from '../../assets/images/elementor.png';

type DemoProps = {
	isPro?: Boolean;
	isPremium?: Boolean;
	isNew?: Boolean;
};

const SingleDemo = (props: DemoProps) => {
	const { isPro, isPremium, isNew } = props;
	return (
		<Link
			to="/import-detail"
			className="text-[#383838] no-underline hover:text-[#383838] tg-demo flex flex-col gap-0"
		>
			<div className="shadow flex flex-col  ">
				<div className="relative">
					<img
						src="https://d1sb0nhp4t2db4.cloudfront.net/resources/zakra/zakra-optigo/screenshot.jpg"
						alt=""
						className="w-full h-full"
					/>
					{isPro && (
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
					{isPremium && (
						<div className="tg-demo-pro text-white">
							<p className="m-0 font-semibold">Premium</p>
						</div>
					)}
					<div className="absolute bottom-[16px] left-[16px] p-[4px] bg-white rounded tg-pagebuilder hidden cursor-auto">
						<img src={elementor} alt="" className="block" />
						<span className="text-[#383838] ml-[8px] font-[600] hidden">Elementor</span>
					</div>
				</div>

				<div className="bg-white px-[16px] py-[15px] border-[#f4f4f4]">
					<h4 className="m-0 ">
						Optigo
						{isNew && (
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
