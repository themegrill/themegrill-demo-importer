import React from 'react';
import { HashRouter } from 'react-router-dom';
import MainContent from './controls/MainContent';
import Router from './router/Router';

const App = () => {
	return (
		<HashRouter>
			<MainContent />
			<Router />
		</HashRouter>
	);
};

export default App;
