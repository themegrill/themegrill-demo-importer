import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { HashRouter, Route, Routes } from 'react-router-dom';
import Home from './Home';
import { LocalizedDataProvider } from './LocalizedDataContext';
import Import from './components/features/sites/components/detail/import/Import';
import { queryClient } from './lib/query-client';

const App = () => {
	return (
		<QueryClientProvider client={queryClient}>
			<HashRouter>
				<LocalizedDataProvider>
					<Routes>
						<Route path="/" element={<Home />} />
						<Route path="/import-detail/:demo_theme/:slug" element={<Import />} />
					</Routes>
				</LocalizedDataProvider>
			</HashRouter>
			<ReactQueryDevtools initialIsOpen={false} />
		</QueryClientProvider>
	);
};

export default App;
