import { AiPageSlug, BrandKit, DemoSummary, ImageSlot, Section } from '../types';

export type NicheFixture = {
	niche: string;
	// Words that make auto-detect pick this fixture from a description.
	keywords: string[];
	demo: DemoSummary;
	alternatives: DemoSummary[];
	brand: Omit<BrandKit, 'siteTitle'>;
	// Copy may contain `{brand}`, replaced with the brand name on generation.
	pages: Record<AiPageSlug, { title: string; sections: Section[] }>;
};

// Mock images come from Lorem Picsum so they always resolve. The live backend
// returns Unsplash/Pexels results in the same shape.
const picsum = (seed: string, w = 1200, h = 800) => `https://picsum.photos/seed/${seed}/${w}/${h}`;

export const image = (seed: string, alt: string): ImageSlot => ({
	url: picsum(seed),
	alt,
	credit: 'Photo via Lorem Picsum (mock)',
	alternatives: [1, 2, 3, 4].map((n) => picsum(`${seed}-alt${n}`)),
});

export const thumbnail = (seed: string) => picsum(seed, 600, 714);
