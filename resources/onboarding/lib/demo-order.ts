import { DemoType } from './types';

// Zakra templates always shown first, in this order. The other Zakra templates are
// shuffled once per browser tab; ColorMag and other themes keep the API order.
const PINNED_ZAKRA_SLUGS = ['agency-03', 'suffice', 'coffee-shop'];
const ROTATED_THEME = 'zakra';
const SEED_KEY = 'tgdm_demo_order_seed';

let fallbackSeed: number | null = null;

// One seed per tab: same order across reloads and filters, a new one in a new tab.
const getSessionSeed = (): number => {
	try {
		const stored = Number(window.sessionStorage.getItem(SEED_KEY));
		if (stored) {
			return stored;
		}
		const seed = Math.floor(Math.random() * 2 ** 31) + 1;
		window.sessionStorage.setItem(SEED_KEY, String(seed));
		return seed;
	} catch {
		if (!fallbackSeed) {
			fallbackSeed = Math.floor(Math.random() * 2 ** 31) + 1;
		}
		return fallbackSeed;
	}
};

// mulberry32: small seeded PRNG so the shuffle is repeatable for a given seed.
const createRandom = (seed: number) => {
	let state = seed;
	return () => {
		state = (state + 0x6d2b79f5) | 0;
		let t = Math.imul(state ^ (state >>> 15), 1 | state);
		t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
		return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
	};
};

const isZakra = (demo: DemoType) => demo.theme_slug === ROTATED_THEME;

export const orderDemos = (demos: DemoType[]): DemoType[] => {
	const pinned = PINNED_ZAKRA_SLUGS.map((slug) =>
		demos.find((demo) => isZakra(demo) && demo.slug === slug),
	).filter((demo): demo is DemoType => Boolean(demo));
	const rest = demos.filter((demo) => !pinned.includes(demo));

	const rotated = rest.filter(isZakra);
	const random = createRandom(getSessionSeed());
	for (let i = rotated.length - 1; i > 0; i--) {
		const j = Math.floor(random() * (i + 1));
		const current = rotated[i];
		const other = rotated[j];
		if (current && other) {
			rotated[i] = other;
			rotated[j] = current;
		}
	}

	// Shuffled Zakra templates fill the Zakra slots, so other themes keep their positions.
	let next = 0;
	return [...pinned, ...rest.map((demo) => (isZakra(demo) ? (rotated[next++] ?? demo) : demo))];
};
