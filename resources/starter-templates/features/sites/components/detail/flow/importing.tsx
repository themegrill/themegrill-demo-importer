import { Button } from "@/starter-templates/components/ui/button";
import {
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/starter-templates/components/ui/dialog";
import { Progress } from "@/starter-templates/components/ui/progress";
import { useShallow } from "zustand/shallow";
import { cn } from "@/starter-templates/lib/utils";
import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";
import { __, sprintf } from "@wordpress/i18n";
import { Info } from "lucide-react";
import { memo } from "react";
import { useFormContext } from "react-hook-form";
import { SiteDetailForm } from "@/starter-templates/features/sites/components/detail/site-detail-form";
import { omit } from "lodash";

const OPERATION_LABELS: Record<string, string> = {
	"installing-plugins": __("Installing Plugins", "themegrill-demo-importer"),
	"importing-content": __("Importing Content", "themegrill-demo-importer"),
	"importing-theme-mods": __(
		"Applying Theme Customizations",
		"themegrill-demo-importer"
	),
	"importing-widgets": __("Configuring Widgets", "themegrill-demo-importer"),
	cleanup: __("Cleaning Up", "themegrill-demo-importer"),
	"getting-log": __("Finalizing Import", "themegrill-demo-importer"),
};

const SUB_OPERATION_LABELS = {
	"importing-content": __("Processing: %s...", "themegrill-demo-importer"),
};

export const Importing = memo(() => {
	const { currentOperation, overallProgress, hasErrors, operations } =
		useSiteDetailStore(useShallow((s) => s.importState));
	const currentSubStep = operations?.["importing-content"]?.currentSubStep;
	const startImporting = useSiteDetailStore.getState().startImporting;
	const form = useFormContext<SiteDetailForm>();
	const site = useSiteDetailStore.getState().site!;
	return (
		<>
			<DialogHeader>
				<DialogTitle className="text-center flex items-baseline justify-center gap-2 text-[26px] font-semibold leading-[44px]">
					{hasErrors ? (
						<>
							<span>
								{__(
									"Import Failed",
									"themegrill-demo-importer"
								)}
							</span>
							<Info size={24} color="#E67E22" />
						</>
					) : (
						<>
							<span>
								{__(
									"Importing your site",
									"themegrill-demo-importer"
								)}
							</span>
							<div className="loader" />
						</>
					)}
				</DialogTitle>
				<DialogDescription className="text-center text-[#6B6B6B] text-[15px] leading-[25px]">
					{hasErrors ? (
						<>
							{__(
								"Unable to import the template. If the problem continues, refer to our ",
								"themegrill-demo-importer"
							)}{" "}
							<a href="#">
								{__(
									"documentation",
									"themegrill-demo-importer"
								)}
							</a>
							.
						</>
					) : (
						__(
							"It might take a couple of minutes. Please do not close or refresh this page.",
							"themegrill-demo-importer"
						)
					)}
				</DialogDescription>
			</DialogHeader>
			<DialogFooter
				className={cn(
					"flex flex-col w-[400px] space-x-0 space-y-4 sm:space-x-0 mx-auto justify-center sm:flex-col",
					hasErrors && "space-y-1"
				)}
			>
				{!hasErrors ? (
					<>
						<div className="relative">
							<Progress
								value={Number(overallProgress.toFixed(0))}
								className="h-[51px] rounded-md"
							/>
							<span className="absolute text-[#6B6B6B] font-semibold left-1/2 top-1/2 -translate-x-1/2 mix-blend-difference -translate-y-1/2">
								{overallProgress.toFixed(0)}%
							</span>
						</div>
						<div>
							{currentOperation && (
								<p className="text-center text-sm text-[#6B6B6B]">
									{OPERATION_LABELS[currentOperation]}
								</p>
							)}
							{currentSubStep && (
								<p className="text-center text-xs text-gray-400">
									{sprintf(
										SUB_OPERATION_LABELS[
											"importing-content"
										],
										currentSubStep
									)}
								</p>
							)}
						</div>
					</>
				) : (
					<>
						<Button
							onClick={() => {
								startImporting(
									{
										...form.getValues(),
										...omit(site, ["plugins"]),
									},
									true
								);
							}}
							className="w-full h-[51px] py-0 px-5 text-[15px] font-semibold text-[#FAFBFF]"
						>
							{__("Try Again", "themegrill-demo-importer")}
						</Button>
						<Button
							onClick={() =>
								useSiteDetailStore.setState({
									step: "features",
								})
							}
							className="h-[51px] py-0 px-5 text-[15px] text-[#6B6B6B] hover:bg-gray-100"
							variant="ghost"
						>
							{__("Cancel", "themegrill-demo-importer")}
						</Button>
					</>
				)}
			</DialogFooter>
		</>
	);
});
