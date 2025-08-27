import { useEffect, useRef, useState } from 'react';
import { Demo, PageType, PageWithSelection, PluginItem } from '../../../../../../lib/types';
import { useLocalizedData } from '../../../../../../LocalizedDataContext';
import { Route } from '../../../../../../routes/import.$theme.$id';
import Pages from '../pages/Pages';
import Content from './Content';
import FeatureSidebar from './FeatureSidebar';
import Sidebar from './Sidebar';
import StartImport from './StartImport';

declare const require: any;

const Import = () => {
	const data = Route.useLoaderData();

	const { localizedData } = useLocalizedData();
	const { id } = Route.useParams();
	const iframeRef = useRef<HTMLIFrameElement>(null);

	const [siteLogoId, setSiteLogoId] = useState<number>(0);
	const [demo, setDemo] = useState(data.demo || ({} as Demo));
	const [pages, setPages] = useState<PageType[]>(data.pages || []);
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
	const [plugins, setPlugins] = useState<PluginItem[]>(data.plugins || []);
	const [device, setDevice] = useState('desktop');
	const [pageImport, setPageImport] = useState('all');
	const [isPagesSelected, setIsPagesSelected] = useState(false);
	const [showFeatureLayout, setShowFeatureLayout] = useState(false);
	const [showSidebar, setShowSidebar] = useState(true);
	const [open, setOpen] = useState(false);

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
	}, [pages]);

	useEffect(() => {
		// Add the class when the component mounts
		document.body.classList.add('tg-full-overlay-active');
		document.documentElement.classList.remove('wp-toolbar');
	}, [id]);

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

	if (data.isEmpty) {
		return (
			<div className="flex items-center justify-center h-screen">
				<div className="text-center">
					<h2>No Demo Available</h2>
					<p>This demo doesn't have any content available to import.</p>
				</div>
			</div>
		);
	}

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
