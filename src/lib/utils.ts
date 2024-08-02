import clsx, { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export const cn = (...inputs: ClassValue[]) => {
	return twMerge(clsx(inputs));
};

export const __TDI_DASHBOARD__: {
	data: {
		categories: Record<string, string>;
		pagebuilders: Record<string, string>;
		slug: string;
		version: string;
		name: string;
		homepage: string;
		demos: Record<
			string,
			Record<
				string,
				{
					title: string;
					pagebuilder: Array<string>;
					category: Array<string>;
					[k: string]: any;
				}
			>
		>;
	};
} = (window as any).__TDI_DASHBOARD__;
