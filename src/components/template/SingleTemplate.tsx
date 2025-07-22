import React, { useState } from 'react';
import { PageWithSelection, SearchResultType } from '../../lib/types';

declare const require: any;

type Props = {
	page: PageWithSelection;
	setAllPages: React.Dispatch<React.SetStateAction<PageWithSelection[]>>;
	demo: SearchResultType;
};

const SingleTemplate = ({ page, setAllPages, demo }: Props) => {
	const [selected, setSelected] = useState(false);
	const handleSelected = (selected: Boolean, id: number) => {
		setSelected(!selected);
		setAllPages((prevPages) =>
			prevPages.map((page) => (page.ID === id ? { ...page, isSelected: !page.isSelected } : page)),
		);
	};

	return (
		<button
			className={`p-[8px] pb-[10px] bg-white border border-solid rounded-[2px] cursor-pointer ${selected ? 'border-[#2563EB]' : 'border-[#F4F4F4]'}`}
			type="button"
			onClick={() => handleSelected(selected, page.ID)}
		>
			<div className="w-[100px] h-[120px] sm:w-[177px] sm:h-[180px] mb-[10px]">
				{page.screenshot ? (
					<img
						src={page.screenshot}
						alt={page.post_title}
						className="w-full h-full border border-solid border-[#F4F4F4] rounded-[2px]"
					/>
				) : demo.theme === 'colormag' || demo.theme === 'colornews' ? (
					<img
						src={require(`../../assets/images/colormag-skeleton.jpg`)}
						alt={page.post_title}
						className="w-full h-full border border-solid border-[#F4F4F4] rounded-[2px]"
					/>
				) : (
					<img
						src={require(`../../assets/images/zakra-skeleton.jpg`)}
						alt={page.post_title}
						className="w-full h-full border border-solid border-[#F4F4F4] rounded-[2px]"
					/>
				)}
			</div>

			<div className="flex justify-between items-center">
				<h4 className="m-0 text-[#383838]">{page.post_title}</h4>
				{selected && (
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="17"
						height="16"
						viewBox="0 0 17 16"
						fill="none"
					>
						<circle cx="8" cy="8" r="7" fill="#2563EB" />
						<path
							d="M11.5 5L6 10.1733L4 8.17333"
							stroke="white"
							strokeWidth="2"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				)}
			</div>
		</button>
	);
};

export default SingleTemplate;
