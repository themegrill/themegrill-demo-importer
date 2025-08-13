import apiFetch from '@wordpress/api-fetch';
import React, { useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Demo, PageType, PageWithSelection, PluginItem } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';
import Pages from '../pages/Pages';
import Content from './Content';
import FeatureSidebar from './FeatureSidebar';
import Sidebar from './Sidebar';
import StartImport from './StartImport';

declare const require: any;

const Import = () => {
	const pluginsList = [
		{
			plugin: 'everest-forms/everest-forms.php',
			name: 'Everest Form',
			description: 'Let visitors reach you through easy-to-use contact forms.',
			isSelected: false,
		},
		{
			plugin: 'woocommerce/woocommerce.php',
			name: 'Woocommerce',
			description: 'Sell products online and accept secure payments.',
			isSelected: false,
		},
	];

	const { localizedData } = useLocalizedData();
	const { slug, demo_theme, pagebuilder = '' } = useParams();
	const iframeRef = useRef<HTMLIFrameElement>(null);
	const [siteLogoId, setSiteLogoId] = useState<number>(0);

	const [demo, setDemo] = useState({} as Demo);
	const [error, setError] = useState<string | null>(null);
	const [empty, setEmpty] = useState<boolean>(false);
	const [loading, setLoading] = useState(true);
	const [device, setDevice] = useState('desktop');
	const [pageImport, setPageImport] = useState('all');
	const [isPagesSelected, setIsPagesSelected] = useState(false);
	const [showFeatureLayout, setShowFeatureLayout] = useState(false);
	const [showSidebar, setShowSidebar] = useState(true);
	const [plugins, setPlugins] = useState<PluginItem[]>(pluginsList);
	const [open, setOpen] = useState(false);
	const [pages, setPages] = useState<PageType[]>([]);
	const [allPages, setAllPages] = useState<PageWithSelection[]>(() => {
		return pages.map((p, index) => {
			return {
				id: index + 1,
				slug: p.slug,
				title: p.title,
				content: p.content,
				screenshot: p.screenshot,
				isSelected: index === 0 ? true : false,
			};
		});
	});

	useEffect(() => {
		const updatedPages = pages.map((p, index) => ({
			id: p.id ?? index + 1,
			slug: p.slug,
			title: p.title,
			content: p.content,
			screenshot: p.screenshot,
			isSelected: index === 0 ? true : false,
		}));

		setAllPages(updatedPages);
	}, [pagebuilder, pages]);

	useEffect(() => {
		// Add the class when the component mounts
		document.body.classList.add('tg-full-overlay-active');
		document.documentElement.classList.remove('wp-toolbar');

		const fetchSiteData = async () => {
			try {
				const response = await apiFetch<{ success: boolean; message?: string; data?: Demo }>({
					path: `tg-demo-importer/v1/data?slug=${slug}&theme=${demo_theme}`,
					method: 'GET',
				});
				if (!response.success) {
					setError(response.message || 'Something went wrong');
				} else if (!response.data || Object.keys(response.data).length === 0) {
					setEmpty(true);
				} else {
					setDemo(response.data);
					setPages(response.data?.pages || []);
					const mergedPlugins = mergePlugins(pluginsList, response.data?.plugins || {});
					const sortedPlugins = mergedPlugins.sort((a, b) => {
						const aMandatory = a.isMandatory || false;
						const bMandatory = b.isMandatory || false;

						// If both are mandatory or both are not mandatory, maintain original order
						if (aMandatory === bMandatory) {
							return 0;
						}
						// Put mandatory plugins first
						return bMandatory ? 1 : -1;
					});
					setPlugins(sortedPlugins);
					setEmpty(false);
					setLoading(false);
				}
			} catch (e) {
				console.error('Failed to fetch site data:', e);
			}
		};

		// Small delay to ensure DOM changes are applied before showing loading
		const timer = setTimeout(() => {
			fetchSiteData();
		}, 10);
	}, [slug]);

	const mergePlugins = (
		pluginsList: PluginItem[],
		plugins: Record<string, { name: string; description: string }>,
	): PluginItem[] => {
		const uniquePlugins = new Map();

		// Add existing pluginsList items to the map
		pluginsList.forEach((item) => {
			uniquePlugins.set(item.plugin, {
				plugin: item.plugin,
				name: item.name,
				description: item.description,
				isSelected: item.isSelected,
				isMandatory: false,
			});
		});

		// Add/Override with plugins from the API object with isSelected and isMandatory true
		Object.entries(plugins).forEach(([pluginPath, pluginData]) => {
			uniquePlugins.set(pluginPath, {
				plugin: pluginPath,
				name: pluginData.name,
				description: pluginData.description,
				isSelected: true,
				isMandatory: true,
			});
		});

		// Convert Map values back to array
		return Array.from(uniquePlugins.values());
	};

	const checkThemeExists = (demo: Demo) => {
		const proTheme = demo.theme_slug + '-pro';
		if (demo.theme_slug === 'zakra') {
			if (localizedData.zakra_pro_installed) {
				return true;
			}
			return false;
		}
		const themeExists = localizedData.installed_themes.includes(proTheme);
		return themeExists;
	};

	if (loading)
		return (
			<div className="flex h-screen">
				<div className="w-[350px] h-screen flex flex-col">
					<div className="p-6  border-0 border-r border-solid border-[#E9E9E9] flex flex-col gap-6 bg-[#FAFBFC] box-border flex-1">
						<div className="pb-6 border-0 border-b border-solid border-[#E3E3E3]">
							<div className="flex justify-between items-center">
								<div className="w-[123px] h-6 bg-[#E7E8E9] rounded-sm animate-pulse"></div>
								<div className="w-6 h-6 bg-[#E7E8E9] rounded animate-pulse"></div>
							</div>
							<div className="w-[212px] h-[12px] bg-[#E7E8E9] rounded-sm animate-pulse mt-2"></div>
						</div>
						<div>
							<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mb-5 "></div>
							<div className="w-[302px] h-[48px] bg-[#E7E8E9] rounded-md animate-pulse "></div>
						</div>
						<div>
							<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mb-5"></div>
							<div className="grid grid-cols-2 gap-[14px] ">
								{Array.from({ length: 6 }).map((_, outerIndex) => (
									<div
										className="h-[46px] w-[144px] bg-[#E7E8E9] rounded-md animate-pulse "
										key={outerIndex}
									></div>
								))}
							</div>
						</div>
						<div>
							<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mb-5"></div>
							<div className="grid grid-cols-3 gap-[14px] ">
								{Array.from({ length: 9 }).map((_, outerIndex) => (
									<div className="h-[46px] w-[92px] bg-[#E7E8E9] rounded-md animate-pulse "></div>
								))}
							</div>
							<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse mt-5 mb-4"></div>
							<div className="w-[302px] h-[48px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
						</div>
					</div>
					<div className="border-0 border-t border-r border-solid border-[#E9E9E9] bg-white p-[24px] pb-[12px]">
						<div className="w-[302px] h-[51px] bg-[#E7E8E9] rounded-md animate-pulse mb-4"></div>
						<div className="w-[74px] h-[24px] bg-[#E7E8E9] rounded-sm animate-pulse ml-auto mr-auto"></div>
					</div>
				</div>
				<div className="flex-1 p-[88px] grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-[40px] overflow-y-auto bg-[#fff]"></div>
			</div>
		);

	return (
		<div className="flex h-screen content-container">
			{!showFeatureLayout ? (
				<Sidebar
					demo={demo}
					iframeRef={iframeRef}
					setSiteLogoId={setSiteLogoId}
					device={device}
					setDevice={setDevice}
					pageImport={pageImport}
					setPageImport={setPageImport}
					setIsPagesSelected={setIsPagesSelected}
					onContinue={() => {
						setPageImport('all');
						if (demo.premium) {
							if (checkThemeExists(demo)) {
								if (
									!(demo.theme_slug === 'zakra'
										? localizedData.zakra_pro_activated
										: demo.theme_slug + '-pro' === localizedData.current_theme)
								) {
									setOpen(true);
								} else {
									setOpen(false);
									setShowFeatureLayout(true);
								}
							} else {
								setOpen(true);
							}
						} else {
							setOpen(false);
							setShowFeatureLayout(true);
						}
					}}
				/>
			) : (
				showSidebar && (
					<FeatureSidebar
						demo={demo}
						plugins={plugins}
						setPlugins={setPlugins}
						onOpen={() => setOpen(true)}
						setShowFeatureLayout={setShowFeatureLayout}
					/>
				)
			)}
			{pageImport === 'all' ? (
				<Content demo={demo} iframeRef={iframeRef} device={device} />
			) : (
				<Pages pages={allPages} setAllPages={setAllPages} demo={demo} />
			)}
			<StartImport
				open={open}
				setOpen={setOpen}
				onOpen={() => setOpen(true)}
				onClose={() => setOpen(false)}
				demo={demo}
				plugins={plugins}
				siteLogoId={siteLogoId}
				pages={allPages}
				setShowSidebar={setShowSidebar}
				isPagesSelected={isPagesSelected}
			/>
		</div>
	);
};

export default Import;
