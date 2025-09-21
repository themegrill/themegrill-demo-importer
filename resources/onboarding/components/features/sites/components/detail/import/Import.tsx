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
		: [];

	const predefinedPalettes = [
		[
			'#269bd1',
			'#1e7ba6',
			'#FFFFFF',
			'#F9FEFD',
			'#27272A',
			'#16181A',
			'#51585f',
			'#FFFFFF',
			'#e4e4e7',
		],
		[
			'#F44336',
			'#D12729',
			'#FFFFFF',
			'#FEF6F4',
			'#0F000A',
			'#252020',
			'#7E7777',
			'#FFFFFF',
			'#C1BDBD',
		],
		[
			'#4CAF50',
			'#379643',
			'#FFFFFF',
			'#FAFEF6',
			'#000504',
			'#141614',
			'#858585',
			'#FFFFFF',
			'#BDBDBD',
		],
		[
			'#FFA726',
			'#DB851B',
			'#FFFFFF',
			'#FFFDF6',
			'#0B0A0A',
			'#121110',
			'#828282',
			'#FFFFFF',
			'#B7B5B3',
		],
	];

	// Helper function to compare two arrays of strings
	const arraysEqual = (arr1: string[], arr2: string[]) => {
		if (arr1.length !== arr2.length) return false;
		return arr1.every((val, index) => val === arr2[index]);
	};

	// Find if defaultColorPalette exists in predefinedPalettes
	const existingIndex = predefinedPalettes.findIndex((palette) =>
		arraysEqual(defaultColorPalette, palette),
	);

	let colorPalette: string[][];

	if (existingIndex !== -1) {
		// If defaultColorPalette exists, move it to index 0
		const foundPalette = predefinedPalettes[existingIndex]!; // Non-null assertion since we know it exists
		colorPalette = [
			foundPalette,
			...predefinedPalettes.filter((_, index) => index !== existingIndex),
		];
	} else {
		// If defaultColorPalette doesn't exist, add it at index 0
		colorPalette = [defaultColorPalette, ...predefinedPalettes];
	}

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

	const actualBodyTypography = bodyTypography === 'Inherit' ? 'System' : bodyTypography;
	const actualHeadingTypography =
		headingTypography === 'Inherit' ? actualBodyTypography : headingTypography;

	const defaultTypography: string[] =
		actualBodyTypography && actualHeadingTypography
			? [actualHeadingTypography, actualBodyTypography]
			: ['Inter', 'Inter'];

	const typography = [
		defaultTypography,
		['Rubik', 'Lato'],
		['PT Serif', 'Roboto'],
		['IBM Plex Serif', 'Inter'],
		['Bitter', 'Public Sans'],
		['Outfit', 'DM Sans'],
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
