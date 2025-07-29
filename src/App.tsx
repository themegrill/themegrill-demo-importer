import React from 'react';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Import from './components/import/Import';
import Home from './Home';
import { LocalizedDataProvider } from './LocalizedDataContext';

const App = () => {
	// const [localizedData, setLocalizedData] = useState<TDIDashboardType>(__TDI_DASHBOARD__);
	// const allProps = {
	// 	localizedData,
	// 	setLocalizedData,
	// };

	return (
		<HashRouter>
			<LocalizedDataProvider>
				<Routes>
					<Route path="/" element={<Home />} />
					<Route path="/import-detail/:slug/:pagebuilder" element={<Import />} />
				</Routes>
			</LocalizedDataProvider>
		</HashRouter>
	);
};

export default App;
