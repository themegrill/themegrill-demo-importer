// Types for the "Build with AI" flow. Shapes mirror the frontend <-> backend
// API contract (AI_STARTER_SITES_KB.md §6) so the mock and live clients are
// interchangeable.

export type AiTone = 'friendly' | 'professional' | 'bold' | 'elegant' | 'playful';

export type AiPageSlug = 'home' | 'about' | 'services' | 'contact' | 'blog';

export type GenerateRequest = {
	brandName: string;
	description: string;
	niche: string; // 'auto' or a niche slug.
	tone: AiTone;
	language: string;
	pages: AiPageSlug[];
	// The design the user picked, and the brief read for the designs list.
	demoSlug?: string;
	briefId?: string;
	// #rrggbb. The other colors follow it; unset keeps the design's own colors.
	primaryColor?: string;
};

export type DemoSummary = {
	slug: string;
	name: string;
	thumbnail: string;
	primaryColor?: string; // The design's own primary color.
};

// How close a design is to the business, for grouping the designs list.
export type DesignMatch = 'best' | 'related' | 'other';

export type DesignOption = DemoSummary & { demoUrl: string; premium: boolean; match: DesignMatch };

export type DesignsResponse = { briefId: string; designs: DesignOption[] };

export type BrandPalette = {
	primary: string;
	secondary: string;
	accent: string;
	text: string;
	background: string;
};

export type BrandKit = {
	siteTitle: string;
	tagline: string;
	palette: BrandPalette;
	fonts: { heading: string; body: string };
};

export type ImageSlot = {
	url: string;
	alt: string;
	credit: string;
	alternatives: string[];
	overlays?: Record<string, string>; // Photo URL => overlay the importer adds over it.
};

// A pictogram swapped for a BlockArt icon: the icon block's name, colors and sizes.
export type IconSlot = {
	icon: string; // BlockArt icon name, e.g. "paw-solid".
	color: string;
	size: number; // px
	shape?: { color: string; radius: number; padding: number }; // radius in %, padding in px
};

export type SlotValue = string | ImageSlot | IconSlot;

export type Section = {
	id: string;
	type: string;
	slots: Record<string, SlotValue>;
	// Optional extras from the live backend: the section's first heading, and
	// what each slot is (h1-h6, paragraph, button, image...).
	title?: string;
	kinds?: Record<string, string>;
	fixed?: string[]; // Text kept as designed (stats, prices, contact details).
	index?: number; // Position on the demo page, for putting restored sections back in order.
};

// A section group the backend left out because it doesn't fit the business.
// `sections` hold template copy until restored (restoring regenerates it).
export type RemovedGroup = {
	groupId: string;
	title: string;
	reason: string;
	sections: Section[];
	restored?: boolean;
};

export type GeneratedPage = {
	slug: string;
	title: string;
	sections: Section[];
	removed?: RemovedGroup[];
};

export type GenerationPackage = {
	id: string;
	demo: DemoSummary;
	alternatives: DemoSummary[];
	brand: BrandKit;
	pages: GeneratedPage[];
	importPackage: Record<string, unknown>;
	notes?: string[]; // e.g. a requested page the design doesn't have.
	products?: GeneratedProduct[]; // The demo's shop products, rewritten for the business.
};

export type GeneratedProduct = {
	demoId: number; // Product ID in the demo; the import finds the imported product by it.
	name: string;
	description: string;
	image?: ImageSlot;
};

export type RegenerateSectionRequest = {
	generationId: string;
	page: string;
	sectionId: string;
	instruction?: string;
};

export type SwitchDemoRequest = {
	generationId: string;
	demoSlug: string;
	primaryColor?: string;
};

export type GenerationStep =
	| 'understanding_brand'
	| 'picking_design'
	| 'writing_copy'
	| 'finding_images'
	| 'assembling';

export type GenerationProgress = {
	step: GenerationStep;
	progress: number; // 0..1
};

export type AiErrorCode =
	| 'RATE_LIMITED'
	| 'INVALID_INPUT'
	| 'GENERATION_FAILED'
	| 'UNAUTHORIZED'
	| 'NETWORK_ERROR'
	| 'INVALID_RESPONSE'
	| 'CANCELLED';

export type AiErrorPayload = {
	error: {
		code: AiErrorCode;
		message: string;
		retryAfter?: number;
	};
};

export const isImageSlot = (value: SlotValue | undefined): value is ImageSlot =>
	typeof value === 'object' && value !== null && 'url' in value;

export const isIconSlot = (value: SlotValue | undefined): value is IconSlot =>
	typeof value === 'object' && value !== null && 'icon' in value;
