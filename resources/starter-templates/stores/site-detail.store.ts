import { create } from "zustand";
import { SiteData } from "@/starter-templates/features/sites/api/sites.types";
import { SiteDetailForm } from "@/starter-templates/features/sites/components/detail/site-detail-form";
import {
	calculateOverallProgress,
	importContent,
	importThemeMods,
	importWidgets,
	installPlugins,
} from "@/starter-templates/features/sites/api/import.api";

export type Step =
	| "initial"
	| "features"
	| "ready"
	| "importing"
	| "completed"
	| "paywall";

export type Device = "desktop" | "tablet" | "mobile";

export type ImportOperation =
	| "installing-plugins"
	| "importing-content"
	| "importing-theme-mods"
	| "importing-widgets";

export type ContentImportStep =
	| "parsing"
	| "categories"
	| "tags"
	| "terms"
	| "attachments"
	| "pages"
	| "posts"
	| "others"
	| "finalize";

export type ImportStatus =
	| "pending"
	| "running"
	| "success"
	| "error"
	| "skipped";

export type ImportOperationState = {
	operation: ImportOperation;
	status: ImportStatus;
	progress: number;
	error?: string;
	result?: unknown;
	startTime?: number;
	endTime?: number;
	currentSubStep?: ContentImportStep;
	subStepProgress?: number;
};

export type ImportState = {
	currentOperation: ImportOperation | null;
	operations: Record<ImportOperation, ImportOperationState>;
	overallProgress: number;
	hasErrors: boolean;
	isComplete: boolean;
};

export type ImportData = Omit<
	SiteData,
	| "plugins"
	| "pages"
	| "premium"
	| "theme_slug"
	| "title"
	| "url"
	| "canImport"
> &
	SiteDetailForm;

const VALID_TRANSITIONS: Record<Step, Step[]> = {
	initial: ["features", "paywall"],
	features: ["ready"],
	ready: ["importing"],
	importing: ["completed"],
	completed: [],
	paywall: [],
} as const;

const TERMINAL_STATES = new Set<Step>(["completed", "paywall"]);

const STEP_ORDER: Step[] = [
	"initial",
	"features",
	"ready",
	"importing",
	"completed",
];

const IMPORT_OPERATIONS: ImportOperation[] = [
	"installing-plugins",
	"importing-content",
	"importing-theme-mods",
	"importing-widgets",
];

export const OPERATION_WEIGHTS: Record<ImportOperation, number> = {
	"installing-plugins": 15,
	"importing-content": 55,
	"importing-theme-mods": 15,
	"importing-widgets": 15,
};

export const CONTENT_IMPORT_STEPS: ContentImportStep[] = [
	"parsing",
	"categories",
	"tags",
	"terms",
	"attachments",
	"pages",
	"posts",
	"others",
	"finalize",
];

export const canTransitionTo = (from: Step, to: Step): boolean => {
	return VALID_TRANSITIONS[from].includes(to);
};

const getNextStep = (currentStep: Step): Step | null => {
	const currentIndex = STEP_ORDER.indexOf(currentStep);
	return STEP_ORDER[currentIndex + 1] ?? null;
};

const getPreviousStep = (currentStep: Step): Step | null => {
	if (currentStep === "paywall") return "initial";
	const currentIndex = STEP_ORDER.indexOf(currentStep);
	return currentIndex > 0 ? (STEP_ORDER[currentIndex - 1] ?? null) : null;
};

const isTerminalState = (step: Step): boolean => {
	return TERMINAL_STATES.has(step);
};

const createInitialImportState = (): ImportState => ({
	currentOperation: null,
	operations: IMPORT_OPERATIONS.reduce(
		(acc, op) => {
			acc[op] = {
				operation: op,
				status: "pending",
				progress: 0,
			};
			return acc;
		},
		{} as Record<ImportOperation, ImportOperationState>
	),
	overallProgress: 0,
	hasErrors: false,
	isComplete: false,
});

// Update sub-step progress for importing-content
export const updateContentImportSubStep = (
	set: (fn: (state: SiteDetailStore) => Partial<SiteDetailStore>) => void,
	subStep: ContentImportStep,
	progress: number
) => {
	set((state) => {
		const operations = {
			...state.importState.operations,
			"importing-content": {
				...state.importState.operations["importing-content"],
				currentSubStep: subStep,
				subStepProgress: progress,
			},
		};

		return {
			importState: {
				...state.importState,
				operations,
				overallProgress: calculateOverallProgress(operations),
			},
		};
	});
};

export interface SiteDetailStore {
	device: Device;
	step: Step;
	importState: ImportState;
	site: SiteData | null;
	logoUrl: string;

	validNextSteps: () => Step[];
	canGoForward: () => boolean;
	canGoBack: () => boolean;

	setDevice: (device: Device) => void;
	setLogoUrl: (url: string) => void;
	init: (site: SiteData, initialStep?: Step, initialDevice?: Device) => void;

	goToFeatures: () => void;
	goToPaywall: () => void;
	goToReady: () => void;
	startImporting: (data: ImportData, retry?: boolean) => Promise<void>;
	completeImport: () => void;
	goBack: () => void;
	reset: (targetStep?: Step) => void;
}

export const useSiteDetailStore = create<SiteDetailStore>((set, get) => ({
	device: "desktop",
	step: "initial",
	importState: createInitialImportState(),
	site: null,
	logoUrl: "",

	init(site, initialStep = "initial", initialDevice = "desktop") {
		set({
			site,
			step: initialStep,
			device: initialDevice,
			logoUrl: "",
			importState: createInitialImportState(),
		});
	},

	setLogoUrl(url) {
		set({ logoUrl: url });
	},

	validNextSteps: () => VALID_TRANSITIONS[get().step],

	canGoForward: () => {
		const { step } = get();
		const nextStep = getNextStep(step);
		return nextStep !== null && canTransitionTo(step, nextStep);
	},

	canGoBack: () => {
		const { step } = get();
		return getPreviousStep(step) !== null && !isTerminalState(step);
	},

	setDevice: (device) => set({ device }),

	goToFeatures: () => {
		const { step } = get();
		if (canTransitionTo(step, "features")) {
			set({ step: "features" });
		}
	},

	goToPaywall: () => {
		const { step } = get();
		if (canTransitionTo(step, "paywall")) {
			set({ step: "paywall" });
		}
	},

	goToReady: () => {
		const { step } = get();
		if (canTransitionTo(step, "ready")) {
			set({ step: "ready" });
		}
	},

	startImporting: async (data, retry) => {
		const { step } = get();
		if (!canTransitionTo(step, "importing") && !retry) {
			console.error(`Cannot start importing from step: ${step}`);
			return;
		}

		set({ step: "importing", importState: createInitialImportState() });

		for (const operation of IMPORT_OPERATIONS) {
			try {
				set((state) => ({
					importState: {
						...state.importState,
						currentOperation: operation,
						operations: {
							...state.importState.operations,
							[operation]: {
								...state.importState.operations[operation],
								status: "running",
								progress: 0,
								startTime: Date.now(),
								currentSubStep: undefined,
								subStepProgress: undefined,
							},
						},
					},
				}));

				const result =
					operation === "importing-content"
						? await importContent(data, set)
						: operation === "installing-plugins"
							? await installPlugins(data)
							: operation === "importing-theme-mods"
								? await importThemeMods(data)
								: await importWidgets(data);

				set((state) => {
					const operations = {
						...state.importState.operations,
						[operation]: {
							...state.importState.operations[operation],
							status: "success" as const,
							progress: 100,
							result,
							endTime: Date.now(),
							currentSubStep: undefined,
							subStepProgress: undefined,
						},
					};

					return {
						importState: {
							...state.importState,
							operations,
							overallProgress:
								calculateOverallProgress(operations),
						},
					};
				});
			} catch (error) {
				const errorMessage =
					error instanceof Error ? error.message : "Unknown error";

				set((state) => {
					const operations = {
						...state.importState.operations,
						[operation]: {
							...state.importState.operations[operation],
							status: "error" as const,
							error: errorMessage,
							endTime: Date.now(),
						},
					};

					return {
						importState: {
							...state.importState,
							operations,
							currentOperation: null,
							hasErrors: true,
							overallProgress:
								calculateOverallProgress(operations),
						},
					};
				});

				return;
			}
		}

		set((state) => ({
			importState: {
				...state.importState,
				currentOperation: null,
				isComplete: true,
				overallProgress: 100,
			},
		}));

		get().completeImport();
	},

	completeImport: () => {
		const { step } = get();
		if (canTransitionTo(step, "completed")) {
			set({ step: "completed" });
		}
	},

	goBack: () => {
		const prevStep = getPreviousStep(get().step);
		if (prevStep) {
			set({ step: prevStep });
		}
	},

	reset: (targetStep = "initial") => {
		set({
			step: targetStep,
			importState: createInitialImportState(),
		});
	},
}));
