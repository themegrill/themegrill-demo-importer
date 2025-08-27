import React, { useState } from 'react';
import { Demo, PageWithSelection } from '../../../../../../lib/types';

declare const require: any;

type Props = {
	page: PageWithSelection;
	setAllPages: React.Dispatch<React.SetStateAction<PageWithSelection[]>>;
	demo: Demo;
};
const Page = ({ page, setAllPages, demo }: Props) => {
	const [selected, setSelected] = useState(page.isSelected);
	const handleSelected = (selected: Boolean, id: number) => {
		setSelected(!selected);
		setAllPages((prevPages) =>
			prevPages.map((page) => (page.id === id ? { ...page, isSelected: !page.isSelected } : page)),
		);
	};

	return (
		<div
			className={`border-2 rounded-md border-solid cursor-pointer flex-shrink-0 self-start ${selected ? 'border-[#5182EF]' : 'border-[#EDEDED]'}`}
			style={{
				boxShadow: selected ? '0 4.089px 24.531px 0 rgba(0, 0, 0, 0.10)' : 'none',
			}}
			onClick={() => handleSelected(selected, page.id)}
		>
			<div style={{ aspectRatio: '.84 / 1' }}>
				{page.screenshot ? (
					<img src={page.screenshot} alt={page.title} className="w-full h-full rounded-t-md" />
				) : (
					<img
						src={require(`../../../../../../assets/images/demo-skeleton.jpg`)}
						alt={page.title}
						className="w-full h-full rounded-t-md"
					/>
				)}
			</div>

			<div className="bg-white px-4 py-4 border-0 border-t border-solid border-[#EDEDED] rounded-b-md flex justify-between items-center">
				<h4 className="m-0 text-[#383838] text-[16px]">{page.title}</h4>
				{selected ? (
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="20"
						height="21"
						viewBox="0 0 20 21"
						fill="none"
						className="block shrink-0"
					>
						<circle cx="9.78076" cy="10.5" r="8.75" fill="#5182EF" />
						<path
							d="M14.156 6.75L7.28101 13.2167L4.78101 10.7167"
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
						height="21"
						viewBox="0 0 20 21"
						fill="none"
					>
						<circle cx="9.78076" cy="10.5" r="8.75" fill="#D3D3D3" />
						<path
							d="M14.156 6.75L7.28101 13.2167L4.78101 10.7167"
							stroke="white"
							strokeWidth="2.5"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				)}
			</div>
		</div>
	);
};

export default Page;
