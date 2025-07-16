import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useDemoContext } from '../../context';
import ImportContent from './ImportContent';
import ImportSidebar from './ImportSidebar';

// type Props = {
// 	demos: SearchResultType[];
// 	initialTheme: string;
// 	data: DataObjectType;
// };

const Import = () => {
	const {
		data,
		theme,
		pagebuilder,
		category,
		plan,
		search,
		searchResults,
		searchTerms,
		setTheme,
		setPagebuilder,
		setCategory,
		setPlan,
		setSearch,
		setSearchResults,
	} = useDemoContext();

	const iframeRef = useRef<HTMLIFrameElement>(null);
	const { slug } = useParams();

	const [siteTitle, setSiteTitle] = useState('');
	const [siteTagline, setSiteTagline] = useState('');
	const [siteLogoId, setSiteLogoId] = useState<number>(0);
	const [collapse, setCollapse] = useState(false);

	const demo = useMemo(() => {
		return searchTerms.filter((demo) => demo.slug === slug)[0];
	}, [slug]);

	const handleClick = (collapse: Boolean) => {
		setCollapse(!collapse);
	};

	const handleSiteTitleChange = (value: string) => {
		try {
			if (!iframeRef?.current?.contentWindow) {
				console.warn('Iframe not available');
				return;
			}

			// Send message to iframe
			iframeRef.current.contentWindow.postMessage(
				{
					type: 'UPDATE_SITE_TITLE',
					siteTitle: value,
				},
				'*',
			);

			// Listen for confirmation
			const handleMessage = (event: MessageEvent) => {
				if (event.data.type === 'SITE_TITLE_UPDATED') {
					console.log('Site title updated successfully:', event.data.success);
					window.removeEventListener('message', handleMessage);
				}
			};

			setSiteTitle(value);
			window.addEventListener('message', handleMessage);

			// Cleanup after timeout
			setTimeout(() => {
				window.removeEventListener('message', handleMessage);
			}, 5000);
		} catch (error) {
			console.error('Error sending site title update message:', error);
		}
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
					className="bg-white rounded-full px-[8px] py-[16px] border border-solid border-[#E1E1E1] cursor-pointer absolute top-[45%] left-[1%]"
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
						className="bg-white rounded-full px-[8px] py-[16px] border border-solid border-[#E1E1E1] cursor-pointer absolute top-[45%] left-[285px]"
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
					<ImportSidebar
						demo={demo}
						iframeRef={iframeRef}
						handleSiteTitleChange={handleSiteTitleChange}
						setSiteTagline={setSiteTagline}
						setSiteLogoId={setSiteLogoId}
					/>
				</>
			)}
			<ImportContent
				demo={demo}
				initialTheme={theme}
				iframeRef={iframeRef}
				siteTitle={siteTitle}
				siteTagline={siteTagline}
				siteLogoId={siteLogoId}
			/>
		</div>
	);
};

export default Import;
