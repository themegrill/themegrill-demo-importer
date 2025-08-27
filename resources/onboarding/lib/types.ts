// export const __TDI_DASHBOARD__: {
// 	theme: string;
// 	data: DataObjectType;
// } = (window as any).__TDI_DASHBOARD__;

export const __TDI_DASHBOARD__: TDIDashboardType = (window as any).__TDI_DASHBOARD__;

export type TDIDashboardType = {
	theme: string;
	data: DataObjectType;
	error_msg?: string;
	siteUrl: string;
	installed_themes: string[];
	current_theme: string;
	zakra_pro_installed: boolean;
	zakra_pro_activated: boolean;
};

export type DataObjectType = {
	demos: DemoType[];
	builders: PagebuilderCategory[];
	categories: PagebuilderCategory[];
};

export type DemoType = {
	id: number;
	lastUpdated: string;
	url: string;
	keywords: string[];
	categories: string[];
	title: string;
	description: string;
	pagebuilder: string;
	slug: string;
	theme_slug: string;
	previewImage: string;
	new: boolean;
};

// export type DataObjectType = Record<string, ThemeDataType>;

// export type ThemeDataType = {
// 	slug: string;
// 	name: string;
// 	categories: Record<string, string>;
// 	pagebuilders?: Record<string, string>;
// 	demos: ThemeItem[];
// };

// export type DemoDataType = {
// 	id: number;
// 	slug: string;
// 	name: string;
// 	description: string;
// 	url: string;
// 	image: string;
// 	pro: boolean;
// 	premium: boolean;
// 	new?: boolean;
// 	plugins: Array<string>;
// 	pagebuilders?: Record<string, string>;
// 	categories: Record<string, string>;
// 	[key: string]: any;
// };

// export type SearchResultType = {
// 	id: number;
// 	slug: string;
// 	name: string;
// 	description: string;
// 	url: string;
// 	image: string;
// 	pro: boolean;
// 	premium: boolean;
// 	new?: boolean;
// 	pagebuilders: Record<string, string>;
// 	categories: Record<string, string>;
// 	theme: string;
// 	[k: string]: any;
// };

// export type Theme = {
// 	slug: string;
// 	name: string;
// };

export type PagebuilderCategory = {
	id: string;
	value: string;
};

// export type Page = {
// 	ID: number;
// 	post_name: string;
// 	post_title: string;
// 	content: string;
// 	screenshot: string;
// };

// export type PageType = {
// 	ID: number;
// 	post_name: string;
// 	post_title: string;
// 	content: string;
// 	screenshot: string;
// };

// export type ThemeItem = {
// 	id: string;
// 	theme_slug: string;
// 	theme_name: string;
// 	slug: string;
// 	name: string;
// 	description: string;
// 	image: string;
// 	pro: boolean;
// 	premium: boolean;
// 	new: boolean;
// 	categories: Record<string, string>;
// 	pagebuilders: Record<string, string>;
// 	related_sites?: Record<string, string>;
// };

// export type FilterItem = Record<string, string>;
// export type FilterOptions = {
// 	categories: FilterItem;
// 	pagebuilders: FilterItem;
// 	themes: Record<string, string>;
// };

// export type ThemeDataResponse = {
// 	success: boolean;
// 	data: ThemeItem[];
// 	filter_options: FilterOptions;
// };

export type PageType = {
	id: number;
	title: string;
	slug: string;
	screenshot: string;
	content: string;
};

export type PageWithSelection = PageType & {
	isSelected: boolean;
};

export type CoreOptions = {
	blogname: string;
	page_on_front: string;
	page_for_posts: string;
};

export type CustomizerDataUpdate = {
	nav_menu_locations: {
		primary: string;
		header: number;
		footer: string;
	};
};

export type Demo = {
	slug: string;
	title: string;
	themeMods: Record<string, any>;
	widgets: Record<string, any>;
	content: string;
	show_on_front: string;
	page_on_front: string;
	page_for_posts: string;
	url: string;
	premium: boolean;
	theme_slug: string;
	plugins: Record<string, { name: string; description: string }>;
	pages: PageType[];
	// id: number;
	// theme_slug: string;
	// theme_name: string;
	// slug: string;
	// name: string;
	// description: string;
	// image: string;
	// pro: boolean;
	// premium: boolean;
	// new: boolean;
	// categories: Record<string, string>;
	// pagebuilders: Record<string, string>;
	// url: string;
	// plugins: string[];
	// content: string;
	// widget: string;
	// customizer: string;
	// customizer_data_update: CustomizerDataUpdate;
	// pages: PageData[];
	// core_options: CoreOptions;
	// pagebuilder_data: Record<string, Record<string, any>>;
};

// export type PluginItem = Record<
// 	string,
// 	{
// 		name: string;
// 		description: string;
// 		isSelected: boolean;
// 		isMandatory?: boolean;
// 	}
// >;
export type PluginItem = {
	plugin: string;
	name: string;
	description: string;
	isSelected: boolean;
	isMandatory?: boolean;
};
