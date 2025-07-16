import React, { createContext, ReactNode, useContext, useEffect, useMemo, useReducer } from 'react';
import { DataObjectType, DemoDataType, SearchResultType, ThemeDataType } from './lib/types';
import { useLocalizedData } from './LocalizedDataContext';

type Action = {
	type: string;
	payload: any;
};

type DemoContextType = {
	data: DataObjectType;
	theme: string;
	pagebuilder: string;
	plan: string;
	category: string;
	search: string;
	searchResults: SearchResultType[];
	searchTerms: SearchResultType[];
	currentTheme: string;
	setTheme: (slug: string) => void;
	setPagebuilder: (slug: string) => void;
	setCategory: (slug: string) => void;
	setPlan: (slug: string) => void;
	setSearch: (value: string) => void;
	setSearchResults: (data: SearchResultType[]) => void;
	setCurrentTheme: (theme: string) => void;
	zakraProInstalled: boolean;
	zakraProActivated: boolean;
};

// type DemoContextProviderProps = {
// 	children: ReactNode;
// 	data: DataObjectType;
// 	initialTheme: string;
// };

const reducer = (state: any, action: Action) => {
	if (!action?.type) {
		return state;
	}
	return {
		...state,
		[action.type]: action.payload,
	};
};

const DemoContext = createContext<DemoContextType | null>(null);

export const DemoContextProvider = ({ children }: { children: ReactNode }) => {
	const { data: localizedData } = useLocalizedData();

	const INITIAL_STATE = {
		data: localizedData.data,
		theme: localizedData.theme || 'all',
		pagebuilder: 'all',
		plan: 'all',
		category: 'all',
		search: '',
		searchResults: [],
		currentTheme: localizedData.current_theme,
	};

	const [state, dispatch] = useReducer(reducer, INITIAL_STATE);

	// When localized data changes (e.g. after activating a theme), sync it into demo context
	useEffect(() => {
		dispatch({ type: 'data', payload: localizedData.data });
		dispatch({ type: 'theme', payload: localizedData.theme });
		dispatch({ type: 'currentTheme', payload: localizedData.current_theme });
	}, [localizedData]);

	// Generate search terms based on current theme
	const searchTerms = useMemo<SearchResultType[]>(() => {
		const { data, theme } = state;

		if (!data || Object.keys(data).length === 0) {
			return [];
		}

		if (theme === 'all') {
			let idx = 1;
			return Object.entries(data).flatMap(([key, value]) => {
				const themeData = value as ThemeDataType;

				if (!themeData || !themeData.demos) return [];

				return Object.entries(themeData.demos).map(([key3, value3]) => ({
					...value3,
					id: idx++,
					slug: key3,
					theme: key,
					name: value3.name || '',
					description: value3?.description ?? '',
					pagebuilders: value3?.pagebuilders ?? {},
					categories: value3?.categories ?? {},
					pro: value3?.pro ?? false,
					premium: value3?.premium ?? false,
				}));
			});
		} else {
			return Object.entries(data[state.theme]?.demos || {}).map(([key, d], idx) => {
				const demo = d as DemoDataType;

				return {
					...demo,
					id: idx + 1,
					slug: key,
					theme: state.theme,
					name: demo.name || '',
					description: demo?.description ?? '',
					pagebuilders: demo.pagebuilders ?? {},
					categories: demo.categories ?? {},
					pro: demo.pro ?? false,
					premium: demo.premium ?? false,
				};
			});
		}
	}, [state.data, state.theme]);

	const setTheme = (theme: string) => {
		dispatch({
			type: 'theme',
			payload: theme,
		});
	};

	const setPagebuilder = (pagebuilder: string) => {
		dispatch({
			type: 'pagebuilder',
			payload: pagebuilder,
		});
	};

	const setPlan = (plan: string) => {
		dispatch({
			type: 'plan',
			payload: plan,
		});
	};

	const setCategory = (category: string) => {
		dispatch({
			type: 'category',
			payload: category,
		});
	};

	const setSearch = (search: string) => {
		dispatch({
			type: 'search',
			payload: search,
		});
	};

	const setSearchResults = (searchResults: SearchResultType[]) => {
		dispatch({
			type: 'searchResults',
			payload: searchResults,
		});
	};

	const setCurrentTheme = (theme: string) => {
		dispatch({
			type: 'currentTheme',
			payload: theme,
		});
	};

	useEffect(() => {
		if (searchTerms.length > 0) {
			setSearchResults(searchTerms);
		}
	}, [searchTerms]);

	// Initialize search results with search terms
	useEffect(() => {
		if (searchTerms.length > 0) {
			setSearchResults(searchTerms);
		}
	}, [searchTerms]);

	// Handle URL hash changes
	useEffect(() => {
		const updateFromHash = () => {
			try {
				const hash = window.location.hash.slice(1); // remove "#"
				const [path, queryString] = hash.split('?');

				if (queryString) {
					const params = new URLSearchParams(queryString);
					const tab = params.get('tab') || state.theme;
					const pb = params.get('pagebuilder') || 'all';
					const cat = params.get('category') || 'all';
					const plan = params.get('plan') || 'all';
					const search = params.get('search') || '';

					setTheme(tab);
					setPagebuilder(pb);
					setCategory(cat);
					setPlan(plan);
					setSearch(search);
				}
			} catch (error) {
				console.error('Error parsing URL hash:', error);
			}
		};

		updateFromHash();
		window.addEventListener('hashchange', updateFromHash);

		return () => window.removeEventListener('hashchange', updateFromHash);
	}, [state.theme]);

	return (
		<DemoContext.Provider
			value={{
				...state,
				searchTerms,
				setTheme,
				setPagebuilder,
				setPlan,
				setCategory,
				setSearch,
				setSearchResults,
				setCurrentTheme,
				zakraProInstalled: localizedData.zakra_pro_installed,
				zakraProActivated: localizedData.zakra_pro_activated,
			}}
		>
			{children}
		</DemoContext.Provider>
	);
};

export const useDemoContext = () => {
	const context = useContext(DemoContext);
	if (!context) {
		throw new Error('useDemoContext must be used within a DemoProvider');
	}
	return context;
};
