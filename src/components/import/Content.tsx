import React, { useEffect, useState } from 'react';
import { Demo } from '../../lib/types';
import IframeLoading from './IframeLoading';

type Props = {
	demo: Demo;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	device: string;
};

const Content = ({ demo, iframeRef, device }: Props) => {
	const [deviceClass, setDeviceClass] = useState('');
	const [isIframeLoading, setIsIframeLoading] = useState(true);

	useEffect(() => {
		if (device === 'desktop') {
			setDeviceClass('w-full');
		} else if (device === 'tablet') {
			setDeviceClass('w-[768px] border-2 border-solid border-[#EDEDED]');
		} else if (device === 'mobile') {
			setDeviceClass('w-[420px] border-2 border-solid border-[#EDEDED]');
		}
	}, [device]);

	return (
		<>
			<div className="flex-1 bg-[#fff]">
				{isIframeLoading && (
					<div className="p-[60px] pb-0 bg-white iframe-wrapper">
						<IframeLoading />
					</div>
				)}
				<div className="h-full flex justify-center items-center">
					<iframe
						src={demo?.url}
						title={`${
							demo.title ||
							demo.slug.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
						} Preview`}
						className={`ml-auto mr-auto h-full ${deviceClass}`}
						onLoad={() => setIsIframeLoading(false)}
						ref={iframeRef}
					></iframe>
				</div>
			</div>
		</>
	);
};

export default Content;
