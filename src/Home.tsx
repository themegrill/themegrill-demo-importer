import React, { useEffect, useMemo, useState } from 'react';
import { matchPath, useLocation, useSearchParams } from 'react-router-dom';
import { Tabs } from './components/Tabs';
import Content from './controls/content/Content';
import Header from './controls/header/Header';
import { DataObjectType, SearchResultType } from './lib/types';

type Props = {
	data: DataObjectType;
	initialTheme: string;
	theme: string;
	setTheme: (newTheme: string) => void;
	searchTerms: SearchResultType[];
};

const Home = ({ data, initialTheme, theme, setTheme, searchTerms }: Props) => {
	const { pathname } = useLocation();
	const match = matchPath('/import-detail/:slug', pathname);
	const showTabs = !match;
	const [pagebuilder, setPagebuilder] = useState<string>('all');
	const [category, setCategory] = useState<string>('all');
	const [loading, setLoading] = useState(true);
	const [searchResults, setSearchResults] = useState<SearchResultType[]>([]);
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
									acc.set(key3, {
										slug: key3,
										value: value3,
										count:
											searchResults
												.filter((d) => ('all' !== theme ? d.theme === key : true))
												.filter((d) => Object.keys(d.pagebuilders).some((p) => p === key3))
												?.length ?? 0,
									});
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
					searchResults.filter((d) => Object.keys(d.pagebuilders).some((p) => p === key))?.length ??
					0,
			}));
		}
	}, [theme, pagebuilder, category, searchResults]);

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
												.filter((d) => Object.keys(d.categories).some((p) => p === key3))?.length ??
											0,
									});
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
					searchResults.filter((d) => Object.keys(d.categories).some((p) => p === key))?.length ??
					0,
			}));
		}
	}, [theme, pagebuilder, category, searchResults]);

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
		setSearchParams({ tab: theme, category: 'all', pagebuilder: pagebuilder });
	}, [theme, currentPagebuilder]);

	return (
		<>
			{loading ? (
				<p className="px-4">Loading...</p>
			) : (
				showTabs && (
					<Tabs defaultValue={themes[0].slug}>
						<Header
							themes={themes}
							setTheme={setTheme}
							pagebuilders={pagebuilders}
							setPagebuilder={setPagebuilder}
							currentPagebuilder={currentPagebuilder}
						/>
						<div className="bg-[#FAFAFC]">
							<Content
								theme={theme}
								category={category}
								pagebuilder={pagebuilder}
								categories={categories}
								setCategory={setCategory}
								data={searchResults}
							/>
						</div>
					</Tabs>
				)
			)}
		</>
	);
};

export default Home;
