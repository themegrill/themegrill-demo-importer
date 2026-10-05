import { createFileRoute } from '@tanstack/react-router';
import AiFlow from '../ai/AiFlow';

export const Route = createFileRoute('/ai')({
	component: AiFlow,
});
