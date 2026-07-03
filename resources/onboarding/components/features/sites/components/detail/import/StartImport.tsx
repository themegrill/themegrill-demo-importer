import { X } from 'lucide-react';
import React, { useState } from 'react';
import { queryClient } from '../../../../../../lib/query-client';
import { Demo, PageWithSelection, PluginItem } from '../../../../../../lib/types';
import { useLocalizedData } from '../../../../../../LocalizedDataContext';
import { Dialog, DialogClose, DialogContent } from '../../../../../ui/Dialog';
import {
	cleanupQueryOptions,
	importDataQueryOptions,
	importDemo,
	localizedDataQueryOptions,
	saveTrackingConsent,
} from '../../../../api/import.api';
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
	colorPalette: string[] | [];
	typography: string[] | [];
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
	colorPalette,
	typography,
}: Props) => {
	const { localizedData, setLocalizedData } = useLocalizedData();

	const IMPORT_ACTIONS = {
		'install-plugins': {
			progressWeight: 15,
			importDetail: 'Installing required plugins...',
		},
		'import-content': {
			progressWeight: 5,
			importDetail: 'Parsing content XML...',
		},
		'import-content-posts': {
			progressWeight: 25,
			importDetail: 'Importing posts and pages...',
		},
		'import-media': {
			progressWeight: 30,
			importDetail: 'Importing media files...',
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
		const proTheme = demo?.theme_slug + '-pro';
		if (demo?.theme_slug === 'zakra') {
			if (localizedData.zakra_pro_installed) {
				return true;
			}
			return false;
		}
		const themeExists = localizedData.installed_themes.includes(proTheme);
		return themeExists;
	};

	const handleInstallation = async (allowContribution: boolean = false) => {
		await saveTrackingConsent({ allowContribution });
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
			'import-content-posts': null,
			'import-media': null,
			'import-customizer': null,
			'import-widgets': null,
			complete: null,
		};

		const baseParams = {
			demo: demo,
			selectedPlugins: selectedPlugins,
			siteLogoId: siteLogoId,
			selectedPages: selectedPages,
			isPagesSelected: isPagesSelected,
			colorPalette: colorPalette,
			typography: typography,
		};

		for (const key in IMPORT_ACTIONS) {
			const action = key as keyof typeof IMPORT_ACTIONS;
			setImportAction(action);
			setImportProgressImportDetail(IMPORT_ACTIONS[action]?.importDetail ?? '');
			try {
				const batchedActions = ['import-content-posts', 'import-media'] as const;
				if ((batchedActions as readonly string[]).includes(action)) {
					// Batch loop — call until server signals done.
					const actionKeys = Object.keys(IMPORT_ACTIONS);
					const progressBase = actionKeys
						.slice(0, actionKeys.indexOf(action))
						.reduce((sum, k) => sum + (IMPORT_ACTIONS[k as keyof typeof IMPORT_ACTIONS]?.progressWeight ?? 0), 0);
					const actionWeight = IMPORT_ACTIONS[action]?.progressWeight ?? 0;
					let total = 0;
					let remaining = 0;

					while (true) {
						const batchData = await importDemo({ ...baseParams, action });
						if (!batchData) throw new Error(`Empty response from ${action}`);
						results[action] = batchData;
						total = batchData.total ?? total;
						remaining = batchData.remaining ?? 0;

						if (total > 0) {
							const imported = total - remaining;
							setImportProgress(progressBase + Math.round((imported / total) * actionWeight));
						}

						if (batchData.done) break;
					}
				} else {
					const params = { ...baseParams, action };
					const data = await queryClient.ensureQueryData(importDataQueryOptions(params));
					results[action] = data;
					if (action === 'complete') {
						const localizedResponse = await queryClient.ensureQueryData(
							localizedDataQueryOptions({}),
						);
						setLocalizedData(localizedResponse);
						setImportProgress(100);
					} else {
						setImportProgress((prev) => prev + (IMPORT_ACTIONS[action]?.progressWeight ?? 0));
					}
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
		queryClient.clear();
		handleCleanup(false);
	};

	const handleCleanup = async (allowContribution: boolean = false) => {
		const response = await queryClient.ensureQueryData(cleanupQueryOptions());
		if (response.success) {
			setImportProgress(0);
		}
		handleInstallation(allowContribution);
	};

	const renderDialog = () => {
		if (isImportFailed) {
			return <DialogImportFailed handleTryAgain={handleTryAgain} />;
		}

		if (demo?.premium) {
			if (checkThemeExists(demo)) {
				if (
					!(demo?.theme_slug === 'zakra'
						? localizedData.zakra_pro_activated
						: demo?.theme_slug + '-pro' === localizedData.current_theme)
				) {
					return <DialogPro demo={demo} proUpgrade={false} proActivate={true} setOpen={setOpen} />;
				}
			} else {
				return <DialogPro demo={demo} proUpgrade={true} proActivate={false} setOpen={setOpen} />;
			}
		}
		if (!importAction) {
			return <DialogConfirm onConfirm={handleInstallation} />;
		}
		if (importProgress !== 100) {
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
