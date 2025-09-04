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
	const { id, theme } = Route.useParams();
	const iframeRef = useRef<HTMLIFrameElement>(null);

	const defaultColorPalette: string[] = data?.demo?.themeMods?.[`${theme}_color_palette`]?.colors
		? Object.values(data.demo.themeMods?.[`${theme}_color_palette`]?.colors)
		: [
				'#eaf3fb',
				'#bfdcf3',
				'#94c4eb',
				'#6aace2',
				'#257bc1',
				'#1d6096',
				'#15446b',
				'#0c2941',
				'#040e16',
			];
	const colorPalette = [
		defaultColorPalette,
		[
			'#E74C3C',
			'#9B59B6',
			'#3498DB',
			'#1ABC9C',
			'#F39C12',
			'#2ECC71',
			'#E67E22',
			'#34495E',
			'#95A5A6',
		],
		[
			'#FF1744',
			'#E91E63',
			'#9C27B0',
			'#673AB7',
			'#3F51B5',
			'#2196F3',
			'#00BCD4',
			'#009688',
			'#4CAF50',
		],
		[
			'#8BC34A',
			'#CDDC39',
			'#FFEB3B',
			'#FFC107',
			'#FF9800',
			'#FF5722',
			'#795548',
			'#607D8B',
			'#9E9E9E',
		],
		[
			'#FF4081',
			'#FF80AB',
			'#F8BBD9',
			'#E1BEE7',
			'#D1C4E9',
			'#C5CAE9',
			'#BBDEFB',
			'#B3E5FC',
			'#B2EBF2',
		],
		[
			'#212121',
			'#424242',
			'#616161',
			'#757575',
			'#9E9E9E',
			'#BDBDBD',
			'#E0E0E0',
			'#EEEEEE',
			'#F5F5F5',
		],
	];

	const typographyKeys = {
		zakra: {
			body: 'zakra_body_typography',
			heading: 'zakra_heading_typography',
		},
		colormag: {
			body: 'colormag_base_typography',
			heading: 'colormag_headings_typography',
		},
		elearning: {
			body: 'elearning_base_typography_body',
			heading: 'elearning_base_typography_heading',
		},
	};

	const bodyTypography: string =
		data?.demo?.themeMods?.[typographyKeys[theme as keyof typeof typographyKeys]?.body]?.[
			'font-family'
		];
	const headingTypography: string =
		data?.demo?.themeMods?.[typographyKeys[theme as keyof typeof typographyKeys]?.heading]?.[
			'font-family'
		];
	const defaultTypography: string[] =
		bodyTypography && headingTypography ? [bodyTypography, headingTypography] : ['Inter', 'Inter'];

	const typography = [
		defaultTypography,
		['Dancing Script', 'Story Script'],
		['Playfair Display', 'Lato'],
		['IBM Plex Sans Thai Looped', 'Lato'],
		['Raleway', 'Lato'],
		['DM Sans', 'Lato'],
		['Nunito', 'Lato'],
		['Courier New', 'Lato'],
		['Quando', 'Lato'],
	];

	const supportedThemes = ['zakra', 'colormag', 'elearning'];
	const isThemeSupported = supportedThemes.includes(theme || '');

	const [selectedPaletteIndex, setSelectedPaletteIndex] = useState<number>(0);
	const [selectedTypographyIndex, setSelectedTypographyIndex] = useState<number>(0);

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
					colorPalette={colorPalette}
					typography={typography}
					selectedPaletteIndex={selectedPaletteIndex}
					setSelectedPaletteIndex={setSelectedPaletteIndex}
					selectedTypographyIndex={selectedTypographyIndex}
					setSelectedTypographyIndex={setSelectedTypographyIndex}
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
					isThemeSupported={isThemeSupported}
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
				colorPalette={
					isThemeSupported && selectedPaletteIndex !== 0
						? (colorPalette[selectedPaletteIndex] ?? [])
						: []
				}
				typography={
					isThemeSupported && selectedTypographyIndex !== 0
						? (typography[selectedTypographyIndex] ?? [])
						: []
				}
			/>
		</div>
	);
};

export default Import;
