import apiFetch from '@wordpress/api-fetch';
import Lottie from 'lottie-react';
import React, { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import spinner from '../../assets/animation/spinner.json';
import { Demo } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';
import ImportContent from './ImportContent';
import ImportSidebar from './ImportSidebar';

declare const require: any;

const Import = () => {
	const { localizedData, setLocalizedData } = useLocalizedData();

	const iframeRef = useRef<HTMLIFrameElement>(null);
	const { slug } = useParams();
	const [demo, setDemo] = useState({} as Demo);
	const [loading, setLoading] = useState(true);
	const [siteTitle, setSiteTitle] = useState('');
	const [siteTagline, setSiteTagline] = useState('');
	const [siteLogoId, setSiteLogoId] = useState<number>(0);
	const [collapse, setCollapse] = useState(false);
	const [device, setDevice] = useState('desktop');
	const [error, setError] = useState<string | null>(null);
	const [empty, setEmpty] = useState<boolean>(false);

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
					// console.log('Site title updated successfully:', event.data.success);
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

		// Handle resize logic
		const handleResize = () => {
			if (window.innerWidth <= 768) {
				setCollapse(true);
			} else {
				setCollapse(false);
			}
		};

		// Set initial resize state
		handleResize();
		window.addEventListener('resize', handleResize);

		const fetchSiteData = async () => {
			try {
				const response = await apiFetch<{ success: boolean; message?: string; data?: Demo }>({
					path: `tg-demo-importer/v1/data?slug=${slug}`,
					method: 'GET',
				});

				if (!response.success) {
					setError(response.message || 'Something went wrong');
				} else if (!response.data || Object.keys(response.data).length === 0) {
					setEmpty(true);
				} else {
					setDemo(response.data);
					setEmpty(false);
				}
				setLoading(false);
			} catch (e) {
				console.error('Failed to fetch site data:', e);
			}
		};

		// Small delay to ensure DOM changes are applied before showing loading
		const timer = setTimeout(() => {
			fetchSiteData();
		}, 10);

		return () => {
			document.body.classList.remove('tg-full-overlay-active');
			document.documentElement.classList.add('wp-toolbar');
			window.removeEventListener('resize', handleResize);
			clearTimeout(timer);
		};
	}, [slug]);

	if (loading)
		return (
			<div className="tg-full-overlay">
				<div className="w-[375px]">
					<div className="tg-full-overlay-sidebar">
						<div className="space-y-6">
							<div className="space-y-2">
								<div className="h-6 bg-gray-300 rounded w-full animate-pulse" />
								<div className="h-6 bg-gray-300 rounded w-full animate-pulse" />
							</div>
							<div className="space-y-2">
								<div className="h-6 bg-gray-300 rounded w-1/2 animate-pulse" />
								<div className="h-32 bg-gray-300 rounded animate-pulse" />
							</div>
							<div className="space-y-2">
								<div className="h-6 bg-gray-300 rounded w-1/2 animate-pulse" />
								<div className="h-10 bg-gray-200 rounded animate-pulse" />
							</div>
							<div className="space-y-2">
								<div className="h-6 bg-gray-300 rounded w-1/2 animate-pulse" />
								<div className="h-10 bg-gray-200 rounded animate-pulse" />
							</div>
						</div>
					</div>
					<div className="sticky left-0 bottom-0 w-full p-[24px] flex justify-center gap-[10px] box-border border-0 border-t border-r border-solid border-[#E9E9E9] bg-white">
						<div className="h-6 bg-gray-300 rounded w-full animate-pulse" />
					</div>
				</div>
				<div className="tg-full-overlay-content bg-[#f4f4f4] w-full">
					<Lottie animationData={spinner} loop={true} autoplay={true} className="h-4 py-20" />
					{/* <img
						src={require(`../../assets/images/iframe-skeleton.png`)}
						alt={slug}
						className="w-full h-full border border-solid border-[#F4F4F4] rounded-[2px]"
					/> */}
				</div>
			</div>
		);

	if (error)
		return (
			<div className="tg-full-overlay">
				<div
					className="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800"
					role="alert"
				>
					<svg
						className="shrink-0 inline w-4 h-4 me-3"
						xmlns="http://www.w3.org/2000/svg"
						fill="currentColor"
						viewBox="0 0 20 20"
					>
						<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
					</svg>
					<span className="font-medium">No demos available.</span>
				</div>
			</div>
		);

	if (empty)
		return (
			<div className="tg-full-overlay">
				<div
					className="flex items-center p-4 m-4 text-sm text-blue-800 border border-solid border-blue-300 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-800"
					role="alert"
				>
					<svg
						className="shrink-0 inline w-4 h-4 me-3"
						xmlns="http://www.w3.org/2000/svg"
						fill="currentColor"
						viewBox="0 0 20 20"
					>
						<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
					</svg>
					<span className="font-medium">No data found for this demo.</span>
				</div>
			</div>
		);

	return (
		<>
			<div className="tg-full-overlay relative">
				{collapse ? (
					<button
						type="button"
						className="bg-white rounded-full px-[8px] py-[16px] border border-solid border-[#E1E1E1] cursor-pointer absolute top-[45%] left-[1%] shadow-custom-light"
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
							<path
								d="M2.5 6L9.5 6"
								stroke="#383838"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
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
							className="bg-white rounded-full px-[8px] py-[16px] border border-solid border-[#E1E1E1] cursor-pointer absolute top-[45%] left-[285px] shadow-custom-light"
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
								<path
									d="M9.5 6H2.5"
									stroke="#383838"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
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
							device={device}
							setDevice={setDevice}
						/>
					</>
				)}
				<ImportContent
					demo={demo}
					iframeRef={iframeRef}
					siteTitle={siteTitle}
					siteTagline={siteTagline}
					siteLogoId={siteLogoId}
					// currentTheme={data.current_theme}
					// zakraProInstalled={data.zakra_pro_installed}
					// zakraProActivated={data.zakra_pro_activated}
					// data={localizedData}
					// setData={setLocalizedData}
					device={device}
				/>
			</div>
		</>
	);
};

export default Import;
