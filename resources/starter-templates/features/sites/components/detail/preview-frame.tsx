import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";
import {
	forwardRef,
	memo,
	useCallback,
	useImperativeHandle,
	useRef,
	useState,
} from "react";
import { useShallow } from "zustand/shallow";

export interface PreviewFrameHandle {
	reload: () => void;
	getIframe: () => HTMLIFrameElement | null;
}

interface PreviewFrameProps {
	onLoad?: React.ReactEventHandler<HTMLIFrameElement>;
	onError?: React.ReactEventHandler<HTMLIFrameElement>;
}

export const PreviewFrame = memo(
	forwardRef<PreviewFrameHandle, PreviewFrameProps>(
		({ onLoad, onError }, ref) => {
			const { site, device } = useSiteDetailStore(
				useShallow((s) => ({
					site: s.site!,
					device: s.device,
				}))
			);
			const src = site.url;
			const [isLoading, setIsLoading] = useState<boolean>(true);
			const iframeRef = useRef<HTMLIFrameElement>(null);

			useImperativeHandle(
				ref,
				(): PreviewFrameHandle => ({
					reload: () => {
						if (iframeRef.current) {
							iframeRef.current.src = iframeRef.current.src;
						}
					},
					getIframe: () => iframeRef.current,
				}),
				[]
			);

			const handleLoad = useCallback<
				React.ReactEventHandler<HTMLIFrameElement>
			>(
				(e) => {
					setIsLoading(false);
					onLoad?.(e);
				},
				[onLoad]
			);

			const handleError = useCallback<
				React.ReactEventHandler<HTMLIFrameElement>
			>(
				(e) => {
					setIsLoading(false);
					onError?.(e);
				},
				[onError]
			);

			return (
				<div className="relative w-full h-full flex-1 flex flex-col min-h-svh">
					{isLoading && (
						<div className="absolute inset-0 flex items-center justify-center bg-gray-100">
							<div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" />
						</div>
					)}
					<div
						data-device={device}
						className="mx-auto transition-all flex-1 flex flex-col min-h-svh w-full max-w-full data-[device=tablet]:max-w-[768px] data-[device=mobile]:max-w-[375px]"
					>
						<iframe
							ref={iframeRef}
							src={src}
							className="w-full h-fit border-0 overflow-hidden flex-1"
							onLoad={handleLoad}
							onError={handleError}
							title="Preview Frame"
						/>
					</div>
				</div>
			);
		}
	)
);

PreviewFrame.displayName = "PreviewFrame";
