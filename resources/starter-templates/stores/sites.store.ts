import { Site } from "@/starter-templates/features/sites/api/sites.types";
import { BUILDERS } from "@/starter-templates/features/sites/constants/builder";
import { create } from "zustand";

interface SiteStore {
	sites: Site[];
	builders: string[];
	categories: string[];
	sitesBySlug: Map<string, Site>;
	sitesByBuilder: Map<string, Site[]>;
	sitesByCategoryLower: Map<string, Site[]>;
	actions: {
		getSiteBySlug: (slug: string) => Site | null;
		init: (sites: Site[]) => void;
		getFilteredSites: (args: {
			query?: string;
			builder?: string;
			categories?: string[];
		}) => Site[];
	};
}

export const useSitesStore = create<SiteStore>()((set, get) => ({
	sites: [],
	builders: [],
	categories: [],
	sitesBySlug: new Map(),
	sitesByBuilder: new Map(),
	sitesByCategoryLower: new Map(),
	actions: {
		getSiteBySlug: (slug: string) => {
			return get().sitesBySlug.get(slug) ?? null;
		},

		init(sites) {
			const sitesBySlug = new Map<string, Site>();
			const sitesByBuilder = new Map<string, Site[]>();
			const sitesByCategoryLower = new Map<string, Site[]>();
			const categoriesSet = new Set<string>();
			const buildersSet = new Set<string>();

			sites.forEach((site) => {
				const normalizedSlug = site.slug.replaceAll("/", "");
				sitesBySlug.set(normalizedSlug, site);

				const builderLower = (
					site.pagebuilder || "gutenberg"
				).toLowerCase();
				buildersSet.add(builderLower);
				if (!sitesByBuilder.has(builderLower)) {
					sitesByBuilder.set(builderLower, []);
				}
				sitesByBuilder.get(builderLower)!.push(site);

				site.categories.forEach((category) => {
					const categoryLower = category.toLowerCase();
					categoriesSet.add(category);

					if (!sitesByCategoryLower.has(categoryLower)) {
						sitesByCategoryLower.set(categoryLower, []);
					}
					sitesByCategoryLower.get(categoryLower)!.push(site);
				});
			});

			set({
				sites,
				categories: Array.from(categoriesSet),
				builders: Array.from(buildersSet).sort(
					(a, b) =>
						Object.keys(BUILDERS).indexOf(a) -
						Object.keys(BUILDERS).indexOf(b)
				),
				sitesBySlug,
				sitesByBuilder,
				sitesByCategoryLower,
			});
		},

		getFilteredSites: ({ builder, categories, query }) => {
			let sites = get().sites;

			if (builder) {
				const builderSites = get().sitesByBuilder.get(
					builder.toLowerCase()
				);
				sites = builderSites || [];
			}

			if (categories?.length && !categories.includes("all")) {
				if (builder) {
					sites = sites.filter((site) =>
						site.categories.some((cat) =>
							categories.some(
								(filterCat) =>
									cat.toLowerCase() ===
									filterCat.toLowerCase()
							)
						)
					);
				} else {
					const categorySets = categories.map(
						(cat) =>
							new Set(
								get().sitesByCategoryLower.get(
									cat.toLowerCase()
								) || []
							)
					);

					if (categorySets.length > 0) {
						sites = sites.filter((site) =>
							categorySets.every((categorySet) =>
								categorySet.has(site)
							)
						);
					}
				}
			}

			if (query?.trim()) {
				const queryLower = query.trim().toLowerCase();
				sites = sites.filter((site) =>
					site.title.toLowerCase().includes(queryLower)
				);
			}

			return sites;
		},
	},
}));
