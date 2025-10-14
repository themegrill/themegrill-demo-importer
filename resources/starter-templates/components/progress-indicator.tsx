import { useIsFetching } from "@tanstack/react-query";
import { useEffect, useState, useRef } from "react";
import { Progress } from "@/starter-templates/components/ui/progress";

export const ProgressIndicator = () => {
	const isFetching = useIsFetching();
	const [width, setWidth] = useState(0);
	const intervalRef = useRef<NodeJS.Timeout | null>(null);
	const timeoutRef = useRef<NodeJS.Timeout | null>(null);

	useEffect(() => {
		if (intervalRef.current) clearInterval(intervalRef.current);
		if (timeoutRef.current) clearTimeout(timeoutRef.current);
		if (isFetching) {
			setWidth(20);
			document.body.dataset.wait = "true";
			intervalRef.current = setInterval(() => {
				setWidth((prevWidth) => {
					if (prevWidth >= 90) {
						if (intervalRef.current)
							clearInterval(intervalRef.current);
						return 90;
					}
					return prevWidth + 10;
				});
			}, 500);
		} else {
			setWidth(100);
			timeoutRef.current = setTimeout(() => {
				setWidth(0);
			}, 200);
			document.body.removeAttribute("data-wait");
		}

		return () => {
			if (intervalRef.current) clearInterval(intervalRef.current);
			if (timeoutRef.current) clearTimeout(timeoutRef.current);
			document.body.removeAttribute("data-wait");
		};
	}, [isFetching]);

	if (width === 0) return null;

	return (
		<div className="fixed top-0 left-0 right-0 h-1 bg-gray-200 z-50">
			<Progress value={width} className="rounded-none h-full" />
		</div>
	);
};

ProgressIndicator.displayName = "ProgressIndicator";
