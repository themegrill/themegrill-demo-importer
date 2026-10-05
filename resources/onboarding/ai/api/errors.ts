import { __ } from '@wordpress/i18n';
import { AiErrorCode } from '../types';

export class AiApiError extends Error {
	code: AiErrorCode;
	retryAfter?: number;

	constructor(code: AiErrorCode, message: string, retryAfter?: number) {
		super(message);
		this.name = 'AiApiError';
		this.code = code;
		this.retryAfter = retryAfter;
	}
}

export const toAiApiError = (error: unknown): AiApiError => {
	if (error instanceof AiApiError) {
		return error;
	}
	if (error instanceof DOMException && error.name === 'AbortError') {
		return new AiApiError('CANCELLED', __('Generation was cancelled.', 'themegrill-demo-importer'));
	}
	// The friendly message hides the cause; keep it in the console for debugging
	// (CORS, mixed content, server down, dropped stream...).
	console.error('[Build with AI] Request to the AI service failed:', error);
	return new AiApiError(
		'NETWORK_ERROR',
		__('Could not reach the AI service. Check your connection and try again.', 'themegrill-demo-importer'),
	);
};
