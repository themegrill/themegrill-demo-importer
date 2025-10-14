import { Button } from "@/starter-templates/components/ui/button";
import {
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/starter-templates/components/ui/dialog";
import { SiteDetailForm } from "@/starter-templates/features/sites/components/detail/site-detail-form";
import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";
import { __ } from "@wordpress/i18n";
import { omit } from "lodash";
import { memo } from "react";
import { useFormContext } from "react-hook-form";

export const Ready = memo(() => {
	const goBack = useSiteDetailStore.getState().goBack;
	const startImporting = useSiteDetailStore.getState().startImporting;
	const form = useFormContext<SiteDetailForm>();
	const site = useSiteDetailStore.getState().site!;
	return (
		<>
			<DialogHeader>
				<DialogTitle className="text-center text-[26px] font-semibold leading-[44px]">
					{__("Ready to Import?", "themegrill-demo-importer")}
				</DialogTitle>
				<DialogDescription className="text-center text-[#6B6B6B] text-[15px] leading-[25px]">
					{__(
						"Importing this template adds content to your site and overwrites current theme settings.",
						"themegrill-demo-importer"
					)}
				</DialogDescription>
			</DialogHeader>
			<DialogFooter className="flex flex-col w-[400px] space-x-0 space-y-1 sm:space-x-0 mx-auto justify-center sm:flex-col">
				<Button
					onClick={() => {
						startImporting({
							...form.getValues(),
							...omit(site, ["plugins"]),
						});
					}}
					className="w-full h-[51px] py-0 px-5 text-[15px] font-semibold text-[#FAFBFF]"
				>
					{__("Start Import", "themegrill-demo-importer")}
				</Button>
				<Button
					onClick={goBack}
					className="h-[51px] py-0 px-5 text-[15px] text-[#6B6B6B] hover:bg-gray-100"
					variant="ghost"
				>
					{__("Cancel", "themegrill-demo-importer")}
				</Button>
			</DialogFooter>
		</>
	);
});
