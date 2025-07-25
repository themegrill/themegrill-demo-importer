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

// Individual theme item type
export type ThemeItem = {
	id: string;
	theme_slug: string;
	theme_name: string;
	slug: string;
	name: string;
	description: string;
	image: string;
	pro: boolean;
	premium: boolean;
	new: boolean;
	categories: Record<string, string>;
	pagebuilders: Record<string, string>;
	related_sites?: Record<string, string>;
};

export type FilterItem = Record<string, string>;
export type FilterCounts = {
	categories: FilterItem;
	pagebuilders: FilterItem;
	themes: Record<string, string>;
};

// Main response type
export type ThemeDataResponse = {
	success: boolean;
	data: ThemeItem[];
	filter_options: FilterCounts;
};

// Page data structure
export type PageData = {
	ID: number;
	post_title: string;
	post_name: string;
	screenshot: string;
	content: string;
};

// Core options structure
export type CoreOptions = {
	blogname: string;
	page_on_front: string;
	page_for_posts: string;
};

// Customizer data update structure
export type CustomizerDataUpdate = {
	nav_menu_locations: {
		primary: string;
		header: number;
		footer: string;
	};
};

// Individual demo type
export type Demo = {
	id: number;
	theme_slug: string;
	theme_name: string;
	slug: string;
	name: string;
	description: string;
	image: string;
	pro: boolean;
	premium: boolean;
	new: boolean;
	categories: Record<string, string>;
	pagebuilders: Record<string, string>;
	url: string;
	plugins: string[];
	content: string;
	widget: string;
	customizer: string;
	customizer_data_update: CustomizerDataUpdate;
	pages: PageData[];
	core_options: CoreOptions;
	pagebuilder_data: Record<string, Record<string, any>>;
};
