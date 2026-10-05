// Mock AI client with simulated latency. Put one of these tags in the
// description (or a regenerate instruction) to force an error state:
//   #ratelimit #invalid #fail #unauthorized #network #badresponse
import { __ } from '@wordpress/i18n';
import { buildPackage, fillSection, findFixtureByDemo, pickFixture } from '../fixtures';
import { GenerateRequest, GenerationStep, Section, SlotValue } from '../types';
import { AiApiError } from './errors';
import { AiClient, RequestOptions } from './types';

const sleep = (ms: number, signal?: AbortSignal) =>
	new Promise<void>((resolve, reject) => {
		if (signal?.aborted) {
			reject(new DOMException('Aborted', 'AbortError'));
			return;
		}
		const timer = setTimeout(resolve, ms);
		signal?.addEventListener(
			'abort',
			() => {
				clearTimeout(timer);
				reject(new DOMException('Aborted', 'AbortError'));
			},
			{ once: true },
		);
	});

const throwForTag = (text: string, stage: 'start' | 'midway') => {
	const has = (tag: string) => text.includes(tag);

	if (stage === 'start') {
		if (has('#ratelimit')) {
			throw new AiApiError(
				'RATE_LIMITED',
				__('You’ve reached today’s AI generation limit.', 'themegrill-demo-importer'),
				3600,
			);
		}
		if (has('#invalid')) {
			throw new AiApiError(
				'INVALID_INPUT',
				__('The description could not be understood. Try adding more detail.', 'themegrill-demo-importer'),
			);
		}
		if (has('#unauthorized')) {
			throw new AiApiError(
				'UNAUTHORIZED',
				__('This site is not authorized to use AI generation.', 'themegrill-demo-importer'),
			);
		}
		if (has('#network')) {
			throw new TypeError('Failed to fetch');
		}
	}

	if (stage === 'midway') {
		if (has('#fail')) {
			throw new AiApiError(
				'GENERATION_FAILED',
				__('Something went wrong while generating your site.', 'themegrill-demo-importer'),
			);
		}
		if (has('#badresponse')) {
			throw new AiApiError(
				'INVALID_RESPONSE',
				__('The AI service returned an unexpected response.', 'themegrill-demo-importer'),
			);
		}
	}
};

const STEP_TIMELINE: { step: GenerationStep; ms: number }[] = [
	{ step: 'understanding_brand', ms: 700 },
	{ step: 'picking_design', ms: 600 },
	{ step: 'writing_copy', ms: 1400 },
	{ step: 'finding_images', ms: 800 },
	{ step: 'assembling', ms: 500 },
];

// Requests kept per generation id so switchDemo / regenerate can rebuild copy.
const requests = new Map<string, GenerateRequest>();
let regenerateCount = 0;

const OPENERS = ['Simply put, ', 'Here’s the idea: ', 'In short, '];

const rewrite = (value: SlotValue, instruction: string): SlotValue => {
	if (typeof value !== 'string') {
		return value;
	}
	const words = value.split(' ');
	if (/short/i.test(instruction) && words.length > 4) {
		return words.slice(0, Math.ceil(words.length * 0.6)).join(' ').replace(/[,;:]$/, '') + '.';
	}
	if (words.length > 8) {
		const opener = OPENERS[regenerateCount % OPENERS.length] ?? '';
		return opener + value.charAt(0).toLowerCase() + value.slice(1);
	}
	return value;
};

export const mockClient: AiClient = {
	async generate(request, options = {}) {
		const { signal, onProgress } = options;
		throwForTag(request.description, 'start');

		const total = STEP_TIMELINE.reduce((sum, s) => sum + s.ms, 0);
		let elapsed = 0;
		for (const { step, ms } of STEP_TIMELINE) {
			onProgress?.({ step, progress: elapsed / total });
			await sleep(ms, signal);
			elapsed += ms;
			if (step === 'writing_copy') {
				throwForTag(request.description, 'midway');
			}
		}
		onProgress?.({ step: 'assembling', progress: 1 });

		const pkg = buildPackage(pickFixture(request), request);
		requests.set(pkg.id, request);
		return pkg;
	},

	async regenerateSection({ generationId, page, sectionId, instruction = '' }, options: RequestOptions = {}) {
		await sleep(1500, options.signal);
		throwForTag(instruction, 'start');
		throwForTag(instruction, 'midway');

		const request = requests.get(generationId);
		const fixture = request && pickFixture(request);
		const source = fixture?.pages[page as keyof typeof fixture.pages]?.sections.find(
			(s) => s.id === sectionId,
		);
		if (!request || !source) {
			throw new AiApiError(
				'INVALID_INPUT',
				__('That section could not be found.', 'themegrill-demo-importer'),
			);
		}

		regenerateCount += 1;
		const filled = fillSection(source, request.brandName);
		const section: Section = {
			...filled,
			slots: Object.fromEntries(
				Object.entries(filled.slots).map(([key, value]) => [key, rewrite(value, instruction)]),
			),
		};
		return { section };
	},

	async switchDemo({ generationId, demoSlug }, options: RequestOptions = {}) {
		await sleep(3000, options.signal);

		const request = requests.get(generationId);
		const fixture = findFixtureByDemo(demoSlug);
		const demo = fixture && [fixture.demo, ...fixture.alternatives].find((d) => d.slug === demoSlug);
		if (!request || !fixture || !demo) {
			throw new AiApiError(
				'INVALID_INPUT',
				__('That design is not available.', 'themegrill-demo-importer'),
			);
		}

		const pkg = buildPackage(fixture, request, demo);
		requests.set(pkg.id, request);
		return pkg;
	},
};
