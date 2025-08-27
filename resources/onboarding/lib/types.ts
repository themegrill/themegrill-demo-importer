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

export type PagebuilderCategory = {
	id: string;
	value: string;
};

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
};

export type PluginItem = {
	plugin: string;
	name: string;
	description: string;
	isSelected: boolean;
	isMandatory?: boolean;
};
