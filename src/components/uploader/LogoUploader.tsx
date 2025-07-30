import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/media-utils';
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
				<div className="bg-white rounded mb-[10px] border border-dashed border-[#BABABA] p-[16px]">
					<div className="flex items-center gap-[12px]">
						<div className="flex-shrink-0">
							<img
								src={selectedLogo.url}
								alt={selectedLogo.alt}
								className="max-w-[120px] max-h-[60px] object-contain border border-[#eee] rounded"
							/>
						</div>

						<div className="flex-1 min-w-0">
							<p className="m-0 text-[12px] text-[#222] font-medium truncate">
								{selectedLogo.filename || 'Logo'}
							</p>
							<p className="m-0 text-[11px] text-[#6B6B6B] mt-[2px]">
								{selectedLogo.width} × {selectedLogo.height} pixels
							</p>
							<p className="m-0 text-[11px] text-[#6B6B6B]">{selectedLogo.mime}</p>
						</div>
					</div>
				</div>
				<div className="flex items-center gap-[12px]">
					<button
						type="button"
						className="text-[#dc3545] hover:text-[#c82333] text-[12px] underline"
						onClick={handleRemoveLogo}
					>
						{__('Remove', 'themegrill-demo-importer')}
					</button>
					<MediaUpload
						allowedTypes={['image']}
						onSelect={handleLogoSelect}
						value={selectedLogo.id}
						render={({ open }: { open: () => void }) => (
							<button
								type="button"
								className="text-[#0073aa] hover:text-[#005a87] text-[12px] underline"
								onClick={open}
							>
								{__('Change Logo', 'themegrill-demo-importer')}
							</button>
						)}
					/>
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
		<div className="mb-[24px]">
			<h4 className="text-[17px] m-0 mb-[20px]">{__('Change Logo', 'themegrill-demo-importer')}</h4>
			{selectedLogo ? (
				renderSelectedLogo()
			) : (
				<>
					<MediaUpload
						allowedTypes={['image']}
						onSelect={handleLogoSelect}
						render={({ open }: { open: () => void }) => {
							return (
								<button
									type="button"
									className="tg-upload-logo px-[16px] py-[32px] cursor-pointer bg-white rounded mb-[10px] border border-dashed border-[#BABABA]"
									onClick={open}
								>
									<div className="text-center">
										<h4 className="m-0 mb-[8px] text-[14px] text-[#222]">
											{__('Upload Logo Here', 'themegrill-demo-importer')}
										</h4>
										<p className="m-0 text-[12px] text-[#6B6B6B]">
											{__('Suggested Dimension: 190x60 pixels', 'themegrill-demo-importer')}
										</p>
									</div>
								</button>
							);
						}}
					/>
					<p className="text-[#6b6b6b] font-[400] text-[12px] m-0">
						<i>
							{__(
								'Don’t have a logo yet? No problem! You can upload it later.',
								'themegrill-demo-importer',
							)}
						</i>
					</p>
				</>
			)}
		</div>
	);
};

export default LogoUploader;
