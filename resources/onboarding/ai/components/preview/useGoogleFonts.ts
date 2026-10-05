import { useEffect } from 'react';

// Loads the brand fonts so the preview renders in them.
export const useGoogleFonts = (families: string[]) => {
	const key = [...new Set(families.filter(Boolean))].sort().join('|');

	useEffect(() => {
		if (!key) return;
		const link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = `https://fonts.googleapis.com/css2?${key
			.split('|')
			.map((f) => `family=${encodeURIComponent(f).replace(/%20/g, '+')}:wght@400;600;700`)
			.join('&')}&display=swap`;
		document.head.appendChild(link);
		return () => link.remove();
	}, [key]);
};
