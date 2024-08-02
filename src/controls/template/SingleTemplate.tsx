import React, { useState } from 'react';

const SingleTemplate = () => {
	const [selected, setSelected] = useState(false);
	const handleSelected = (selected: Boolean) => {
		setSelected(!selected);
	};
	return (
		<button
			className={`p-[8px] pb-[10px] bg-white border border-solid rounded-[2px] cursor-pointer ${selected ? 'border-[#2563EB]' : 'border-[#F4F4F4]'}`}
			type="button"
			onClick={() => handleSelected(selected)}
		>
			<div className="w-[100px] h-[120px] sm:w-[160px] sm:h-[180px] mb-[8px]">
				<img
					src="https://img.freepik.com/free-photo/white-smooth-textured-paper-background_53876-128527.jpg?t=st=1721888024~exp=1721891624~hmac=78db48fa770b4824071f4b0df8aba5309d30ceb8e9c244fbdb7fa4652590b79c&w=996"
					alt="Optigo"
					className="w-full h-full border border-solid border-[#F4F4F4] rounded-[2px]"
				/>
			</div>

			<div className="flex justify-between items-center">
				<h4 className="m-0 text-[#383838]">Home</h4>
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
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
						/>
					</svg>
				)}
			</div>
		</button>
	);
};

export default SingleTemplate;
