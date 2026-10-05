import { getAiConfig } from '../config';
import { liveClient } from './live';
import { mockClient } from './mock';
import { AiClient } from './types';

export const getAiClient = (): AiClient => (getAiConfig().useMock ? mockClient : liveClient);

export type { AiClient } from './types';
