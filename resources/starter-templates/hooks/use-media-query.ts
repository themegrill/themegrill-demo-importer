"use client";

import { useEffect, useLayoutEffect, useState } from "react";

type UseMediaQueryOptions = {
	defaultValue?: boolean;
	initializeWithValue?: boolean;
};

export function useMediaQuery(
	query: string,
	{
		defaultValue = false,
		initializeWithValue = true,
	}: UseMediaQueryOptions = {}
): boolean {
	const getMatches = (query: string): boolean =>
		window.matchMedia(query).matches;
	const [matches, setMatches] = useState<boolean>(() => {
		if (initializeWithValue) {
			return getMatches(query);
		}
		return defaultValue;
	});

	function handleChange() {
		setMatches(getMatches(query));
	}

	useLayoutEffect(() => {
		const matchMedia = window.matchMedia(query);
		handleChange();
		matchMedia.addEventListener("change", handleChange);

		return () => {
			matchMedia.removeEventListener("change", handleChange);
		};
	}, [query]);

	return matches;
}
