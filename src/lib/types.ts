// export const __TDI_DASHBOARD__: {
// 	theme: string;
// 	data: DataObjectType;
// } = (window as any).__TDI_DASHBOARD__;

export const __TDI_DASHBOARD__: TDIDashboardType = (window as any).__TDI_DASHBOARD__;

export type TDIDashboardType = {
	theme: string;
	data: DataObjectType;
	siteUrl: string;
	installed_themes: string[];
	current_theme: string;
	zakra_pro_installed: boolean;
	zakra_pro_activated: boolean;
};

export type DataObjectType = Record<string, ThemeDataType>;

export type ThemeDataType = {
	slug: string;
	name: string;
	description?: string;
	pro: boolean;
	premium: boolean;
	categories: Record<string, string>;
	pagebuilders?: Record<string, string>;
	demos: Record<string, DemoDataType>;
};

export type DemoDataType = {
	id: number;
	slug: string;
	name: string;
	description: string;
	url: string;
	image: string;
	pro: boolean;
	premium: boolean;
	new?: boolean;
	plugins: Array<string>;
	pagebuilders?: Record<string, string>;
	categories: Record<string, string>;
	[key: string]: any;
};

export type SearchResultType = {
	id: number;
	slug: string;
	name: string;
	description: string;
	url: string;
	image: string;
	pro: boolean;
	premium: boolean;
	new?: boolean;
	pagebuilders: Record<string, string>;
	categories: Record<string, string>;
	theme: string;
	[k: string]: any;
};

export type Theme = {
	slug: string;
	name: string;
};

export type PagebuilderCategory = {
	slug: string;
	value: string;
	count?: number;
};

export type Page = {
	ID: number;
	post_name: string;
	post_title: string;
	content: string;
	screenshot: string;
};

export type PageWithSelection = Page & {
	isSelected: boolean;
};
