import { __ } from '@wordpress/i18n';
import { Loader2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

// Pages are laid out at desktop width, then scaled down to fit the panel.
const DESKTOP_WIDTH = 1280;

/**
 * The generated page in a browser-like frame. The HTML comes from the AI
 * backend, so the frame is sandboxed without same-origin: it can't reach
 * wp-admin or its cookies.
 */
const SitePreview = ({ html, title, loading }: { html?: string; title: string; loading?: boolean }) => {
	const frameRef = useRef<HTMLDivElement>(null);
	const [scale, setScale] = useState(1);

	useEffect(() => {
		const el = frameRef.current;
		if (!el) return;
		const observer = new ResizeObserver(([entry]) => {
			setScale(Math.min(1, (entry?.contentRect.width ?? DESKTOP_WIDTH) / DESKTOP_WIDTH));
		});
		observer.observe(el);
		return () => observer.disconnect();
	}, []);

	return (
		<div className="rounded-lg border border-solid border-[#E5E7EB] bg-white overflow-hidden shadow-sm">
			<div className="flex items-center gap-3 px-4 h-10 border-0 border-b border-solid border-[#E5E7EB] bg-[#F8FAFC]">
				<span className="flex gap-[6px]" aria-hidden="true">
					<span className="w-[10px] h-[10px] rounded-full bg-[#F87171]" />
					<span className="w-[10px] h-[10px] rounded-full bg-[#FBBF24]" />
					<span className="w-[10px] h-[10px] rounded-full bg-[#34D399]" />
				</span>
				<span className="flex-1 min-w-0 truncate text-center text-[12px] text-[#6B6B6B]">{title}</span>
				<span className="w-[42px]" aria-hidden="true" />
			</div>
			<div ref={frameRef} className="relative h-[max(520px,calc(100vh-300px))] overflow-hidden bg-white">
				{html && (
					<iframe
						title={title}
						srcDoc={html}
						sandbox="allow-scripts"
						className="absolute top-0 left-0 border-0 origin-top-left"
						style={{ width: DESKTOP_WIDTH, height: `${100 / scale}%`, transform: `scale(${scale})` }}
					/>
				)}
				{loading && (
					<div className="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-white text-[14px] text-[#6B6B6B]">
						<Loader2 size={28} className="animate-spin text-[#2563EB]" aria-hidden="true" />
						{__('Putting your page together…', 'themegrill-demo-importer')}
					</div>
				)}
			</div>
		</div>
	);
};

export default SitePreview;
