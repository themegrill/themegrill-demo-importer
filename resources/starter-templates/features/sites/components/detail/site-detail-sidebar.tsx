import { Button } from "@/starter-templates/components/ui/button";
import { ScrollArea } from "@/starter-templates/components/ui/scroll-area";
import { Separator } from "@/starter-templates/components/ui/separator";
import {
	SidebarContent,
	SidebarFooter,
	SidebarGroup,
	SidebarHeader,
	Sidebar as SidebarPrimitive,
} from "@/starter-templates/components/ui/sidebar";
import {
	Tooltip,
	TooltipContent,
	TooltipTrigger,
} from "@/starter-templates/components/ui/tooltip";
import { Customize } from "@/starter-templates/features/sites/components/detail/customize";
import { type SiteDetailForm } from "@/starter-templates/features/sites/components/detail/site-detail-form";
import {
	SiteDetailStore,
	useSiteDetailStore,
} from "@/starter-templates/stores/site-detail.store";
import { useRouter } from "@tanstack/react-router";
import { __ } from "@wordpress/i18n";
import { ArrowLeft, Check, Monitor, Smartphone, Tablet } from "lucide-react";
import { memo, useCallback, useMemo } from "react";
import { Controller, useFormContext } from "react-hook-form";
import { useShallow } from "zustand/shallow";

export const SiteDetailSidebar = memo(() => {
	const router = useRouter();
	const site = useSiteDetailStore(useShallow((s) => s.site))!;
	const { goBack, step, setDevice, goToFeatures, goToPaywall, goToReady } =
		useSiteDetailStore(
			useShallow((s) => ({
				goBack: s.goBack,
				step: s.step,
				setDevice: s.setDevice,
				goToReady: s.goToReady,
				goToPaywall: s.goToPaywall,
				goToFeatures: s.goToFeatures,
			}))
		);

	const handleDeviceChange = useCallback(
		(device: SiteDetailStore["device"]) => {
			return () => setDevice(device);
		},
		[]
	);

	const form = useFormContext<SiteDetailForm>();

	const plugins = useMemo(
		() =>
			Object.entries(site.plugins)
				.filter((x) => x[1].name.trim() !== "")
				.sort(([, a], [, b]) => {
					if (a.mandatory && !b.mandatory) return -1;
					if (!a.mandatory && b.mandatory) return 1;
					return 0;
				}),
		[]
	);

	return (
		<SidebarPrimitive className="bg-[#FAFBFC]">
			<SidebarHeader className="p-6">
				<div className="flex items-center justify-between">
					<div>
						<h2 className="text-[#131313] text-xl font-semibold leading-7">
							{step === "initial" && site.title}
							{step !== "initial" &&
								__("Features", "themegrill-demo-importer")}
						</h2>
						<p className="text-[6b6b6b] text-sm">
							{step === "initial" &&
								__(
									"Add your branding: logo, colors & fonts",
									"themegrill-demo-importer"
								)}
							{step !== "initial" &&
								__(
									"Select features that you need for your site",
									"themegrill-demo-importer"
								)}
						</p>
					</div>
					<Tooltip>
						<TooltipTrigger asChild>
							<Button
								className=""
								variant="ghost"
								size="icon"
								aria-label={__(
									"Back to starter templates",
									"themegrill-demo-importer"
								)}
								onClick={() => router.history.back()}
							>
								<ArrowLeft />
							</Button>
						</TooltipTrigger>
						<TooltipContent>
							{__(
								"Back to starter templates",
								"themegrill-demo-importer"
							)}
						</TooltipContent>
					</Tooltip>
				</div>
			</SidebarHeader>
			<div className="px-6">
				<Separator />
			</div>
			<SidebarContent className="gap-0 overflow-hidden">
				<ScrollArea>
					{("initial" === step || step === "paywall") && (
						<SidebarGroup className="p-6">
							<Customize />
						</SidebarGroup>
					)}
					{step !== "initial" && step !== "paywall" && (
						<Controller
							name="plugins"
							control={form.control}
							defaultValue={[]}
							render={({ field: { value = [], onChange } }) => (
								<SidebarGroup className="p-6 space-y-6">
									{plugins.map(([slug, pluginInfo]) => (
										<Button
											data-state={
												pluginInfo.mandatory
													? "active"
													: value.includes(slug)
														? "active"
														: "inactive"
											}
											data-mandatory={
												pluginInfo.mandatory
													? "true"
													: "false"
											}
											key={slug}
											variant="ghost"
											onClick={() =>
												onChange(
													pluginInfo.mandatory
														? value
														: value.includes(slug)
															? value.filter(
																	(v) =>
																		v !==
																		slug
																)
															: [...value, slug]
												)
											}
											className="p-4 bg-white hover:bg-white h-fit flex-col justify-start items-start flex [&_svg]:size-[14px] border-2 border-solid border-border rounded-md group data-[state=active]:border-primary data-[mandatory=true]:pointer-events-none gap-2 data-[mandatory=true]:opacity-65"
										>
											<div className="w-full flex items-center justify-between gap-2">
												<span className="text-base font-semibold">
													{pluginInfo.name}
												</span>
												<div className="size-[18px] text-white inline-grid place-items-center overflow-hidden rounded-full border-2 border-solid border-gray-400 group-data-[state=active]:border-primary group-data-[mandatory=true]:border-gray-400 group-data-[state=active]:bg-primary group-data-[mandatory=true]:bg-gray-400">
													<Check strokeWidth={4} />
												</div>
											</div>
											<p className="w-full text-[13px] whitespace-normal text-left leading-[23px] text-[#545454] opacity-100">
												{pluginInfo.description}
											</p>
										</Button>
									))}
								</SidebarGroup>
							)}
						></Controller>
					)}
				</ScrollArea>
			</SidebarContent>
			<SidebarFooter className="p-6 pb-2">
				<div className="flex flex-col justify-center">
					<Button
						onClick={
							step === "initial"
								? !site.canImport
									? goToPaywall
									: goToFeatures
								: step === "features"
									? goToReady
									: undefined
						}
						className="w-full h-[51px] py-0 px-5 text-[15px] font-semibold text-[#FAFBFF]"
					>
						{step === "features"
							? __("Start Import", "themegrill-demo-importer")
							: __("Continue", "themegrill-demo-importer")}
					</Button>
					{step !== "initial" && step !== "paywall" && (
						<Button
							onClick={goBack}
							className=" h-[51px] py-0 px-5 mt-1 text-[15px] text-[#6B6B6B] hover:bg-gray-100"
							variant="ghost"
						>
							{__("Cancel", "themegrill-demo-importer")}
						</Button>
					)}
					{(step === "initial" || step === "paywall") && (
						<div className="flex h-[51px] items-center justify-center mt-1">
							<Button
								onClick={handleDeviceChange("desktop")}
								className="rounded-sm hover:bg-gray-100"
								variant="ghost"
								size="icon"
							>
								<Monitor size={20} />
								<span className="sr-only">
									{__(
										"Desktop preview",
										"themegrill-demo-importer"
									)}
								</span>
							</Button>
							<Button
								onClick={handleDeviceChange("tablet")}
								className="rounded-sm hover:bg-gray-100"
								variant="ghost"
								size="icon"
							>
								<Tablet size={20} />
								<span className="sr-only">
									{__(
										"Tablet preview",
										"themegrill-demo-importer"
									)}
								</span>
							</Button>
							<Button
								onClick={handleDeviceChange("mobile")}
								className="rounded-sm hover:bg-gray-100"
								variant="ghost"
								size="icon"
							>
								<Smartphone size={20} />
								<span className="sr-only">
									{__(
										"Mobile preview",
										"themegrill-demo-importer"
									)}
								</span>
							</Button>
						</div>
					)}
				</div>
			</SidebarFooter>
		</SidebarPrimitive>
	);
});
