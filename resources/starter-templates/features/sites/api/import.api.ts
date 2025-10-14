import {
	CONTENT_IMPORT_STEPS,
	ContentImportStep,
	ImportData,
	OPERATION_WEIGHTS,
} from "@/starter-templates/stores/site-detail.store";
import apiFetch from "@wordpress/api-fetch";

export const calculateOverallProgress = (operations: any): number => {
	let totalProgress = 0;

	Object.keys(OPERATION_WEIGHTS).forEach((op) => {
		const opState = operations[op];
		const weight = OPERATION_WEIGHTS[op as keyof typeof OPERATION_WEIGHTS];

		if (opState.status === "success") {
			totalProgress += weight;
		} else if (opState.status === "running") {
			if (op === "importing-content" && opState.currentSubStep) {
				const currentStepIndex = CONTENT_IMPORT_STEPS.indexOf(
					opState.currentSubStep
				);
				const totalSteps = CONTENT_IMPORT_STEPS.length;
				const stepWeight = 1 / totalSteps;

				const completedStepsProgress = currentStepIndex * stepWeight;
				const currentStepProgress =
					((opState.subStepProgress || 0) / 100) * stepWeight;
				const operationProgress =
					completedStepsProgress + currentStepProgress;

				totalProgress += weight * operationProgress;
			} else {
				totalProgress += weight * (opState.progress / 100);
			}
		}
	});

	return Math.round(totalProgress);
};

export const installPlugins = async ({
	plugins = [],
}: ImportData): Promise<unknown> => {
	if (!plugins.length) return true;
	const res = await apiFetch({
		path: "themegrill-starter-templates/v1/import/plugins",
		method: "POST",
		data: {
			plugins,
		},
	});
	return res;
};

export const updateContentSubStep = (
	set: any,
	subStep: ContentImportStep,
	progress: number
) => {
	set((state: any) => {
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

export const importContent = async (
	data: ImportData,
	set: any
): Promise<unknown> => {
	try {
		updateContentSubStep(set, "parsing", 0);
		const parsedData = await apiFetch<{
			categories: Record<string, unknown>[];
			tags: Record<string, unknown>[];
			terms: Record<string, unknown>[];
			posts: Record<string, unknown>[];
			authors: Record<string, unknown>[];
			baseUrl: string;
			baseBlogUrl: string;
		}>({
			path: "themegrill-starter-templates/v1/import/content/initialize",
			method: "POST",
			data: {
				content: data.content,
				id: data.slug,
			},
		});
		updateContentSubStep(set, "parsing", 100);

		let attachments: Record<string, unknown>[] = [];
		let pages: Record<string, unknown>[] = [];
		let posts: Record<string, unknown>[] = [];
		let others: Record<string, unknown>[] = [];

		for (const post of parsedData.posts) {
			if (post.post_type === "attachment") {
				attachments.push(post);
			} else if (post.post_type === "page") {
				pages.push(post);
			} else if (post.post_type === "post") {
				posts.push(post);
			} else {
				others.push(post);
			}
		}

		const taxonomySteps: Array<{
			key: keyof typeof parsedData;
			step: ContentImportStep;
		}> = [
			{ key: "categories", step: "categories" },
			{ key: "tags", step: "tags" },
			{ key: "terms", step: "terms" },
		];

		for (const { key, step } of taxonomySteps) {
			if (parsedData[key]) {
				updateContentSubStep(set, step, 0);
				await apiFetch({
					path: `themegrill-starter-templates/v1/import/content/process/${key}`,
					data: {
						data: parsedData[key],
					},
					method: "POST",
				});
				updateContentSubStep(set, step, 100);
			}
		}

		const postTypeSteps: Array<{
			data: Record<string, unknown>[];
			step: ContentImportStep;
		}> = [
			{ data: attachments, step: "attachments" },
			{ data: pages, step: "pages" },
			{ data: posts, step: "posts" },
			{ data: others, step: "others" },
		];

		for (const { data: postTypeData, step } of postTypeSteps) {
			if (postTypeData.length > 0) {
				updateContentSubStep(set, step, 0);
				const totalChunks = Math.ceil(postTypeData.length / 10);

				for (let i = 0; i < postTypeData.length; i += 10) {
					const chunk = postTypeData.slice(i, i + 10);
					await apiFetch({
						path: "themegrill-starter-templates/v1/import/content/process/posts",
						data: {
							data: chunk,
						},
						method: "POST",
					});

					const currentChunk = Math.floor(i / 10) + 1;
					const progress = Math.round(
						(currentChunk / totalChunks) * 100
					);
					updateContentSubStep(set, step, progress);
				}
				updateContentSubStep(set, step, 100);
			}
		}

		await apiFetch({
			path: "themegrill-starter-templates/v1/import/content/finalize",
			method: "POST",
			data: { data },
		});
		updateContentSubStep(set, "finalize", 100);
	} catch (error) {
		throw new Error("Content import failed");
	}
	return true;
};

export const importThemeMods = async (data: ImportData): Promise<unknown> => {
	const res = apiFetch({
		path: "themegrill-starter-templates/v1/import/theme-mods",
		method: "POST",
		data: {
			"theme-mods": data.themeMods,
			"page-on-front": data.page_on_front,
			"page-for-posts": data.page_for_posts,
			"show-on-front": data.show_on_front,
		},
	});
	return res;
};

export const importWidgets = async (data: ImportData): Promise<unknown> => {
	const res = apiFetch({
		path: "themegrill-starter-templates/v1/import/widgets",
		method: "POST",
		data: {
			widgets: data.widgets,
		},
	});
	return res;
};
