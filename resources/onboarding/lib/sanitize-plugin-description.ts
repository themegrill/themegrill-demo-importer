/**
 * Sanitize a small HTML fragment for plugin feature descriptions.
 * Allows basic formatting and http(s)/mailto links only.
 */
const ALLOWED_TAGS = new Set(['A', 'STRONG', 'B', 'EM', 'I', 'CODE', 'BR', 'P', 'SPAN']);
const ALLOWED_ATTRS: Record<string, Set<string>> = {
	A: new Set(['href', 'title', 'target', 'rel']),
};

function decodeHtmlEntities(value: string): string {
	const textarea = document.createElement('textarea');
	textarea.innerHTML = value;
	return textarea.value;
}

export function sanitizePluginDescriptionHtml(html: string): string {
	if (!html) {
		return '';
	}

	// SSR / non-DOM: never return raw markup that React would paint as text.
	if (typeof document === 'undefined') {
		return html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
	}

	let input = html.trim();

	// Some payloads arrive HTML-escaped (`&lt;strong&gt;…`). Decode up to twice.
	for (let i = 0; i < 2; i++) {
		if (!/&lt;\/?[a-z]/i.test(input)) {
			break;
		}
		const decoded = decodeHtmlEntities(input);
		if (decoded === input) {
			break;
		}
		input = decoded;
	}

	const doc = new DOMParser().parseFromString(input, 'text/html');

	const sanitizeNode = (node: Node): Node | null => {
		if (node.nodeType === Node.TEXT_NODE) {
			return document.createTextNode(node.textContent ?? '');
		}

		if (node.nodeType !== Node.ELEMENT_NODE) {
			return null;
		}

		const el = node as Element;

		if (!ALLOWED_TAGS.has(el.tagName)) {
			const frag = document.createDocumentFragment();
			Array.from(el.childNodes).forEach((child) => {
				const clean = sanitizeNode(child);
				if (clean) {
					frag.appendChild(clean);
				}
			});
			return frag;
		}

		const cleanEl = document.createElement(el.tagName.toLowerCase());
		const allowedAttrs = ALLOWED_ATTRS[el.tagName] ?? new Set<string>();

		Array.from(el.attributes).forEach((attr) => {
			const name = attr.name.toLowerCase();
			if (!allowedAttrs.has(name)) {
				return;
			}

			if (name === 'href') {
				const href = attr.value.trim();
				if (!/^(https?:|mailto:|#)/i.test(href)) {
					return;
				}
				cleanEl.setAttribute('href', href);
				cleanEl.setAttribute('target', '_blank');
				cleanEl.setAttribute('rel', 'noopener noreferrer');
				return;
			}

			if (name === 'target' || name === 'rel') {
				return;
			}

			cleanEl.setAttribute(name, attr.value);
		});

		Array.from(el.childNodes).forEach((child) => {
			const clean = sanitizeNode(child);
			if (clean) {
				cleanEl.appendChild(clean);
			}
		});

		return cleanEl;
	};

	const wrap = document.createElement('div');
	Array.from(doc.body.childNodes).forEach((child) => {
		const clean = sanitizeNode(child);
		if (clean) {
			wrap.appendChild(clean);
		}
	});

	return wrap.innerHTML;
}
