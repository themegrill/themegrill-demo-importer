import Lottie from 'lottie-react';
import React, { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import loader from './assets/animation/loader.json';
import spinner from './assets/animation/spinner.json';
import Content from './components/content/Content';
import Header from './components/header/Header';
import { DataObjectType, ThemeItem } from './lib/types';
import { useLocalizedData } from './LocalizedDataContext';

// type Props = {
// 	localizedData: TDIDashboardType;
// 	setLocalizedData: React.Dispatch<React.SetStateAction<TDIDashboardType>>;
// };

const Home = () => {
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

	const { localizedData, setLocalizedData } = useLocalizedData();

	const plans = {
		all: 'All',
		free: 'Free',
		pro: 'Pro',
	};

	// const themeBasedData = data?.data || {};
	const themeSlug = localizedData?.theme || 'all';
	const baseTheme = themeSlug.endsWith('-pro') ? themeSlug.replace('-pro', '') : themeSlug;
	const themeName = localizedData?.theme_name || 'All';
	const baseThemeName = localizedData?.theme_name.endsWith(' Pro')
		? themeName.replace(' Pro', '')
		: themeName;
	// const { pathname } = useLocation();
	// const match = matchPath('/import-detail/:slug/:pagebuilder', pathname);
	// const showTabs = !match;
	// const [categoryFilter, setCategoryFilter] = useState<FilterItem>({});
	// const [pagebuilderFilter, setPagebuilderFilter] = useState<FilterItem>({});
	// const [themeFilter, setThemeFilter] = useState<Record<string, string>>({});

	const [data, setData] = useState<DataObjectType>(localizedData?.data || []);
	const [loading, setLoading] = useState(true);
	const [contentLoading, setContentLoading] = useState(true);
	const [searchParams, setSearchParams] = useSearchParams();
	const [isDataEmpty, setIsDataEmpty] = useState(false);

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

	// const [data, setData] = useState<TDIDashboardType>(__TDI_DASHBOARD__);
	// useEffect(() => {
	// 	setContentLoading(true);
	// 	const fetchSites = async () => {
	// 		const params = new URLSearchParams();
	// 		/**
	// 		 * Theme validation logic:
	// 		 * - If baseTheme is 'all': Allow any valid theme, reset invalid ones to 'all'
	// 		 * - If baseTheme is specific: Force that theme only, correct URL if different theme selected
	// 		 */
	// const validThemes = ['zakra', 'colormag', 'elearning'];
	// if (baseTheme === 'all') {
	// 	if (validThemes.includes(theme)) {
	// 		params.append('theme', theme);
	// 	} else {
	// 		setSearchParams((prev) => {
	// 			prev.set('theme', 'all');
	// 			return prev;
	// 		});
	// 	}
	// } else {
	// 	if (theme === baseTheme) {
	// 		params.append('theme', theme);
	// 	} else {
	// 		setSearchParams((prev) => {
	// 			prev.set('theme', baseTheme);
	// 			return prev;
	// 		});
	// 		params.append('theme', baseTheme);
	// 	}
	// }
	// 		// if (theme && theme !== 'all') {
	// 		// 	params.append('theme', theme);
	// 		// }

	// 		// Build the path
	// 		const queryString = params.toString();
	// 		const path = `tg-demo-importer/v1/sites${queryString ? `?${queryString}` : ''}`;

	// 		try {
	// 			const response = await apiFetch<ThemeDataResponse>({
	// 				path: path,
	// 				method: 'GET',
	// 			});
	// 			if (response.success) {
	// 				if (response.data.length === 0 && theme !== 'all') {
	// 					setSearchParams((prev) => {
	// 						prev.set('theme', 'all');
	// 						return prev;
	// 					});
	// 				}
	// 				setData(response.data);
	// 				setCategoryFilter(response.filter_options.categories);
	// 				setPagebuilderFilter(response.filter_options.pagebuilders);
	// 				setThemes(response.filter_options.themes || {});
	// 				setLoading(false);
	// 				setContentLoading(false);
	// 				// setErrorNotice(false);
	// 			} else {
	// 				console.error('Failed to fetch sites:', response);
	// 				// setErrorNotice(true);
	// 			}
	// 		} catch (e) {
	// 			// Handle error
	// 			console.error('Failed to fetch sites:', e);
	// 		}
	// 	};

	// 	fetchSites();
	// }, [theme]);
	// useEffect(() => {
	// 	setContentLoading(true);
	// 	const fetchSites = async () => {
	// 		const params = new URLSearchParams();
	// 		/**
	// 		 * Theme validation logic:
	// 		 * - If baseTheme is 'all': Allow any valid theme, reset invalid ones to 'all'
	// 		 * - If baseTheme is specific: Force that theme only, correct URL if different theme selected
	// 		 */
	// 		const validThemes = ['zakra', 'colormag', 'elearning'];
	// 		if (baseTheme === 'all') {
	// 			if (validThemes.includes(theme)) {
	// 				params.append('theme', theme);
	// 			} else {
	// 				setSearchParams((prev) => {
	// 					prev.set('theme', 'all');
	// 					return prev;
	// 				});
	// 			}
	// 		} else {
	// 			if (theme === baseTheme) {
	// 				params.append('theme', theme);
	// 			} else {
	// 				setSearchParams((prev) => {
	// 					prev.set('theme', baseTheme);
	// 					return prev;
	// 				});
	// 				params.append('theme', baseTheme);
	// 			}
	// 		}
	// 		// if (theme && theme !== 'all') {
	// 		// 	params.append('theme', theme);
	// 		// }

	// 		// Build the path
	// 		const queryString = params.toString();
	// 		const path = `tg-demo-importer/v1/sites${queryString ? `?${queryString}` : ''}`;

	// 		try {
	// 			const response = await apiFetch<ThemeDataResponse>({
	// 				path: path,
	// 				method: 'GET',
	// 			});
	// 			if (response.success) {
	// 				if (response.data.length === 0 && theme !== 'all') {
	// 					setSearchParams((prev) => {
	// 						prev.set('theme', 'all');
	// 						return prev;
	// 					});
	// 				}
	// 				setData(response.data);
	// 				setCategoryFilter(response.filter_options.categories);
	// 				setPagebuilderFilter(response.filter_options.pagebuilders);
	// 				setThemes(response.filter_options.themes || {});
	// 				setLoading(false);
	// 				setContentLoading(false);
	// 				// setErrorNotice(false);
	// 			} else {
	// 				console.error('Failed to fetch sites:', response);
	// 				// setErrorNotice(true);
	// 			}
	// 		} catch (e) {
	// 			// Handle error
	// 			console.error('Failed to fetch sites:', e);
	// 		}
	// 	};

	// 	fetchSites();
	// }, [theme]);

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

			if (hasData) {
				setIsDataEmpty(false);
			} else {
				setIsDataEmpty(true); // Indicate that data is empty
			}

			setLoading(false);
			setContentLoading(false);
		}, 100);
		return () => clearTimeout(timer);
	}, [demos, pagebuilders, categories, themes]);

	useEffect(() => {
		setContentLoading(true);
	}, [theme, pagebuilder, plan, search]);

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

	if (isDataEmpty) {
		return (
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
				<span className="font-medium">No demos available.</span>
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
						<Lottie animationData={loader} loop={true} autoplay={true} className="h-40" />
					) : (
						<Content categories={categories} demos={demos} />
					)}
				</>
			)}
		</>
	);
};

export default Home;
