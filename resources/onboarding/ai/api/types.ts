import {
	DesignsResponse,
	GenerateRequest,
	GenerationPackage,
	GenerationProgress,
	RegenerateSectionRequest,
	Section,
	SwitchDemoRequest,
} from '../types';

export type RequestOptions = {
	signal?: AbortSignal;
};

export type GenerateOptions = RequestOptions & {
	onProgress?: (progress: GenerationProgress) => void;
};

export interface AiClient {
	designs(request: GenerateRequest, options?: RequestOptions): Promise<DesignsResponse>;
	generate(request: GenerateRequest, options?: GenerateOptions): Promise<GenerationPackage>;
	regenerateSection(
		request: RegenerateSectionRequest,
		options?: RequestOptions,
	): Promise<{ section: Section }>;
	switchDemo(request: SwitchDemoRequest, options?: RequestOptions): Promise<GenerationPackage>;
}
