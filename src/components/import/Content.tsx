import React, { useEffect, useState } from 'react';
import { Demo } from '../../lib/types';

type Props = {
	demo: Demo;
	iframeRef: React.RefObject<HTMLIFrameElement>;
	device: string;
};

const Content = ({ demo, iframeRef, device }: Props) => {
	const [deviceClass, setDeviceClass] = useState('');

	useEffect(() => {
		if (device === 'desktop') {
			setDeviceClass('w-full');
		} else if (device === 'tablet') {
			setDeviceClass('w-[768px]');
		} else if (device === 'mobile') {
			setDeviceClass('w-[420px]');
		}
	}, [device]);

	return (
		<div className="flex-1 flex justify-center items-center bg-[#fff]">
			<iframe
				src={demo?.url}
				title={`${
					demo.title || demo.slug.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
				} Preview`}
				className={`ml-auto mr-auto h-full ${deviceClass}`}
				ref={iframeRef}
			></iframe>
		</div>
	);
};

export default Content;
