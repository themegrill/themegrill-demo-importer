import React, { useEffect, useMemo, useState } from 'react';
import { matchPath, useLocation, useSearchParams } from 'react-router-dom';
import Content from './components/content/Content';
import Header from './components/header/Header';
import { useDemoContext } from './context';
import { Tabs } from './controls/Tabs';
import { DataObjectType, SearchResultType } from './lib/types';

type Props = {
	data: DataObjectType;
	initialTheme: string;
	// theme: string;
	// setTheme: (newTheme: string) => void;
	searchTerms: SearchResultType[];
};

const Home = ({ data, initialTheme, searchTerms }: Props) => {
	const {
		theme,
		pagebuilder,
		category,
		plan,
		searchResults,
		setPagebuilder,
		setCategory,
		setSearchResults,
	} = useDemoContext();
	const { pathname } = useLocation();
	const match = matchPath('/import-detail/:slug', pathname);
	const showTabs = !match;
	const plans = {
		all: 'All',
		free: 'Free',
		pro: 'Pro',
	};
	// const [pagebuilder, setPagebuilder] = useState<string>('all');
	// const [category, setCategory] = useState<string>('all');
	// const [plan, setPlan] = useState('all');
	// const [searchResults, setSearchResults] = useState<SearchResultType[]>([]);

	const [loading, setLoading] = useState(true);
	const [searchParams, setSearchParams] = useSearchParams();

	const themes = useMemo(() => {
		if ('all' === initialTheme) {
			const allThemeObject = {
				slug: 'all',
				name: 'All',
			};
			const allThemes = Object.entries(data || {}).map(([key, value]) => {
				return {
					slug: key,
					name: value.name,
				};
			});
			return [allThemeObject, ...allThemes];
		} else {
			const currentTheme = Object.entries(data || {})
				.filter(([key, _]) => key === theme)
				.map(([key, value]) => {
					return {
						slug: key,
						name: value.name,
					};
				});
			return currentTheme;
		}
	}, [theme, pagebuilder, category, searchResults]);

	const pagebuilders = useMemo(() => {
		if ('all' === initialTheme) {
			const result = Object.entries(data || {})
				.filter(([key, value]) => ('all' !== theme ? key === theme : true))
				.reduce((acc, [key, value]) => {
					Object.entries(value)
						.filter(([key2]) => key2 === 'pagebuilders')
						.map(([key2, value2]) => {
							Object.entries(value2).map(([key3, value3]) => {
								if (!acc.has(key3)) {
									if ('all' === key3) {
										acc.set(key3, {
											slug: key3,
											value: value3,
											count:
												searchResults
													.filter((d) => ('all' !== theme ? d.theme === key : true))
													.filter((d) =>
														'all' !== plan
															? plan === 'pro'
																? d.pro || d.premium
																: !d.pro && !d.premium
															: true,
													)
													.filter((d) =>
														searchParams.get('search')
															? d.name
																	.toLowerCase()
																	.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
															: true,
													)?.length ?? 0,
										});
									} else {
										acc.set(key3, {
											slug: key3,
											value: value3,
											count:
												searchResults
													.filter((d) => ('all' !== theme ? d.theme === key : true))
													.filter((d) => Object.keys(d.pagebuilders).some((p) => p === key3))
													.filter((d) =>
														'all' !== plan
															? plan === 'pro'
																? d.pro || d.premium
																: !d.pro && !d.premium
															: true,
													)
													.filter((d) =>
														searchParams.get('search')
															? d.name
																	.toLowerCase()
																	.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
															: true,
													)?.length ?? 0,
										});
									}
								}
							});
						});
					return acc;
				}, new Map())
				.values();
			return Array.from(result);
		} else {
			return Object.entries(data?.[theme]?.pagebuilders || {}).map(([key, val]) => ({
				slug: key,
				value: val,
				count:
					'all' === key
						? (searchResults?.length ?? 0)
						: (searchResults.filter((d) => Object.keys(d.pagebuilders).some((p) => p === key))
								?.length ?? 0),
			}));
		}
	}, [theme, pagebuilder, category, searchResults, searchParams, plan]);

	const categories = useMemo(() => {
		if ('all' === initialTheme) {
			const result = Object.entries(data || {})
				.filter(([key, value]) => ('all' !== theme ? key === theme : true))
				.reduce((acc, [key, value]) => {
					Object.entries(value)
						.filter(([key2]) => key2 === 'categories')
						.map(([key2, value2]) => {
							Object.entries(value2).map(([key3, value3]) => {
								if (!acc.has(key3)) {
									if ('all' === key3) {
										acc.set(key3, {
											slug: key3,
											value: value3,
											count:
												searchResults
													.filter((d) => ('all' !== theme ? d.theme === key : true))
													.filter((d) =>
														'all' !== pagebuilder
															? Object.keys(d.pagebuilders).some((p) => p === pagebuilder)
															: true,
													)
													.filter((d) =>
														'all' !== plan
															? plan === 'pro'
																? d.pro || d.premium
																: !d.pro && !d.premium
															: true,
													)
													.filter((d) =>
														searchParams.get('search')
															? d.name
																	.toLowerCase()
																	.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
															: true,
													)?.length ?? 0,
										});
									} else {
										acc.set(key3, {
											slug: key3,
											value: value3,
											count:
												searchResults
													.filter((d) => ('all' !== theme ? d.theme === key : true))
													.filter((d) =>
														'all' !== pagebuilder
															? Object.keys(d.pagebuilders).some((p) => p === pagebuilder)
															: true,
													)
													.filter((d) => Object.keys(d.categories).some((p) => p === key3))
													.filter((d) =>
														'all' !== plan
															? plan === 'pro'
																? d.pro || d.premium
																: !d.pro && !d.premium
															: true,
													)
													.filter((d) =>
														searchParams.get('search')
															? d.name
																	.toLowerCase()
																	.indexOf(searchParams.get('search')?.toLowerCase() || '') !== -1
															: true,
													)?.length ?? 0,
										});
									}
								}
							});
						});
					return acc;
				}, new Map())
				.values();
			return Array.from(result);
		} else {
			return Object.entries(data?.[theme]?.categories || {}).map(([key, val]) => ({
				slug: key,
				value: val,
				count:
					'all' === key
						? (searchResults?.length ?? 0)
						: (searchResults.filter((d) => Object.keys(d.categories).some((p) => p === key))
								?.length ?? 0),
			}));
		}
	}, [theme, pagebuilder, category, searchResults, searchParams, plan]);

	const currentPagebuilder = useMemo(() => {
		const { value = '', count } = pagebuilders?.filter((p) => p.slug === pagebuilder)[0] || {};
		if (value) {
			return `${value} (${count})`;
		}
		return '';
	}, [pagebuilders, pagebuilder]);

	useEffect(() => {
		setSearchResults(searchTerms);
		setLoading(false);
	}, [searchTerms]);

	useEffect(() => {
		if (!currentPagebuilder) {
			setPagebuilder('all');
		}
	}, [currentPagebuilder]);

	useEffect(() => {
		setCategory('all');
		const newParams = new URLSearchParams(searchParams);
		newParams.set('tab', theme);
		newParams.set('category', 'all');
		newParams.set('pagebuilder', pagebuilder);
		if (searchParams.has('search')) {
			newParams.set('search', searchParams.get('search') || '');
		}
		if (searchParams.has('option')) {
			newParams.set('option', searchParams.get('option') || '');
		}
		setSearchParams(newParams);
	}, [theme, currentPagebuilder]);

	useEffect(() => {
		const newParams = new URLSearchParams(searchParams);
		if (searchParams.has('search')) {
			newParams.delete('search');
		}
		if (searchParams.has('option')) {
			newParams.delete('option');
		}
		setSearchParams(newParams);
	}, []);

	return (
		<>
			{loading ? (
				<p className="px-4">Loading...</p>
			) : (
				showTabs && (
					<Tabs defaultValue={themes[0].slug}>
						<Header
							themes={themes}
							pagebuilders={pagebuilders}
							currentPagebuilder={currentPagebuilder}
							plans={plans}
							searchParams={searchParams}
							setSearchParams={setSearchParams}
						/>
						<div className="bg-[#FAFAFC]">
							<Content
								categories={categories}
								searchParams={searchParams}
								initialTheme={initialTheme}
							/>
						</div>
					</Tabs>
				)
			)}
		</>
	);
};

export default Home;
