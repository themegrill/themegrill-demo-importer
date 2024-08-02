import React from 'react';
import { Route, Routes } from 'react-router-dom';
import Import from '../controls/import/Import';

const Router = () => {
	// const { pathname } = useLocation();
	// console.log(pathname);
	return (
		<Routes>
			{/* <Route path="/" element={<El />} /> */}
			<Route path="/import-detail" element={<Import />} />
		</Routes>
	);
};

export default Router;
