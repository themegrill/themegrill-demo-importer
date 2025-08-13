import React from 'react';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Home from './Home';
import { LocalizedDataProvider } from './LocalizedDataContext';
import Import from './components/import/Import';

const App = () => {
	return (
		<HashRouter>
			<LocalizedDataProvider>
				<Routes>
					<Route path="/" element={<Home />} />
					<Route path="/import-detail/:demo_theme/:pagebuilder/:slug" element={<Import />} />
				</Routes>
			</LocalizedDataProvider>
		</HashRouter>
	);
};

export default App;
