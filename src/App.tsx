import React, { useState } from 'react';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Import from './components/import/Import';
import Home from './Home';
import { __TDI_DASHBOARD__, TDIDashboardType } from './lib/types';

const App = () => {
	const [data, setData] = useState<TDIDashboardType>(__TDI_DASHBOARD__);

	return (
		<HashRouter>
			<Routes>
				<Route path="/" element={<Home data={data} setData={setData} />} />
				<Route
					path="/import-detail/:slug/:pagebuilder"
					element={<Import data={data} setData={setData} />}
				/>
			</Routes>
		</HashRouter>
	);
};

export default App;
