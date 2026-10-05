// Runtime config for the AI flow, localized by PHP under `__TDI_DASHBOARD__.ai`.
// The browser calls this site's REST proxy, which holds the backend URL and
// site token server-side. Without it, the UI runs on the built-in mock.

type AiConfig = {
	enabled: boolean; // Hook for pro/billing gating.
	useMock: boolean;
	restUrl: string; // e.g. https://site.test/wp-json/tg-demo-importer/v1/ai/
	nonce: string; // wp_rest nonce for cookie-authenticated REST calls.
};

type LocalizedAiConfig = Partial<AiConfig> | undefined;

// rest_url() uses the site URL, but the admin may be open on another host or
// port (e.g. Local's fse.local:10003). A different origin drops the login
// cookies, so always call the REST API on the page's own origin.
const onPageOrigin = (url: string) => {
	try {
		const parsed = new URL(url, window.location.href);
		return window.location.origin + parsed.pathname + parsed.search;
	} catch {
		return url;
	}
};

export const getAiConfig = (): AiConfig => {
	const localized: LocalizedAiConfig = (window as any).__TDI_DASHBOARD__?.ai;

	return {
		enabled: localized?.enabled ?? true,
		useMock: localized?.useMock ?? !localized?.restUrl,
		restUrl: localized?.restUrl ? onPageOrigin(localized.restUrl).replace(/\/?$/, '/') : '',
		nonce: localized?.nonce ?? '',
	};
};
