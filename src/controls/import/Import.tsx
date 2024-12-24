import React, { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { SearchResultType } from '../../lib/types';
import ImportContent from './ImportContent';
import ImportSidebar from './ImportSidebar';

type Props = {
	demos: SearchResultType[];
	initialTheme: string;
};

const Import = ({ demos, initialTheme }: Props) => {
	const { slug } = useParams();
	const demo = useMemo(() => {
		return demos.filter((demo) => demo.slug === slug)[0];
	}, [slug]);

	const [collapse, setCollapse] = useState(false);

	const handleClick = (collapse: Boolean) => {
		setCollapse(!collapse);
	};

	useEffect(() => {
		// Add the class when the component mounts
		document.body.classList.add('tg-full-overlay-active');
		document.documentElement.classList.remove('wp-toolbar');

		// Remove the class when the component unmounts
		return () => {
			document.body.classList.remove('tg-full-overlay-active');
		};
	}, []);

	useEffect(() => {
		const handleResize = () => {
			if (window.innerWidth <= 768) {
				setCollapse(true);
			} else {
				setCollapse(false);
			}
		};

		handleResize();

		window.addEventListener('resize', handleResize);

		return () => {
			window.removeEventListener('resize', handleResize);
		};
	}, []);

	return (
		<div className="tg-full-overlay relative">
			{collapse ? (
				<button
					type="button"
					className="bg-white rounded-full px-[8px] py-[16px] border-2 border-solid border-[#F4F4F4] cursor-pointer absolute top-[45%] left-[1%]"
					style={{ zIndex: 100 }}
					onClick={() => handleClick(collapse)}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="12"
						height="12"
						viewBox="0 0 12 12"
						fill="none"
					>
						<path d="M2.5 6L9.5 6" stroke="#383838" strokeLinecap="round" strokeLinejoin="round" />
						<path
							d="M6 2.5L9.5 6L6 9.5"
							stroke="#383838"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</button>
			) : (
				<>
					<button
						type="button"
						className="bg-white rounded-full px-[8px] py-[16px] border-2 border-solid border-[#F4F4F4] cursor-pointer absolute top-[45%] left-[285px]"
						style={{ zIndex: 100 }}
						onClick={() => handleClick(collapse)}
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							width="12"
							height="12"
							viewBox="0 0 12 12"
							fill="none"
						>
							<path d="M9.5 6H2.5" stroke="#383838" strokeLinecap="round" strokeLinejoin="round" />
							<path
								d="M6 9.5L2.5 6L6 2.5"
								stroke="#383838"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</button>
					<ImportSidebar />
				</>
			)}
			<ImportContent demo={demo} initialTheme={initialTheme} />
		</div>
	);
};

export default Import;
