import apiFetch from '@wordpress/api-fetch';
import Lottie from 'lottie-react';
import React, { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import spinner from '../../assets/animation/spinner.json';
import { Demo, TDIDashboardType } from '../../lib/types';
import ImportContent from './ImportContent';
import ImportSidebar from './ImportSidebar';

const Import = ({
	localizedData,
	setLocalizedData,
}: {
	localizedData: TDIDashboardType;
	setLocalizedData: React.Dispatch<React.SetStateAction<TDIDashboardType>>;
}) => {
	const iframeRef = useRef<HTMLIFrameElement>(null);
	const { slug } = useParams();
	const [demo, setDemo] = useState({} as Demo);
	const [loading, setLoading] = useState(true);
	const [siteTitle, setSiteTitle] = useState('');
	const [siteTagline, setSiteTagline] = useState('');
	const [siteLogoId, setSiteLogoId] = useState<number>(0);
	const [collapse, setCollapse] = useState(false);
	const [device, setDevice] = useState('desktop');

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
				const response = await apiFetch<{ success: boolean; data: any }>({
					path: `tg-demo-importer/v1/data?slug=${slug}`,
					method: 'GET',
				});
				if (response.success) {
					setDemo(response.data);
					setLoading(false);
				} else {
					console.error('Failed to fetch site data:', response);
				}
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

	return (
		<>
			{loading && Object.keys(demo).length === 0 ? (
				<div className="tg-full-overlay relative">
					<div className="tg-full-overlay-content bg-[#f4f4f4] w-full relative">
						<Lottie animationData={spinner} loop={true} autoplay={true} className="h-16 py-20" />
					</div>
				</div>
			) : (
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
						data={localizedData}
						setData={setLocalizedData}
						device={device}
					/>
				</div>
			)}
		</>
	);
};

export default Import;
