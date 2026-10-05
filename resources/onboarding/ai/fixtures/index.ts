import { PAGES } from '../constants';
import { DemoSummary, GenerateRequest, GenerationPackage, Section, SlotValue } from '../types';
import { agency } from './agency';
import { bakery } from './bakery';
import { fitness } from './fitness';
import { NicheFixture } from './helpers';

export const FIXTURES: NicheFixture[] = [bakery, agency, fitness];

/**
 * Mimics the backend's niche detection: an explicit niche wins, otherwise the
 * fixture with the most keyword hits in the description, defaulting to agency.
 */
export const pickFixture = (request: GenerateRequest): NicheFixture => {
	const explicit = FIXTURES.find((f) => f.niche === request.niche);
	if (explicit) {
		return explicit;
	}

	const text = `${request.brandName} ${request.description}`.toLowerCase();
	const scored = FIXTURES.map((fixture) => ({
		fixture,
		// Match at word starts so 'consult' hits 'consulting' but 'art' misses 'start'.
		score: fixture.keywords.filter((k) => new RegExp(`\\b${k}`).test(text)).length,
	})).sort((a, b) => b.score - a.score);

	return scored[0] && scored[0].score > 0 ? scored[0].fixture : agency;
};

export const findFixtureByDemo = (demoSlug: string): NicheFixture | undefined =>
	FIXTURES.find(
		(f) => f.demo.slug === demoSlug || f.alternatives.some((a) => a.slug === demoSlug),
	);

const fillBrand = (value: SlotValue, brandName: string): SlotValue =>
	typeof value === 'string' ? value.split('{brand}').join(brandName) : value;

export const fillSection = (section: Section, brandName: string): Section => ({
	...section,
	slots: Object.fromEntries(
		Object.entries(section.slots).map(([key, value]) => [key, fillBrand(value, brandName)]),
	),
});

export const buildPackage = (
	fixture: NicheFixture,
	request: GenerateRequest,
	demo: DemoSummary = fixture.demo,
): GenerationPackage => {
	const allDemos = [fixture.demo, ...fixture.alternatives];
	const requested = new Set(request.pages.length ? request.pages : ['home']);

	return {
		id: `gen_mock_${Date.now().toString(36)}`,
		demo,
		alternatives: allDemos.filter((d) => d.slug !== demo.slug).slice(0, 3),
		brand: {
			...fixture.brand,
			siteTitle: request.brandName,
			palette: { ...fixture.brand.palette },
			fonts: { ...fixture.brand.fonts },
		},
		pages: PAGES.filter((p) => requested.has(p.value)).map((p) => ({
			slug: p.value,
			title: fixture.pages[p.value].title,
			sections: fixture.pages[p.value].sections.map((s) => fillSection(s, request.brandName)),
		})),
		importPackage: { mock: true, demoSlug: demo.slug },
	};
};
