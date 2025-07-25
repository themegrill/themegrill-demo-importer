import React, { useState } from 'react';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Import from './components/import/Import';
import Home from './Home';
import { __TDI_DASHBOARD__, TDIDashboardType } from './lib/types';

const App = () => {
	const [localizedData, setLocalizedData] = useState<TDIDashboardType>(__TDI_DASHBOARD__);
	const allProps = {
		localizedData,
		setLocalizedData,
	};

	return (
		<HashRouter>
			<Routes>
				<Route path="/" element={<Home {...allProps} />} />
				<Route path="/import-detail/:slug/:pagebuilder" element={<Import {...allProps} />} />
			</Routes>
		</HashRouter>
	);
};

export default App;
