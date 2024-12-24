import React, { useMemo, useState } from 'react';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Home from './Home';
import Import from './controls/import/Import';
import { __TDI_DASHBOARD__, SearchResultType } from './lib/types';

const App = () => {
	const data = __TDI_DASHBOARD__.data;
	const initialTheme = 'all';
	const [theme, setTheme] = useState<string>('all');
	// const initialTheme = __TDI_DASHBOARD__.theme;
	// const [theme, setTheme] = useState<string>( __TDI_DASHBOARD__.theme || 'all');

	const searchTerms = useMemo<SearchResultType[]>(() => {
		if ('all' === initialTheme) {
			let idx = 1;
			return Object.entries(data || {}).flatMap(([key, value]) => {
				return Object.entries(value)
					.filter(([key2]) => key2 === 'demos')
					.flatMap(([key2, value2]) => {
						return Object.entries(value2).map(([key3, value3]) => {
							return {
								id: idx++,
								slug: key3,
								theme: key,
								name: value3.name,
								description: value3?.description ?? '',
								pagebuilders: value3?.pagebuilders ?? [],
								categories: value3?.categories ?? [],
								...value3,
							};
						});
					});
			});
		} else {
			return Object.entries(data?.[theme]?.demos || {}).map(([key, d], idx) => {
				return {
					id: idx + 1,
					name: d.name,
					description: d?.description ?? '',
					pagebuilders: d?.pagebuilders ?? [],
					categories: d?.categories ?? [],
					slug: key,
					theme: theme,
					...data,
				};
			});
		}
	}, []);

	const contentProps = {
		data,
		initialTheme,
		theme,
		setTheme,
		searchTerms,
	};

	return (
		<HashRouter>
			<Routes>
				<Route path="/" element={<Home {...contentProps} />} />
				<Route
					path="/import-detail/:slug"
					element={<Import demos={searchTerms} initialTheme={initialTheme} />}
				/>
			</Routes>
		</HashRouter>
	);
};

export default App;
