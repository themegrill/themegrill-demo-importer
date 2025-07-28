import apiFetch from '@wordpress/api-fetch';
import Lottie from 'lottie-react';
import React, { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import loader from './assets/animation/loader.json';
import spinner from './assets/animation/spinner.json';
import Content from './components/content/Content';
import Header from './components/header/Header';
import { FilterItem, TDIDashboardType, ThemeDataResponse, ThemeItem } from './lib/types';

type Props = {
	localizedData: TDIDashboardType;
	setLocalizedData: React.Dispatch<React.SetStateAction<TDIDashboardType>>;
};

const Home = ({ localizedData, setLocalizedData }: Props) => {
	// const {
	// 	theme,
	// 	pagebuilder,
	// 	category,
	// 	plan,
	// 	search,
	// 	searchResults,
	// 	searchTerms,
	// 	setTheme,
	// 	setPagebuilder,
	// 	setCategory,
	// 	setPlan,
	// 	setSearch,
	// 	setSearchResults,
	// } = useDemoContext();
	const plans = {
		all: 'All',
		free: 'Free',
		pro: 'Pro',
	};

	// const themeBasedData = data?.data || {};
	const themeData = localizedData?.theme || 'all';
	const baseTheme = themeData.endsWith('-pro') ? themeData.replace('-pro', '') : themeData;
	// const { pathname } = useLocation();
	// const match = matchPath('/import-detail/:slug/:pagebuilder', pathname);
	// const showTabs = !match;

	const [data, setData] = useState<ThemeItem[]>([]);
	const [categoryFilter, setCategoryFilter] = useState<FilterItem>({});
	const [pagebuilderFilter, setPagebuilderFilter] = useState<FilterItem>({});
	const [themes, setThemes] = useState<Record<string, string>>({});
	const [loading, setLoading] = useState(true);
	const [errorNotice, setErrorNotice] = useState(true);
	const [contentLoading, setContentLoading] = useState(true);
	const [searchParams, setSearchParams] = useSearchParams();
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

	// const [data, setData] = useState<TDIDashboardType>(__TDI_DASHBOARD__);
	useEffect(() => {
		setContentLoading(true);
		const fetchSites = async () => {
			const params = new URLSearchParams();
			/**
			 * Theme validation logic:
			 * - If baseTheme is 'all': Allow any valid theme, reset invalid ones to 'all'
			 * - If baseTheme is specific: Force that theme only, correct URL if different theme selected
			 */
			const validThemes = ['zakra', 'colormag', 'elearning'];
			if (baseTheme === 'all') {
				if (validThemes.includes(theme)) {
					params.append('theme', theme);
				} else {
					setSearchParams((prev) => {
						prev.set('theme', 'all');
						return prev;
					});
				}
			} else {
				if (theme === baseTheme) {
					params.append('theme', theme);
				} else {
					setSearchParams((prev) => {
						prev.set('theme', baseTheme);
						return prev;
					});
					params.append('theme', baseTheme);
				}
			}
			// if (theme && theme !== 'all') {
			// 	params.append('theme', theme);
			// }

			// Build the path
			const queryString = params.toString();
			const path = `tg-demo-importer/v1/sites${queryString ? `?${queryString}` : ''}`;

			try {
				const response = await apiFetch<ThemeDataResponse>({
					path: path,
					method: 'GET',
				});
				if (response.success) {
					if (response.data.length === 0 && theme !== 'all') {
						setSearchParams((prev) => {
							prev.set('theme', 'all');
							return prev;
						});
					}
					setData(response.data);
					setCategoryFilter(response.filter_options.categories);
					setPagebuilderFilter(response.filter_options.pagebuilders);
					setThemes(response.filter_options.themes || {});
					setLoading(false);
					setContentLoading(false);
					// setErrorNotice(false);
				} else {
					console.error('Failed to fetch sites:', response);
					// setErrorNotice(true);
				}
			} catch (e) {
				// Handle error
				console.error('Failed to fetch sites:', e);
			}
		};

		fetchSites();
	}, [theme]);

	const pagebuilders = useMemo(() => {
		if (
			!data ||
			data.length === 0 ||
			!pagebuilderFilter ||
			Object.keys(pagebuilderFilter).length === 0
		) {
			return [
				{
					slug: 'all',
					value: 'All',
					count: 0,
				},
			];
		}

		const filteredResults = data.filter((d) => {
			// const themeMatch = theme === 'all' || d.theme_slug === theme;
			const planMatch =
				plan === 'all' || (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium);
			const searchMatch = !search || d.name.toLowerCase().includes(search.toLowerCase());

			return planMatch && searchMatch;
			// return themeMatch && planMatch && searchMatch;
		});

		const result = [
			{
				slug: 'all',
				value: 'All',
				count: filteredResults.length,
			},
		];

		Object.entries(pagebuilderFilter).forEach(([pbKey, pbValue]) => {
			const count = filteredResults.filter(
				(d) => d.pagebuilders && Object.keys(d.pagebuilders).includes(pbKey),
			).length;

			if (count > 0) {
				result.push({
					slug: pbKey,
					value: pbValue,
					count: count,
				});
			}
		});

		return result;
	}, [data, plan, search, pagebuilderFilter]);

	const categories = useMemo(() => {
		if (!data || data.length === 0) return [];

		const filteredResults = data.filter((item) => {
			const pagebuilderMatch =
				pagebuilder === 'all' ||
				(item.pagebuilders && Object.keys(item.pagebuilders).includes(pagebuilder));

			const planMatch =
				plan === 'all' || (plan === 'pro' ? item.pro || item.premium : !item.pro && !item.premium);

			const searchMatch = !search || item.name.toLowerCase().includes(search.toLowerCase());

			return pagebuilderMatch && planMatch && searchMatch;
		});

		const result = [
			{
				slug: 'all',
				value: 'All',
				count: filteredResults.length,
			},
		];

		Object.entries(categoryFilter).forEach(([pbKey, pbValue]) => {
			const count = filteredResults.filter(
				(d) => d.categories && Object.keys(d.categories).includes(pbKey),
			).length;

			if (count > 0) {
				result.push({
					slug: pbKey,
					value: pbValue,
					count: count,
				});
			}
		});

		return result;
	}, [data, pagebuilder, plan, search, categoryFilter]);

	// // const pagebuilders = useMemo(() => {
	// // 	if (!data || !searchResults) return [];

	// // 	const filteredResults = searchResults.filter((d) => {
	// // 		const themeMatch = theme === 'all' || d.theme === theme;
	// // 		const planMatch =
	// // 			plan === 'all' || (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium);
	// // 		const searchMatch = !search || d.name.toLowerCase().includes(search.toLowerCase());

	// // 		return themeMatch && planMatch && searchMatch;
	// // 	});

	// // 	const pagebuilderMap = new Map();

	// // 	// Get pagebuilders from data structure
	// // 	Object.entries(data).forEach(([themeKey, themeValue]) => {
	// // 		if (theme !== 'all' && themeKey !== theme) return;

	// // 		if (themeValue.pagebuilders) {
	// // 			Object.entries(themeValue.pagebuilders).forEach(([pbKey, pbValue]) => {
	// // 				if (!pagebuilderMap.has(pbKey)) {
	// // 					pagebuilderMap.set(pbKey, {
	// // 						slug: pbKey,
	// // 						value: pbValue,
	// // 						count: 0,
	// // 					});
	// // 				}
	// // 			});
	// // 		}
	// // 	});

	// // 	// Calculate counts
	// // 	pagebuilderMap.forEach((pb, key) => {
	// // 		if (key === 'all') {
	// // 			pb.count = filteredResults.length;
	// // 		} else {
	// // 			pb.count = filteredResults.filter(
	// // 				(d) => d.pagebuilders && Object.keys(d.pagebuilders).includes(key),
	// // 			).length;
	// // 		}
	// // 	});

	// // 	return Array.from(pagebuilderMap.values());
	// // }, [data, theme, searchResults, plan, search]);

	// // Generate categories list with counts
	// const categories = useMemo(() => {
	// 	if (!themeBasedData || !allDemos) return [];

	// 	const filteredResults = allDemos.filter((d) => {
	// 		const themeMatch = theme === 'all' || d.theme === theme;
	// 		const pagebuilderMatch =
	// 			pagebuilder === 'all' ||
	// 			(d.pagebuilders && Object.keys(d.pagebuilders).includes(pagebuilder));
	// 		const planMatch =
	// 			plan === 'all' || (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium);
	// 		const searchMatch = !search || d.name.toLowerCase().includes(search.toLowerCase());

	// 		return themeMatch && pagebuilderMatch && planMatch && searchMatch;
	// 	});

	// 	const categoryMap = new Map();

	// 	// Get categories from data structure
	// 	Object.entries(themeBasedData).forEach(([themeKey, themeValue]) => {
	// 		if (theme !== 'all' && themeKey !== theme) return;

	// 		if (themeValue.categories) {
	// 			Object.entries(themeValue.categories).forEach(([catKey, catValue]) => {
	// 				if (!categoryMap.has(catKey)) {
	// 					categoryMap.set(catKey, {
	// 						slug: catKey,
	// 						value: catValue,
	// 						count: 0,
	// 					});
	// 				}
	// 			});
	// 		}
	// 	});

	// 	// Calculate counts
	// 	categoryMap.forEach((cat, key) => {
	// 		if (key === 'all') {
	// 			cat.count = filteredResults.length;
	// 		} else {
	// 			cat.count = filteredResults.filter(
	// 				(d) => d.categories && Object.keys(d.categories).includes(key),
	// 			).length;
	// 		}
	// 	});

	// 	return Array.from(categoryMap.values());
	// }, [themeBasedData, theme, pagebuilder, plan, search]);

	// const categories = useMemo(() => {
	// 	if (!data || !searchResults) return [];

	// 	const filteredResults = searchResults.filter((d) => {
	// 		const themeMatch = theme === 'all' || d.theme === theme;
	// 		const pagebuilderMatch =
	// 			pagebuilder === 'all' ||
	// 			(d.pagebuilders && Object.keys(d.pagebuilders).includes(pagebuilder));
	// 		const planMatch =
	// 			plan === 'all' || (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium);
	// 		const searchMatch = !search || d.name.toLowerCase().includes(search.toLowerCase());

	// 		return themeMatch && pagebuilderMatch && planMatch && searchMatch;
	// 	});

	// 	const categoryMap = new Map();

	// 	// Get categories from data structure
	// 	Object.entries(data).forEach(([themeKey, themeValue]) => {
	// 		if (theme !== 'all' && themeKey !== theme) return;

	// 		if (themeValue.categories) {
	// 			Object.entries(themeValue.categories).forEach(([catKey, catValue]) => {
	// 				if (!categoryMap.has(catKey)) {
	// 					categoryMap.set(catKey, {
	// 						slug: catKey,
	// 						value: catValue,
	// 						count: 0,
	// 					});
	// 				}
	// 			});
	// 		}
	// 	});

	// 	// Calculate counts
	// 	categoryMap.forEach((cat, key) => {
	// 		if (key === 'all') {
	// 			cat.count = filteredResults.length;
	// 		} else {
	// 			cat.count = filteredResults.filter(
	// 				(d) => d.categories && Object.keys(d.categories).includes(key),
	// 			).length;
	// 		}
	// 	});

	// 	return Array.from(categoryMap.values());
	// }, [data, theme, pagebuilder, searchResults, plan, search]);

	// const currentPagebuilder = useMemo(() => {
	// 	const pb = pagebuilderCount[pagebuilder];
	// 	return pb ? `${pb.name} (${pb.count})` : '';
	// }, [pagebuilderCount, pagebuilder]);

	const currentPagebuilder = useMemo(() => {
		const pb = pagebuilders.find((p) => p.slug === pagebuilder);
		return pb ? `${pb.value} (${pb.count})` : '';
	}, [pagebuilders, pagebuilder]);

	useEffect(() => {
		if (!currentPagebuilder && Object.entries(pagebuilderFilter).length > 0) {
			setSearchParams((prev) => {
				prev.set('pagebuilder', 'all');
				return prev;
			});
		}
	}, [currentPagebuilder, pagebuilderFilter]);

	// Update URL params when filters change
	// useEffect(() => {
	// 	const newParams = new URLSearchParams();

	// 	newParams.set('theme', theme);
	// 	newParams.set('pagebuilder', pagebuilder);
	// 	// newParams.set('category', category);

	// 	if (plan !== 'all') newParams.set('plan', plan);
	// 	if (search) newParams.set('search', search);

	// 	const newUrl = newParams.toString();
	// 	const currentUrl = searchParams.toString();

	// 	if (newUrl !== currentUrl) {
	// 		setSearchParams(newParams);
	// 	}
	// }, [theme, pagebuilder, category, plan, search, searchParams, setSearchParams]);

	// const themes = useMemo(() => {
	// 	if ('all' === initialTheme) {
	// 		const allThemeObject = {
	// 			slug: 'all',
	// 			name: 'All',
	// 		};
	// 		const allThemes = Object.entries(data || {}).map(([key, value]) => {
	// 			return {
	// 				slug: key,
	// 				name: value.name,
	// 			};
	// 		});
	// 		return [allThemeObject, ...allThemes];
	// 	} else {
	// 		const currentTheme = Object.entries(data || {})
	// 			.filter(([key, _]) => key === theme)
	// 			.map(([key, value]) => {
	// 				return {
	// 					slug: key,
	// 					name: value.name,
	// 				};
	// 			});
	// 		return currentTheme;
	// 	}
	// }, [theme, pagebuilder, category, searchResults]);

	// const pagebuilders = useMemo(() => {
	// 	if ('all' === initialTheme) {
	// 		const result = Object.entries(data || {})
	// 			.filter(([key, value]) => ('all' !== theme ? key === theme : true))
	// 			.reduce((acc, [key, value]) => {
	// 				Object.entries(value)
	// 					.filter(([key2]) => key2 === 'pagebuilders')
	// 					.map(([key2, value2]) => {
	// 						Object.entries(value2).map(([key3, value3]) => {
	// 							if (!acc.has(key3)) {
	// 								if ('all' === key3) {
	// 									acc.set(key3, {
	// 										slug: key3,
	// 										value: value3,
	// 										count:
	// 											searchResults
	// 												.filter((d) => ('all' !== theme ? d.theme === key : true))
	// 												.filter((d) =>
	// 													'all' !== plan
	// 														? plan === 'pro'
	// 															? d.pro || d.premium
	// 															: !d.pro && !d.premium
	// 														: true,
	// 												)
	// 												.filter((d) =>
	// 													searchParams.get('search')
	// 														? d.name
	// 																.toLowerCase()
	// 																.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
	// 														: true,
	// 												)?.length ?? 0,
	// 									});
	// 								} else {
	// 									acc.set(key3, {
	// 										slug: key3,
	// 										value: value3,
	// 										count:
	// 											searchResults
	// 												.filter((d) => ('all' !== theme ? d.theme === key : true))
	// 												.filter((d) => Object.keys(d.pagebuilders).some((p) => p === key3))
	// 												.filter((d) =>
	// 													'all' !== plan
	// 														? plan === 'pro'
	// 															? d.pro || d.premium
	// 															: !d.pro && !d.premium
	// 														: true,
	// 												)
	// 												.filter((d) =>
	// 													searchParams.get('search')
	// 														? d.name
	// 																.toLowerCase()
	// 																.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
	// 														: true,
	// 												)?.length ?? 0,
	// 									});
	// 								}
	// 							}
	// 						});
	// 					});
	// 				return acc;
	// 			}, new Map())
	// 			.values();
	// 		return Array.from(result);
	// 	} else {
	// 		return Object.entries(data?.[theme]?.pagebuilders || {}).map(([key, val]) => ({
	// 			slug: key,
	// 			value: val,
	// 			count:
	// 				'all' === key
	// 					? (searchResults?.length ?? 0)
	// 					: (searchResults.filter((d) => Object.keys(d.pagebuilders).some((p) => p === key))
	// 							?.length ?? 0),
	// 		}));
	// 	}
	// }, [theme, pagebuilder, category, searchResults, searchParams, plan]);

	// const categories = useMemo(() => {
	// 	if ('all' === initialTheme) {
	// 		const result = Object.entries(data || {})
	// 			.filter(([key, value]) => ('all' !== theme ? key === theme : true))
	// 			.reduce((acc, [key, value]) => {
	// 				Object.entries(value)
	// 					.filter(([key2]) => key2 === 'categories')
	// 					.map(([key2, value2]) => {
	// 						Object.entries(value2).map(([key3, value3]) => {
	// 							if (!acc.has(key3)) {
	// 								if ('all' === key3) {
	// 									acc.set(key3, {
	// 										slug: key3,
	// 										value: value3,
	// 										count:
	// 											searchResults
	// 												.filter((d) => ('all' !== theme ? d.theme === key : true))
	// 												.filter((d) =>
	// 													'all' !== pagebuilder
	// 														? Object.keys(d.pagebuilders).some((p) => p === pagebuilder)
	// 														: true,
	// 												)
	// 												.filter((d) =>
	// 													'all' !== plan
	// 														? plan === 'pro'
	// 															? d.pro || d.premium
	// 															: !d.pro && !d.premium
	// 														: true,
	// 												)
	// 												.filter((d) =>
	// 													searchParams.get('search')
	// 														? d.name
	// 																.toLowerCase()
	// 																.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
	// 														: true,
	// 												)?.length ?? 0,
	// 									});
	// 								} else {
	// 									acc.set(key3, {
	// 										slug: key3,
	// 										value: value3,
	// 										count:
	// 											searchResults
	// 												.filter((d) => ('all' !== theme ? d.theme === key : true))
	// 												.filter((d) =>
	// 													'all' !== pagebuilder
	// 														? Object.keys(d.pagebuilders).some((p) => p === pagebuilder)
	// 														: true,
	// 												)
	// 												.filter((d) => Object.keys(d.categories).some((p) => p === key3))
	// 												.filter((d) =>
	// 													'all' !== plan
	// 														? plan === 'pro'
	// 															? d.pro || d.premium
	// 															: !d.pro && !d.premium
	// 														: true,
	// 												)
	// 												.filter((d) =>
	// 													searchParams.get('search')
	// 														? d.name
	// 																.toLowerCase()
	// 																.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
	// 														: true,
	// 												)?.length ?? 0,
	// 									});
	// 								}
	// 							}
	// 						});
	// 					});
	// 				return acc;
	// 			}, new Map())
	// 			.values();
	// 		return Array.from(result);
	// 	} else {
	// 		return Object.entries(data?.[theme]?.categories || {}).map(([key, val]) => ({
	// 			slug: key,
	// 			value: val,
	// 			count:
	// 				'all' === key
	// 					? (searchResults?.length ?? 0)
	// 					: (searchResults.filter((d) => Object.keys(d.categories).some((p) => p === key))
	// 							?.length ?? 0),
	// 		}));
	// 	}
	// }, [theme, pagebuilder, category, searchResults, searchParams, plan]);

	// const currentPagebuilder = useMemo(() => {
	// 	const { value = '', count } = pagebuilders?.filter((p) => p.slug === pagebuilder)[0] || {};
	// 	if (value) {
	// 		return `${value} (${count})`;
	// 	}
	// 	return '';
	// }, [pagebuilders, pagebuilder]);

	// useEffect(() => {
	// 	setSearchResults(searchTerms);
	// 	setLoading(false);
	// }, [searchTerms]);

	// useEffect(() => {
	// 	if (!currentPagebuilder) {
	// 		setPagebuilder('all');
	// 	}
	// }, [currentPagebuilder]);

	// useEffect(() => {
	// 	setCategory('all');
	// 	const newParams = new URLSearchParams(searchParams);
	// 	newParams.set('theme', theme);
	// 	newParams.set('category', 'all');
	// 	newParams.set('pagebuilder', pagebuilder);
	// 	if (searchParams.has('search')) {
	// 		newParams.set('search', searchParams.get('search') || '');
	// 	}
	// 	if (searchParams.has('option')) {
	// 		newParams.set('option', searchParams.get('option') || '');
	// 	}
	// 	setSearchParams(newParams);
	// }, [theme, currentPagebuilder]);

	// useEffect(() => {
	// 	const newParams = new URLSearchParams(searchParams);
	// 	if (searchParams.has('search')) {
	// 		newParams.delete('search');
	// 	}
	// 	if (searchParams.has('option')) {
	// 		newParams.delete('option');
	// 	}
	// 	setSearchParams(newParams);
	// }, []);

	// if (errorNotice) {
	// 	return <div className="p-4 text-center">Something went wrong.</div>;
	// }
	return (
		<>
			{loading || data.length === 0 || pagebuilders.length === 0 || categories.length === 0 ? (
				<Lottie animationData={spinner} loop={true} autoplay={true} className="h-16 my-8" />
			) : (
				<>
					<Header
						themes={themes}
						pagebuilders={pagebuilders}
						currentPagebuilder={currentPagebuilder}
						plans={plans}
						theme={baseTheme}
						data={data}
					/>
					{contentLoading ? (
						<Lottie animationData={loader} loop={true} autoplay={true} className="h-40" />
					) : (
						<Content categories={categories} demos={data} />
					)}
				</>
			)}
		</>
	);
};

export default Home;
