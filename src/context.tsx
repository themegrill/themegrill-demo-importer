import React, { createContext, ReactNode, useContext, useReducer } from 'react';
import { __TDI_DASHBOARD__, DataObjectType, SearchResultType } from './lib/types';

type Action = {
	type: string;
	payload: string | SearchResultType[];
};

type DemoContextType = {
	data: DataObjectType;
	theme: string;
	pagebuilder: string;
	plan: string;
	category: string;
	search: string;
	searchResults: SearchResultType[];
	setTheme: (slug: string) => void;
	setPagebuilder: (slug: string) => void;
	setCategory: (slug: string) => void;
	setPlan: (slug: string) => void;
	setSearch: (value: string) => void;
	setSearchResults: (data: SearchResultType[]) => void;
};

const INITIAL_STATE = {
	data: __TDI_DASHBOARD__.data,
	theme: 'all',
	pagebuilder: 'all',
	plan: 'all',
	category: 'all',
	search: '',
	searchResults: [],
};

const reducer = (state = INITIAL_STATE, action: Action) => {
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
	const [state, dispatch] = useReducer(reducer, {
		...INITIAL_STATE,
		theme: __TDI_DASHBOARD__.theme || INITIAL_STATE.theme,
	});

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

	return (
		<DemoContext.Provider
			value={{
				...state,
				setTheme,
				setPagebuilder,
				setPlan,
				setCategory,
				setSearch,
				setSearchResults,
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
