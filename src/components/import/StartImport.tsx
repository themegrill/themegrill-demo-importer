import apiFetch from '@wordpress/api-fetch';
import { X } from 'lucide-react';
import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Dialog, DialogClose, DialogContent } from '../../controls/Dialog';
import { Demo, PageWithSelection, PluginItem, TDIDashboardType } from '../../lib/types';
import { useLocalizedData } from '../../LocalizedDataContext';
import DialogConfirm from './DialogConfirm';
import DialogImported from './DialogImported';
import DialogImportFailed from './DialogImportFailed';
import DialogImporting from './DialogImporting';
import DialogPro from './DialogPro';

type Props = {
	demo: Demo;
	plugins: PluginItem[];
	siteLogoId: number;
	pages: PageWithSelection[];
	open: boolean;
	setOpen: React.Dispatch<React.SetStateAction<boolean>>;
	onOpen: () => void;
	onClose: () => void;
	setShowSidebar: React.Dispatch<React.SetStateAction<boolean>>;
	isPagesSelected: boolean;
};

const StartImport = ({
	demo,
	plugins,
	siteLogoId,
	pages,
	onOpen,
	onClose,
	open,
	setOpen,
	setShowSidebar,
	isPagesSelected,
}: Props) => {
	const { pagebuilder = '' } = useParams();
	const { localizedData, setLocalizedData } = useLocalizedData();

	const IMPORT_ACTIONS = {
		'install-plugins': {
			progressWeight: 15,
			importDetail: 'Installing required plugins...',
		},
		'import-content': {
			progressWeight: 50,
			importDetail: 'Importing content i.e. posts, pages, menus, media etc.',
		},
		'import-customizer': {
			progressWeight: 10,
			importDetail: 'Importing customizer and site settings...',
		},
		'import-widgets': {
			progressWeight: 10,
			importDetail: 'Importing widgets...',
		},
		complete: {
			progressWeight: 100,
			importDetail: 'Completing setup and finalizing settings... ',
		},
	};

	const [importAction, setImportAction] = useState<null | keyof typeof IMPORT_ACTIONS>(null);
	const [importProgress, setImportProgress] = useState(0);
	const [importProgressImportDetail, setImportProgressImportDetail] =
		useState('Initializing import...');
	const [isImportFailed, setIsImportFailed] = useState(false);

	const checkThemeExists = (demo: Demo) => {
		const proTheme = demo.theme_slug + '-pro';
		if (demo.theme_slug === 'zakra') {
			if (localizedData.zakra_pro_installed) {
				return true;
			}
			return false;
		}
		const themeExists = localizedData.installed_themes.includes(proTheme);
		return themeExists;
	};

	const handleInstallation = async () => {
		setShowSidebar(false);
		const selectedPlugins = plugins
			.filter((plugin) => plugin.isSelected === true)
			.map((plugin) => plugin.plugin);
		let selectedPages: PageWithSelection[] = [];

		if (isPagesSelected) {
			selectedPages = pages.filter((page) => page.isSelected === true);
		}

		const results: Record<keyof typeof IMPORT_ACTIONS, any> = {
			'install-plugins': null,
			'import-content': null,
			'import-customizer': null,
			'import-widgets': null,
			complete: null,
		};

		for (const key in IMPORT_ACTIONS) {
			const action = key as keyof typeof IMPORT_ACTIONS;
			setImportAction(action);
			setImportProgressImportDetail(IMPORT_ACTIONS[action]?.importDetail ?? '');
			try {
				const response = await apiFetch<Response>({
					path: 'tg-demo-importer/v1/install?action=' + action,
					method: 'POST',
					data: {
						demo_config: demo,
						opts: {
							pagebuilder: pagebuilder,
							plugins: selectedPlugins,
							blogname: '',
							blogdescription: '',
							custom_logo: siteLogoId,
							pages: isPagesSelected ? selectedPages : [],
						},
					},
					parse: false,
				});
				const data = await response.json();
				console.log(data);
				results[action] = data;
				setImportProgress((prev) => {
					let next = 0;
					if (action !== 'complete') {
						next = prev + (IMPORT_ACTIONS[action]?.progressWeight ?? 0);
					} else {
						next = 100;
					}
					return next;
				});
				if (action === 'complete') {
					const updated = await apiFetch<TDIDashboardType>({
						path: '/tg-demo-importer/v1/localized-data',
						method: 'GET',
					});
					setLocalizedData(updated);
				}
			} catch (e) {
				setImportAction(null);
				setImportProgress(0);
				setIsImportFailed(true);
				break;
			}
		}
	};

	const handleTryAgain = () => {
		let key: 'install-plugins' = 'install-plugins';
		const step = IMPORT_ACTIONS[key]!;
		setImportAction(key);
		setImportProgressImportDetail(step.importDetail);
		setImportProgress(0);
		setIsImportFailed(false);
		handleCleanup();
	};

	const handleCleanup = async () => {
		const response = await apiFetch<{
			success: boolean;
			message: string;
		}>({
			path: 'tg-demo-importer/v1/cleanup',
			method: 'POST',
		});
		if (response.success) {
			setImportProgress(0);
		}
		handleInstallation();
	};

	const renderDialog = () => {
		if (isImportFailed) {
			return <DialogImportFailed handleTryAgain={handleTryAgain} />;
		}

		if (demo.premium) {
			if (checkThemeExists(demo)) {
				if (
					!(demo.theme_slug === 'zakra'
						? localizedData.zakra_pro_activated
						: demo.theme_slug + '-pro' === localizedData.current_theme)
				) {
					return <DialogPro demo={demo} proUpgrade={false} proActivate={true} setOpen={setOpen} />;
				}
			} else {
				return <DialogPro demo={demo} proUpgrade={true} proActivate={false} setOpen={setOpen} />;
			}
		}
		if (!importAction) {
			return <DialogConfirm demo={demo} onConfirm={handleInstallation} />;
		}
		if (importAction !== 'complete') {
			return (
				<DialogImporting
					importProgress={importProgress}
					importProgressImportDetail={importProgressImportDetail}
				/>
			);
		}
		return <DialogImported demo={demo} />;
	};

	return (
		<Dialog
			open={open}
			onOpenChange={(v) => {
				if (v) {
					onOpen();
				} else {
					onClose();
				}
			}}
		>
			<DialogContent
				onInteractOutside={(e) => e.preventDefault()}
				className="border-solid border-[#D3D3D3] rounded-md z-[50000] px-0 py-0 gap-0 max-w-[300px] sm:max-w-[574px]"
				style={{ boxShadow: '0 20px 40px 0 rgba(37, 99, 235, 0.10)' }}
			>
				{!importAction && (
					<DialogClose asChild>
						<button
							type="button"
							className="absolute right-4 top-4 bg-transparent border-0 cursor-pointer"
							aria-label="Close"
						>
							<X size={18} color="#909090" />
						</button>
					</DialogClose>
				)}
				{renderDialog()}
			</DialogContent>
		</Dialog>
	);
};

export default StartImport;
