import React, { createContext, useContext } from 'react';
import { __TDI_DASHBOARD__, TDIDashboardType } from './lib/types';

type LocalizedDataContextType = {
	data: TDIDashboardType;
	setData: React.Dispatch<React.SetStateAction<TDIDashboardType>>;
};

const LocalizedDataContext = createContext<LocalizedDataContextType | null>(null);

export const useLocalizedData = () => {
	const context = useContext(LocalizedDataContext);
	if (!context) {
		throw new Error('useLocalizedData must be used within a LocalizedDemoProvider');
	}
	return context;
};

export const LocalizedDataProvider = ({ children }: { children: React.ReactNode }) => {
	const [data, setData] = React.useState<TDIDashboardType>(__TDI_DASHBOARD__);

	return (
		<LocalizedDataContext.Provider value={{ data, setData }}>
			{children}
		</LocalizedDataContext.Provider>
	);
};
