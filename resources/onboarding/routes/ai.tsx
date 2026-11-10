import { createFileRoute, redirect } from '@tanstack/react-router';
import AiFlow from '../ai/AiFlow';
import { getAiConfig } from '../ai/config';

export const Route = createFileRoute('/ai')({
	// Build with AI only supports Zakra for now; elsewhere the tab is hidden.
	beforeLoad: () => {
		if (!getAiConfig().enabled) {
			throw redirect({
				to: '/',
				search: { search: undefined, builder: undefined, category: undefined },
			});
		}
	},
	component: AiFlow,
});
