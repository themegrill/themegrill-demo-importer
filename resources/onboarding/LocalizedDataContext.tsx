import React, { createContext, useContext, useState } from 'react';
import { __TDI_DASHBOARD__, TDIDashboardType } from './lib/types';

type LocalizedDataContextType = {
	localizedData: TDIDashboardType;
	setLocalizedData: React.Dispatch<React.SetStateAction<TDIDashboardType>>;
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
	const [localizedData, setLocalizedData] = useState<TDIDashboardType>(__TDI_DASHBOARD__);

	return (
		<LocalizedDataContext.Provider value={{ localizedData, setLocalizedData }}>
			{children}
		</LocalizedDataContext.Provider>
	);
};
