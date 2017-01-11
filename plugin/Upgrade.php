<?php

/**
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2017, Paul Ryley
 * @license   GPLv3
 * @since     2.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews;

use GeminiLabs\SiteReviews\App;
use GeminiLabs\SiteReviews\Database;

class Upgrade
{
	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @var Database
	 */
	protected $db;

	public function __construct( App $app, Database $db )
	{
		$this->app = $app;
		$this->db  = $db;
	}

	/**
	 * Migrate plugin options
	 *
	 * @return void
	 */
	public function options_200()
	{
		$defaults = [
			'last_fetch'            => [],
			'logging'               => 0,
			'settings'              => [],
			'version'               => '',
			'version_upgraded_from' => '',
		];

		$oldOptions = [];

		foreach( $defaults as $key => $fallback ) {
			$optionName = sprintf( '%s_%s', $this->app->prefix, $key );
			$oldOptions[ $key ] = get_option( $optionName, $fallback );
			delete_option( $optionName );
		}

		if( isset( $oldOptions['settings']['form'] )) {
			$oldOptions['settings']['reviews-form'] = $oldOptions['settings']['form'];
			unset( $oldOptions['settings']['form'] );
		}

		$newOptions = get_option( $this->db->getOptionName(), [] );
		$newOptions = array_replace_recursive( $oldOptions, $newOptions );

		update_option( $this->db->getOptionName(), $newOptions );

		$this->db->setDefaults();
	}

	/**
	 * @return void
	 */
	public function sidebarWidgets_200()
	{
		$sidebarWidgets = get_option( 'sidebars_widgets' );

		$sidebarWidgets = $this->replaceWidgetNames_200( $sidebarWidgets );

		update_option( 'sidebars_widgets', $sidebarWidgets );
	}

	/**
	 * @return void
	 */
	public function siteName_200()
	{
		global $wpdb;

		$query = "UPDATE {$wpdb->postmeta} AS pm " .
		"INNER JOIN {$wpdb->posts} AS p ON pm.post_id = p.ID " .
		"SET pm.meta_key = 'review_type' " .
		"WHERE pm.meta_key = 'site_name' " .
		"AND pm.meta_value = 'local' " .
		"AND p.post_type = '{$this->app->post_type}'";

		$wpdb->query( $query );
	}

	/**
	 * @return void
	 */
	public function themeMods_200()
	{
		global $wpdb;

		$themeMods = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%theme_mods_%'" );

		foreach( $themeMods as $theme ) {

			$themeMod = get_option( $theme );

			if( !isset( $themeMod['sidebars_widgets']['data'] ) )continue;

			$themeMod['sidebars_widgets']['data'] = $this->replaceWidgetNames_200( $themeMod['sidebars_widgets']['data'] );

			update_option( $theme, $themeMod );
		}
	}

	/**
	 * @return void
	 */
	public function widgetSiteReviews_200()
	{
		$oldWidget = get_option( "widget_{$this->app->id}recent_reviews" );

		if( !$oldWidget )return;

		foreach( $oldWidget as &$widget ) {

			if( !is_array( $widget ) )continue;

			$hide = [];

			if( $widget['display'] == 'title' ) {
				$hide[] = 'excerpt';
			}
			else if( $widget['display'] == 'excerpt' ) {
				$hide[] = 'title';
			}

			if( isset( $widget['show'] ) ) {
				foreach( ['author','date','rating'] as $value ) {
					if( in_array( 'show_' . $value, $widget['show'] ) )continue;
					$hide[] = $value;
				}
			}

			$widget['count']   = $widget['max_reviews'];
			$widget['display'] = $widget['type'];
			$widget['hide']    = $hide;
			$widget['rating']  = $widget['min_rating'];

			foreach( ['max_reviews','min_rating','order_by','show','type'] as $value ) {
				if( isset( $widget[ $value ] ) ) {
					unset( $widget[ $value ] );
				}
			}
		}

		update_option( "widget_{$this->app->id}_site-reviews", $oldWidget );
		delete_option( "widget_{$this->app->id}recent_reviews" );
	}

	/**
	 * @return void
	 */
	public function widgetSiteReviewsForm_200()
	{
		$oldWidget = get_option( "widget_{$this->app->id}reviews_form" );

		if( !$oldWidget )return;

		foreach( $oldWidget as &$widget ) {

			if( !is_array( $widget ) )continue;

			if( isset( $widget['fields'] ) && is_array( $widget['fields'] ) ) {

				if(( $key = array_search( 'reviewer', $widget['fields'] )) !== false ) {
					$widget['fields'][ $key ] = 'name';
				}

				$widget['hide'] = $widget['fields'];
				unset( $widget['fields'] );
			}
		}

		update_option( "widget_{$this->app->id}_site-reviews-form", $oldWidget );
		delete_option( "widget_{$this->app->id}reviews_form" );
	}

	/**
	 * @return void
	 */
	public function yesNo_200()
	{
		foreach( ['general.require.approval', 'general.require.login'] as $option ) {
			$value = $this->db->getOption( $option, false, 'settings' )
				? 'yes'
				: 'no';

			$this->db->setOption( $option, $value, 'settings' );
		}
	}

	/**
	 * @return array
	 */
	protected function replaceWidgetNames_200( array $widgets )
	{
		foreach( $widgets as &$values ) {

			if( !is_array( $values ) )continue;

			foreach( $values as $index => $widget ) {

				$widget1 = $this->app->id . 'recent_reviews';
				$widget2 = $this->app->id . '_recent_reviews';
				$widget3 = $this->app->id . 'reviews_form';
				$widget4 = $this->app->id . '_reviews_form';

				if( strpos( $widget , $widget1 ) !== false || strpos( $widget , $widget2 ) !== false ) {
					$values[ $index ] = str_replace( [ $widget1, $widget2 ], $this->app->id . '_site-reviews', $widget );
				}

				if( strpos( $widget , $widget3 ) !== false || strpos( $widget , $widget4 ) !== false ) {
					$values[ $index ] = str_replace( [ $widget3, $widget4 ], $this->app->id . '_site-reviews-form', $widget );
				}
			}
		}

		return $widgets;
	}
}