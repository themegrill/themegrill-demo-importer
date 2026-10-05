// Live client. Calls this site's REST proxy (which forwards to the AI backend)
// using the request/response contract in AI_STARTER_SITES_KB.md §6.
import { __ } from '@wordpress/i18n';
import { getAiConfig } from '../config';
import { AiErrorPayload, GenerationPackage, GenerationProgress } from '../types';
import { AiApiError, toAiApiError } from './errors';
import { AiClient } from './types';

const unexpected = () =>
	new AiApiError('INVALID_RESPONSE', __('The AI service returned an unexpected response.', 'themegrill-demo-importer'));

const request = async (route: string, body: unknown, signal?: AbortSignal, accept = 'application/json') => {
	const { restUrl, nonce } = getAiConfig();
	try {
		return await fetch(`${restUrl}${route}`, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', Accept: accept, 'X-WP-Nonce': nonce },
			body: JSON.stringify(body),
			signal,
		});
	} catch (error) {
		throw toAiApiError(error);
	}
};

const errorFrom = (status: number, json: unknown) => {
	const error = (json as Partial<AiErrorPayload>)?.error;
	// WordPress's own REST errors (expired nonce, missing capability).
	const wpError = json as { code?: string; message?: string } | null;
	if (!error && wpError?.code && (status === 401 || status === 403)) {
		return new AiApiError(
			'UNAUTHORIZED',
			__('Your session has expired or you are not allowed to do this. Reload the page and try again.', 'themegrill-demo-importer'),
		);
	}
	return new AiApiError(
		error?.code ?? (status === 429 ? 'RATE_LIMITED' : 'GENERATION_FAILED'),
		error?.message ?? __('Something went wrong while generating your site.', 'themegrill-demo-importer'),
		error?.retryAfter,
	);
};

const post = async <T>(route: string, body: unknown, signal?: AbortSignal): Promise<T> => {
	const response = await request(route, body, signal);

	let json: unknown;
	try {
		json = await response.json();
	} catch {
		throw unexpected();
	}

	if (!response.ok) {
		throw errorFrom(response.status, json);
	}

	return json as T;
};

type StreamEvent =
	| ({ type: 'progress' } & GenerationProgress)
	| { type: 'result'; package: GenerationPackage }
	| ({ type: 'error' } & AiErrorPayload);

/**
 * POST that reads an NDJSON stream: progress events, then one result or
 * error event. Errors before the stream starts (auth, validation, rate
 * limits) arrive as a normal JSON error response.
 */
const postStream = async (
	route: string,
	body: unknown,
	onProgress?: (progress: GenerationProgress) => void,
	signal?: AbortSignal,
): Promise<GenerationPackage> => {
	const response = await request(route, body, signal, 'application/x-ndjson');

	if (!response.ok) {
		throw errorFrom(response.status, await response.json().catch(() => null));
	}
	// A backend without streaming support answers with the plain package.
	if (!response.headers.get('content-type')?.includes('ndjson') || !response.body) {
		return response.json().catch(() => {
			throw unexpected();
		});
	}

	const reader = response.body.getReader();
	const decoder = new TextDecoder();
	let buffer = '';

	const handle = (line: string): GenerationPackage | undefined => {
		if (!line.trim()) return undefined;
		let event: StreamEvent;
		try {
			event = JSON.parse(line) as StreamEvent;
		} catch {
			throw unexpected();
		}
		if (event.type === 'progress') onProgress?.({ step: event.step, progress: event.progress });
		if (event.type === 'error') throw errorFrom(500, event);
		return event.type === 'result' ? event.package : undefined;
	};

	try {
		for (;;) {
			const { done, value } = await reader.read();
			buffer += decoder.decode(value, { stream: !done });
			const lines = buffer.split('\n');
			buffer = done ? '' : (lines.pop() ?? '');
			for (const line of lines) {
				const result = handle(line);
				if (result) return result;
			}
			if (done) break;
		}
	} catch (error) {
		throw error instanceof AiApiError ? error : toAiApiError(error);
	} finally {
		reader.cancel().catch(() => undefined);
	}
	// The stream ended without a result (connection dropped mid-generation).
	throw toAiApiError(new TypeError('Stream ended early'));
};

// Light shape check so a malformed body fails here, not deep inside a screen.
const assertPackage = (pkg: GenerationPackage): GenerationPackage => {
	const valid =
		typeof pkg?.id === 'string' &&
		typeof pkg.demo?.slug === 'string' &&
		Array.isArray(pkg.alternatives) &&
		typeof pkg.brand?.palette === 'object' &&
		Array.isArray(pkg.pages) &&
		pkg.pages.every((page) => Array.isArray(page?.sections));

	if (!valid) {
		throw new AiApiError(
			'INVALID_RESPONSE',
			__('The AI service returned an unexpected response.', 'themegrill-demo-importer'),
		);
	}
	return pkg;
};

export const liveClient: AiClient = {
	generate: async (body, options = {}) =>
		assertPackage(await postStream('generate', body, options.onProgress, options.signal)),
	regenerateSection: (body, options = {}) => post('regenerate-section', body, options.signal),
	switchDemo: async (body, options = {}) =>
		assertPackage(await post<GenerationPackage>('switch-demo', body, options.signal)),
};
