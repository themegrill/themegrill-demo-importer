<?php

namespace ThemeGrill\StarterTemplates\Import\Importers;

use Psr\Log\LoggerInterface;
use ThemeGrill\StarterTemplates\Cache\TransientCache;
use ThemeGrill\StarterTemplates\Import\Contracts\ImporterInterface;
use ThemeGrill\StarterTemplates\Traits\Hooks;

class WidgetsImporter implements ImporterInterface {

	use Hooks;

	public function __construct( private LoggerInterface $logger ) {}

	public function import( array $data ) {
		if ( ! empty( $data ) ) {
			$this->logger->info( 'Starting to import widgets...' );

			$this->doAction( 'themegrill:starter-templates:import-widgets-start', $data );

			global $wp_registered_sidebars;
			$availableWidgets        = $this->getAvailableWidgets();
			$originalSidebarsWidgets = get_option( 'sidebars_widgets' );
			TransientCache::put( 'original_sidebar_widgets', $originalSidebarsWidgets );
			$widgetInstances = $this->getWidgetInstances( $availableWidgets );

			$this->resetWidgets();

			foreach ( $data as $sidebarId => $widgets ) {
				$this->doAction( 'themegrill:starter-templates:import-sidebar', $sidebarId, $widgets );

				if ( 'wp_inactive_widgets' === $sidebarId ) {
					$this->logger->info( 'Skipping inactive widgets...' );
					continue;
				}
				$useSidebarId = $this->determineSidebarId( $sidebarId, $wp_registered_sidebars );
				$this->processWidgetsForSidebar( $widgets, $useSidebarId, $availableWidgets, $widgetInstances );

				$this->doAction( 'themegrill:starter-templates:sidebar-imported', $sidebarId, $widgets );
			}

			$this->doAction( 'themegrill:starter-templates:widgets-import-complete', $data );
		}
		return true;
	}

	private function getWidgetInstances( array $availableWidgets ): array {
		$widgetInstances = [];

		foreach ( $availableWidgets as $widgetData ) {
			$widgetInstances[ $widgetData['id_base'] ] = get_option( 'widget_' . $widgetData['id_base'] );
		}

		return $widgetInstances;
	}

	private function determineSidebarId( string $sidebarId, array $registeredSidebars ): string {
		return isset( $registeredSidebars[ $sidebarId ] ) ? $sidebarId : 'wp_inactive_widgets';
	}

	private function processWidgetsForSidebar(
		array $widgets,
		string $useSidebarId,
		array $availableWidgets,
		array &$widgetInstances
	): void {
		foreach ( $widgets as $widgetInstanceId => $widget ) {
			$widgetInfo = $this->parseWidgetInstanceId( $widgetInstanceId );

			if ( $this->shouldSkipWidget( $widgetInfo['idBase'], $availableWidgets, $widget, $useSidebarId, $widgetInstances ) ) {
				$this->logger->info( "Skipping widget import: {$widgetInfo['idBase']}..." );
				continue;
			}

			$this->importWidget( $widget, $widgetInfo, $useSidebarId );
		}
	}

	private function parseWidgetInstanceId( string $widgetInstanceId ): array {
		$idBase           = preg_replace( '/-[0-9]+$/', '', $widgetInstanceId );
		$instanceIdNumber = str_replace( $idBase . '-', '', $widgetInstanceId );

		return [
			'idBase'           => $idBase,
			'instanceIdNumber' => $instanceIdNumber,
			'fullId'           => $widgetInstanceId,
		];
	}

	private function shouldSkipWidget(
		string $idBase,
		array $availableWidgets,
		array $widget,
		string $useSidebarId,
		array $widgetInstances
	): bool {
		if ( ! isset( $availableWidgets[ $idBase ] ) ) {
			return true;
		}

		if ( ! isset( $widgetInstances[ $idBase ] ) ) {
			return false;
		}

		return $this->isDuplicateWidget( $idBase, $widget, $useSidebarId, $widgetInstances );
	}

	private function isDuplicateWidget(
		string $idBase,
		array $widget,
		string $useSidebarId,
		array $widgetInstances
	): bool {
		$sidebarsWidgets = get_option( 'sidebars_widgets' );
		$sidebarWidgets  = $sidebarsWidgets[ $useSidebarId ] ?? [];

		$singleWidgetInstances = $widgetInstances[ $idBase ] ?? [];

		foreach ( $singleWidgetInstances as $checkId => $checkWidget ) {
			$widgetFullId = "{$idBase}-{$checkId}";

			if ( in_array( $widgetFullId, $sidebarWidgets, true ) && $widget === $checkWidget ) {
				return true;
			}
		}

		return false;
	}

	private function importWidget( array $widget, array $widgetInfo, string $useSidebarId ): void {
		$idBase = $widgetInfo['idBase'];

		$this->logger->info( "Importing widget: $idBase..." );

		$widget = json_decode( wp_json_encode( $widget ), true );

		$singleWidgetInstances = get_option( 'widget_' . $idBase ) ?: [ '_multiwidget' => 1 ]; // phpcs:ignore Universal.Operators.DisallowShortTernary.Found

		$singleWidgetInstances[] = $widget;

		end( $singleWidgetInstances );
		$newInstanceIdNumber = key( $singleWidgetInstances );

		if ( 0 === $newInstanceIdNumber ) {
			$newInstanceIdNumber                           = 1;
			$singleWidgetInstances[ $newInstanceIdNumber ] = $singleWidgetInstances[0];
			unset( $singleWidgetInstances[0] );
		}

		$this->moveMultiwidgetToEnd( $singleWidgetInstances );

		update_option( 'widget_' . $idBase, $singleWidgetInstances );

		$this->assignWidgetToSidebar( $idBase, $newInstanceIdNumber, $useSidebarId );

		$this->logger->info( "Widget: $idBase importer..." );
	}

	private function moveMultiwidgetToEnd( array &$instances ): void {
		if ( ! isset( $instances['_multiwidget'] ) ) {
			return;
		}

		$multiwidget = $instances['_multiwidget'];
		unset( $instances['_multiwidget'] );
		$instances['_multiwidget'] = $multiwidget;
	}

	private function assignWidgetToSidebar( string $idBase, int $instanceIdNumber, string $sidebarId ): void {
		$sidebarsWidgets = get_option( 'sidebars_widgets' ) ?: []; // phpcs:ignore Universal.Operators.DisallowShortTernary.Found

		$newInstanceId                   = "{$idBase}-{$instanceIdNumber}";
		$sidebarsWidgets[ $sidebarId ][] = $newInstanceId;

		update_option( 'sidebars_widgets', $sidebarsWidgets );
	}

	protected function getAvailableWidgets() {
		global $wp_registered_widget_controls;
		return array_reduce(
			array_keys( $wp_registered_widget_controls ),
			static function ( array $acc, string $curr ) use ( $wp_registered_widget_controls ): array {
				$widget = $wp_registered_widget_controls[ $curr ];

				if ( empty( $widget['id_base'] ) || isset( $acc[ $widget['id_base'] ] ) ) {
					return $acc;
				}
				$acc[ $widget['id_base'] ] = [
					'id_base' => $widget['id_base'],
					'name'    => $widget['name'],
				];
				return $acc;
			},
			[]
		);
	}

	private function resetWidgets() {
		$widgets = wp_get_sidebars_widgets();
		foreach ( $widgets as $key => $widgets ) {
			$widgets[ $key ] = array();
		}
		wp_set_sidebars_widgets( $widgets );
	}
}
