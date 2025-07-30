import Lottie from 'lottie-react';
import React, { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import spinner from './assets/animation/spinner.json';
import Content from './components/content/Content';
import Header from './components/header/Header';
import { DataObjectType, ThemeItem } from './lib/types';
import { useLocalizedData } from './LocalizedDataContext';

const Home = () => {
	const { localizedData, setLocalizedData } = useLocalizedData();

	const plans = {
		all: 'All',
		free: 'Free',
		pro: 'Pro',
	};

	const themeSlug = localizedData?.theme || 'all';
	const baseTheme = themeSlug.endsWith('-pro') ? themeSlug.replace('-pro', '') : themeSlug;
	const themeName = localizedData?.theme_name || 'All';
	const baseThemeName = localizedData?.theme_name.endsWith(' Pro')
		? themeName.replace(' Pro', '')
		: themeName;

	const [data, setData] = useState<DataObjectType>(localizedData?.data || []);
	const [loading, setLoading] = useState(true);
	const [contentLoading, setContentLoading] = useState(true);
	const [searchParams, setSearchParams] = useSearchParams();
	const [error, setError] = useState('');

	const { theme, pagebuilder, plan, search } = useMemo(() => {
		return {
			theme: searchParams.get('theme') || baseTheme || 'all',
			pagebuilder: searchParams.get('pagebuilder') || 'all',
			plan: searchParams.get('plan') || 'all',
			search: searchParams.get('search') || '',
		};
	}, [searchParams, baseTheme]);

	useEffect(() => {
		setSearchParams((prev) => {
			prev.set('theme', baseTheme);
			prev.set('pagebuilder', 'all');
			prev.set('category', 'all');
			return prev;
		});
	}, []);

	const validatedTheme = useMemo(() => {
		const validThemes = ['zakra', 'colormag', 'elearning'];

		if (baseTheme === 'all') {
			return validThemes.includes(theme) || theme === 'all' ? theme : 'all';
		} else {
			return theme === baseTheme ? theme : baseTheme;
		}
	}, [theme, baseTheme]);

	useEffect(() => {
		if (validatedTheme !== theme) {
			setSearchParams((prev) => {
				prev.set('theme', validatedTheme);
				return prev;
			});
		}
	}, [validatedTheme, theme, setSearchParams]);

	const demos = useMemo(() => {
		let $demos = [];
		if (validatedTheme === 'all') {
			$demos = Object.values(data || {}).reduce((acc, curr) => {
				acc = [...acc, ...(curr.demos || [])];
				return acc;
			}, [] as ThemeItem[]);
		} else {
			$demos = data?.[validatedTheme]?.demos || [];
		}
		return $demos;
	}, [data, validatedTheme]);

	const themes = useMemo(() => {
		if (!data || Object.keys(data).length === 0) {
			if (baseTheme && baseTheme !== 'all') {
				return [{ slug: baseTheme, name: baseThemeName }];
			}
			return [{ slug: 'all', name: 'All' }];
		}

		const allThemes = Object.entries(data).map(([key, value]) => ({
			slug: key,
			name: value.name,
		}));

		// Add "All" option if we have multiple themes
		if (allThemes.length > 1) {
			return [{ slug: 'all', name: 'All' }, ...allThemes];
		}

		return allThemes;
	}, [data, baseTheme]);

	const pagebuilders = useMemo(() => {
		if (!data || Object.entries(data).length === 0) {
			return [
				{
					slug: 'all',
					value: 'All',
					count: 0,
				},
			];
		}

		const filteredResults = demos.filter((d) => {
			const planMatch =
				plan === 'all' || (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium);
			const searchMatch = !search || d.name.toLowerCase().includes(search.toLowerCase());

			return planMatch && searchMatch;
		});

		const pagebuilderMap = new Map();

		Object.entries(data).forEach(([key, value]) => {
			if (theme !== 'all' && key !== theme) return;

			if (value.pagebuilders) {
				Object.entries(value.pagebuilders).forEach(([pbKey, pbValue]) => {
					if (!pagebuilderMap.has(pbKey)) {
						pagebuilderMap.set(pbKey, {
							slug: pbKey,
							value: pbValue,
							count: 0,
						});
					}
				});
			}
		});

		// Calculate counts
		pagebuilderMap.forEach((pb, key) => {
			if (key === 'all') {
				pb.count = filteredResults.length;
			} else {
				pb.count = filteredResults.filter(
					(d) => d.pagebuilders && Object.keys(d.pagebuilders).includes(key),
				).length;
			}
		});

		return Array.from(pagebuilderMap.values());
	}, [data, demos, theme, plan, search]);

	const categories = useMemo(() => {
		if (!data || Object.entries(data).length === 0) {
			return [
				{
					slug: 'all',
					value: 'All',
					count: 0,
				},
			];
		}

		const filteredResults = demos.filter((d) => {
			const pagebuilderMatch =
				pagebuilder === 'all' ||
				(d.pagebuilders && Object.keys(d.pagebuilders).includes(pagebuilder));

			const planMatch =
				plan === 'all' || (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium);

			const searchMatch = !search || d.name.toLowerCase().includes(search.toLowerCase());

			return pagebuilderMatch && planMatch && searchMatch;
		});

		const categoryMap = new Map();

		Object.entries(data).forEach(([themeKey, themeValue]) => {
			if (theme !== 'all' && themeKey !== theme) return;

			if (themeValue.categories) {
				Object.entries(themeValue.categories).forEach(([catKey, catValue]) => {
					if (!categoryMap.has(catKey)) {
						categoryMap.set(catKey, {
							slug: catKey,
							value: catValue,
							count: 0,
						});
					}
				});
			}
		});

		// Calculate counts
		categoryMap.forEach((cat, key) => {
			if (key === 'all') {
				cat.count = filteredResults.length;
			} else {
				cat.count = filteredResults.filter(
					(d) => d.categories && Object.keys(d.categories).includes(key),
				).length;
			}
		});

		return Array.from(categoryMap.values());
	}, [data, demos, theme, pagebuilder, plan, search]);

	const currentPagebuilder = useMemo(() => {
		const pb = pagebuilders.find((p) => p.slug === pagebuilder);
		return pb ? `${pb.value} (${pb.count})` : '';
	}, [pagebuilders, pagebuilder]);

	useEffect(() => {
		if (!currentPagebuilder && pagebuilders.length > 0) {
			setSearchParams((prev) => {
				prev.set('pagebuilder', 'all');
				return prev;
			});
		}
	}, [currentPagebuilder, pagebuilders]);

	useEffect(() => {
		const timer = setTimeout(() => {
			const hasData =
				demos.length !== 0 &&
				pagebuilders.length !== 0 &&
				categories.length !== 0 &&
				themes.length > 0;

			if (!hasData) {
				setError(localizedData?.error_msg || 'Something went wrong');
			}

			setLoading(false);
			setContentLoading(false);
		}, 1000);
		return () => clearTimeout(timer);
	}, [demos, pagebuilders, categories, themes]);

	useEffect(() => {
		setContentLoading(true);
	}, [theme, pagebuilder, plan, search]);

	if (error) {
		return (
			<div
				className="flex items-center p-4 m-4 text-sm text-red-800 border border-solid border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800"
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
				<span className="font-medium">{error}</span>
			</div>
		);
	}
	return (
		<>
			{loading ? (
				<Lottie animationData={spinner} loop={true} autoplay={true} className="h-16 my-8" />
			) : (
				<>
					<Header
						themes={themes}
						pagebuilders={pagebuilders}
						currentPagebuilder={currentPagebuilder}
						plans={plans}
						theme={baseTheme}
						data={demos}
					/>
					{contentLoading ? (
						<Lottie animationData={spinner} loop={true} autoplay={true} className="h-4 py-20" />
					) : (
						<Content categories={categories} demos={demos} />
					)}
				</>
			)}
		</>
	);
};

export default Home;
