import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogOverlay,
} from "@/starter-templates/components/ui/dialog";
import { useSidebar } from "@/starter-templates/components/ui/sidebar";
import { Importing } from "@/starter-templates/features/sites/components/detail/flow/importing";
import { Paywall } from "@/starter-templates/features/sites/components/detail/flow/paywall";
import { Ready } from "@/starter-templates/features/sites/components/detail/flow/ready";
import { Success } from "@/starter-templates/features/sites/components/detail/flow/success";
import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";
import { __ } from "@wordpress/i18n";
import { X } from "lucide-react";
import { memo, useEffect, useMemo } from "react";
import { useShallow } from "zustand/shallow";

export const FlowDialog = memo(() => {
	const { step, goBack } = useSiteDetailStore(
		useShallow((s) => ({
			step: s.step,
			goBack: s.goBack,
		}))
	);
	const Content = useMemo(() => {
		return () => {
			switch (step) {
				case "ready":
					return <Ready />;
				case "paywall":
					return <Paywall />;
				case "importing":
					return <Importing />;
				case "completed":
					return <Success />;
				default:
					return null;
			}
		};
	}, [step]);
	const sidebar = useSidebar();

	useEffect(() => {
		sidebar.setOpen(!(step === "importing" || step === "completed"));
		return () => {
			sidebar.setOpen(true);
		};
	}, [step]);

	return (
		<Dialog
			open={step !== "initial" && step !== "features"}
			onOpenChange={(v) => {
				if (!v) goBack();
			}}
		>
			<DialogContent
				onEscapeKeyDown={(e) => e.preventDefault()}
				onPointerDownOutside={(e) => e.preventDefault()}
				className="gap-8 py-[50px] px-10"
			>
				<Content />
				{!(step === "importing" || step === "completed") && (
					<DialogClose className="absolute right-4 top-4 rounded-sm disabled:opacity-40 opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground">
						<X className="h-4 w-4" />
						<span className="sr-only">
							{__("Close", "themegrill-demo-importer")}
						</span>
					</DialogClose>
				)}
			</DialogContent>
		</Dialog>
	);
});
