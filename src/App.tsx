import React from 'react';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Import from './components/import/Import';
import { DemoContextProvider } from './context';
import Home from './Home';
import { LocalizedDataProvider } from './LocalizedDataContext';

const App = () => {
	// const data = __TDI_DASHBOARD__.data;
	// const initialTheme = __TDI_DASHBOARD__.theme;

	// if (!__TDI_DASHBOARD__ || !data) {
	// 	return (
	// 		<div className="p-4">
	// 			<p>Error: Dashboard data is not available</p>
	// 		</div>
	// 	);
	// }
	// const [theme, setTheme] = useState<string>(__TDI_DASHBOARD__.theme || 'all');

	// const searchTerms = useMemo<SearchResultType[]>(() => {
	// 	if ('all' === initialTheme) {
	// 		let idx = 1;
	// 		return Object.entries(data || {}).flatMap(([key, value]) => {
	// 			return Object.entries(value)
	// 				.filter(([key2]) => key2 === 'demos')
	// 				.flatMap(([key2, value2]) => {
	// 					return Object.entries(value2).map(([key3, value3]) => {
	// 						return {
	// 							id: idx++,
	// 							slug: key3,
	// 							theme: key,
	// 							name: value3.name,
	// 							description: value3?.description ?? '',
	// 							pagebuilders: value3?.pagebuilders ?? [],
	// 							categories: value3?.categories ?? [],
	// 							...value3,
	// 						};
	// 					});
	// 				});
	// 		});
	// 	} else {
	// 		return Object.entries(data?.[theme]?.demos || {}).map(([key, d], idx) => {
	// 			return {
	// 				theme: theme,
	// 				...d,
	// 			};
	// 		});
	// 	}
	// }, [data, initialTheme, theme]);

	// const contentProps = {
	// 	data,
	// 	initialTheme,
	// 	theme,
	// 	setTheme,
	// 	searchTerms,
	// };

	return (
		<HashRouter>
			<LocalizedDataProvider>
				<DemoContextProvider>
					<Routes>
						<Route path="/" element={<Home />} />
						<Route path="/import-detail/:slug" element={<Import />} />
					</Routes>
				</DemoContextProvider>
			</LocalizedDataProvider>
		</HashRouter>
	);
};

export default App;
