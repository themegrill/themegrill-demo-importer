import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/media-utils';
import { PencilLine, Trash2 } from 'lucide-react';
import React, { useState } from 'react';

type Props = {
	iframeRef: React.RefObject<HTMLIFrameElement>;
	setSiteLogoId: (value: number) => void;
};

type LogoData = {
	id: number;
	url: string;
	alt: string;
	width?: number;
	height?: number;
	filename?: string;
	mime?: string;
};

type MediaObject = {
	id: number;
	url: string;
	alt?: string;
	title?: string;
	width?: number;
	height?: number;
	filename?: string;
	mime?: string;
	filesizeInBytes?: number;
};

const LogoUploader = ({ iframeRef, setSiteLogoId }: Props) => {
	const [selectedLogo, setSelectedLogo] = useState<LogoData | null>(null);
	const [hovered, setHovered] = useState(false);

	const handleLogoSelect = (media: MediaObject): void => {
		// Validate file type
		if (!media.mime || !media.mime.startsWith('image/')) {
			alert('Please select a valid image file.');
			return;
		}

		// Validate file size (optional - e.g., max 2MB)
		if (media.filesizeInBytes && media.filesizeInBytes > 2 * 1024 * 1024) {
			alert('File size should be less than 2MB.');
			return;
		}

		const logoData: LogoData = {
			id: media.id,
			url: media.url,
			alt: media.alt || media.title || 'Logo',
			width: media.width,
			height: media.height,
			filename: media.filename,
			mime: media.mime,
		};

		setSelectedLogo(logoData);
		setSiteLogoId(logoData.id);
		updateLogoInIframe(logoData);
	};

	const updateLogoInIframe = async (logoData: LogoData) => {
		try {
			if (!iframeRef?.current?.contentWindow) {
				console.warn('Iframe not available');
				return;
			}

			const response = await fetch(logoData.url);
			const blob = await response.blob();
			const reader = new FileReader();
			reader.onloadend = () => {
				const base64data = reader.result as string;
				// Send message to iframe
				iframeRef.current?.contentWindow?.postMessage(
					{
						type: 'UPDATE_LOGO',
						logoData: base64data,
					},
					'*',
				);
			};
			reader.readAsDataURL(blob);

			// Listen for confirmation
			const handleMessage = (event: MessageEvent) => {
				if (event.data.type === 'LOGO_UPDATED') {
					// console.log('Logo updated successfully:', event.data.success);
					window.removeEventListener('message', handleMessage);
				}
			};

			window.addEventListener('message', handleMessage);

			// Cleanup after timeout
			setTimeout(() => {
				window.removeEventListener('message', handleMessage);
			}, 5000);
		} catch (error) {
			console.error('Error sending logo update message:', error);
		}
	};

	const renderSelectedLogo = () => {
		if (!selectedLogo) return null;
		return (
			<>
				<div
					className="h-[50px] p-1 bg-white rounded-md border-2 border-dashed border-[#C4C4C4]/80 max-w-full flex items-center justify-center relative"
					onMouseEnter={() => setHovered(true)}
					onMouseLeave={() => setHovered(false)}
				>
					<img
						src={selectedLogo.url}
						alt={selectedLogo.alt}
						className="max-w-full max-h-full border border-[#eee] rounded"
					/>
					<div
						className={`absolute bottom-0 left-0 right-0 flex justify-center items-center gap-[12px] bg-white border-0 border-t border-solid border-[#C4C4C4]/80 p-[6px] transition-all duration-300 ease-in-out ${
							hovered ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2 pointer-events-none'
						}`}
					>
						<MediaUpload
							allowedTypes={['image']}
							onSelect={handleLogoSelect}
							value={selectedLogo.id}
							render={({ open }: { open: () => void }) => (
								<button
									type="button"
									className="text-[#2563eb] hover:text-[#2563eb] text-[12px] no-underline border-0 bg-transparent p-0 cursor-pointer flex items-center gap-1"
									onClick={open}
								>
									<PencilLine size={14} />
									{__('Change', 'themegrill-demo-importer')}
								</button>
							)}
						/>
						<span className="text-[#C4C4C4]">|</span>
						<button
							type="button"
							className="text-[#dc3545] hover:text-[#c82333] text-[12px] no-underline border-0 bg-transparent p-0 cursor-pointer flex items-center gap-1"
							onClick={handleRemoveLogo}
						>
							<Trash2 size={14} />
							{__('Remove', 'themegrill-demo-importer')}
						</button>
					</div>
				</div>
			</>
		);
	};

	const handleRemoveLogo = () => {
		setSelectedLogo(null);
		try {
			if (!iframeRef?.current?.contentWindow) {
				console.warn('Iframe not available');
				return;
			}

			// Send message to iframe
			iframeRef.current.contentWindow.postMessage(
				{
					type: 'REMOVE_LOGO',
				},
				'*',
			);

			// Listen for confirmation
			const handleMessage = (event: MessageEvent) => {
				if (event.data.type === 'LOGO_UPDATED') {
					console.log('Logo updated successfully:', event.data.success);
					window.removeEventListener('message', handleMessage);
				}
			};

			window.addEventListener('message', handleMessage);

			// Cleanup after timeout
			setTimeout(() => {
				window.removeEventListener('message', handleMessage);
			}, 5000);
		} catch (error) {
			console.error('Error sending logo update message:', error);
		}
	};

	return (
		<div>
			<h3 className="text-[16px] text-[#1F1F1F] mt-0 mb-5">
				{__('Change Logo', 'themegrill-demo-importer')}
			</h3>{' '}
			{selectedLogo ? (
				renderSelectedLogo()
			) : (
				<MediaUpload
					allowedTypes={['image']}
					onSelect={handleLogoSelect}
					render={({ open }: { open: () => void }) => {
						return (
							<button
								type="button"
								className="tg-upload-logo w-full p-0 h-[62px] cursor-pointer bg-white rounded-md border-2 border-dashed border-[#C4C4c4]/80"
								onClick={open}
							>
								<div className="text-center">
									<p className="m-0 text-[13px] text-[#383838] font-normal">
										{__('Upload Logo Here', 'themegrill-demo-importer')}
									</p>
								</div>
							</button>
						);
					}}
				/>
			)}
		</div>
	);
};

export default LogoUploader;
