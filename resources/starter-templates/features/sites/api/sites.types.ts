export interface Site {
	id: number;
	thumbnail: string;
	lastUpdated: string;
	url: string;
	keywords: string[];
	categories: string[];
	tier: string;
	title: string;
	slug: string;
	pagebuilder: string;
	source: "zakrademos" | "themegrilldemos";
	previewImage: string;
}

export type SiteData = {
	themeMods: unknown;
	widgets: unknown;
	content: string;
	reading: unknown;
	plugins: Record<
		string,
		{ name: string; description: string; mandatory: boolean }
	>;
	url: string;
	contentXml?: string;
	premium: boolean;
	title: string;
	canImport: boolean;
	slug: string;
	show_on_front: string;
	page_on_front: number;
	page_for_posts: number;
};

export type SitesResponse = Array<Site>;

export class SiteAPIError extends Error {
	constructor(
		message: string,
		public status?: number
	) {
		super(message);
		this.name = "SiteAPIError";
	}
}
