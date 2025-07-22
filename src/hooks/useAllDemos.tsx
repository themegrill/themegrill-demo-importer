// hooks/useAllDemos.ts
import { useMemo } from 'react';
import { DemoDataType, TDIDashboardType, ThemeDataType } from '../lib/types';

export const useAllDemos = (data: TDIDashboardType, theme: string) => {
	const themeBasedData = data?.data || {};

	return useMemo(() => {
		if (!themeBasedData || Object.keys(themeBasedData).length === 0) {
			return [];
		}

		if (theme === 'all') {
			let idx = 1;
			return Object.entries(themeBasedData).flatMap(([key, value]) => {
				const themeData = value as ThemeDataType;
				if (!themeData || !themeData.demos) return [];

				return Object.entries(themeData.demos).map(([key3, value3]) => ({
					...value3,
					id: idx++,
					slug: key3,
					theme: key,
					name: value3.name || '',
					description: value3?.description ?? '',
					pagebuilders: value3?.pagebuilders ?? {},
					categories: value3?.categories ?? {},
					pro: value3?.pro ?? false,
					premium: value3?.premium ?? false,
				}));
			});
		} else {
			return Object.entries(themeBasedData[theme]?.demos || {}).map(([key, d], idx) => {
				const demo = d as DemoDataType;

				return {
					...demo,
					id: idx + 1,
					slug: key,
					theme: theme,
					name: demo.name || '',
					description: demo?.description ?? '',
					pagebuilders: demo.pagebuilders ?? {},
					categories: demo.categories ?? {},
					pro: demo.pro ?? false,
					premium: demo.premium ?? false,
				};
			});
		}
	}, [data, theme]);
};
