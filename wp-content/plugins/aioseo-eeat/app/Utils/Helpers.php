<?php
namespace AIOSEO\Plugin\Addon\Eeat\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models;

/**
 * Contains helper functions
 *
 * @since 1.0.0
 */
class Helpers {
	/**
	 * Gets the data for Vue.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $page The current page.
	 * @return array        The data.
	 */
	public function getVueData( $data = [], $page = null ) {
		if ( 'post' === $page ) {
			return $this->getPostData( $data );
		}

		if ( 'search-appearance' === $page ) {
			return $this->getSearchAppearanceData( $data );
		}

		return $data;
	}

	/**
	 * Gets the data for the post page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The current data.
	 * @return array       The data.
	 */
	private function getPostData( $data ) {
		$postId     = $data['currentPost']['id'];
		$aioseoPost = Models\Post::getPost( $postId );

		// Set the reviewed_by column.
		$data['currentPost']['reviewed_by'] = $aioseoPost->reviewed_by;

		return $data;
	}

	/**
	 * Gets the data for the search appearance page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The current data.
	 * @return array       The data.
	 */
	public function getSearchAppearanceData( $data ) {
		$options = aioseoEeat()->options->all();

		$globalKnowsAbout = $options['eeat']['globalKnowsAbout'] ?: [];
		$globalKnowsAbout = array_map( function( $topic ) {
			return [
				'name'       => aioseo()->helpers->decodeHtmlEntities( $topic['name'] ),
				'url'        => $topic['url'],
				'sameAsUrls' => $topic['sameAsUrls']
			];
		}, $globalKnowsAbout );

		$globalKnowsAbout = array_values( array_filter( $globalKnowsAbout, function( $topic ) {
			return ! empty( $topic['name'] ); // Don't show topics without a name.
		} ) );

		$options['eeat']['globalKnowsAbout'] = $globalKnowsAbout;

		$postTypes = aioseo()->helpers->getPublicPostTypes();
		$postTypes = array_values( array_filter( $postTypes, function( $postType ) {
			if ( in_array( $postType['name'], [ 'download', 'product' ], true ) ) {
				return false;
			}

			return post_type_supports( $postType['name'], 'author' );
		} ) );

		$data['eeat'] = [
			'options'   => $options,
			'postTypes' => $postTypes
		];

		return $data;
	}

	/**
	 * Checks if we are in a dev environment or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether we are in a dev environment or not.
	 */
	public function isDev() {
		return aioseoEeat()->isDev || isset( $_REQUEST['aioseo-dev'] ); // phpcs:ignore HM.Security.NonceVerification.Recommended
	}

	/**
	 * Gets the author meta data for the given user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $userId The user ID.
	 * @return array       The author meta data.
	 */
	public function getAuthorMetaData( $userId = 0 ) {
		if ( ! $userId ) {
			global $user_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			if ( ! intval( $user_id ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
				return [];
			}

			$userId = $user_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		}

		static $data = [];
		if ( isset( $data[ $userId ] ) ) {
			return $data[ $userId ];
		}

		$authorMetaData = get_user_meta( $userId, 'aioseo_author_meta_data', true );
		$authorMetaData = $this->migrateOldData( $authorMetaData, $userId );

		$globalKnowsAbout = aioseoEeat()->options->eeat->globalKnowsAbout ?: [];
		$globalKnowsAbout = array_values( array_filter( array_map( function( $topic ) {
			return aioseo()->helpers->decodeHtmlEntities( $topic['name'] );
		}, $globalKnowsAbout ) ) );

		$knowsAbout = ! empty( $authorMetaData['knowsAbout'] ) ? ( json_decode( $authorMetaData['knowsAbout'] ?? '', true ) ?: [] ) : [];
		$knowsAbout = array_map( function( $topic ) {
			return [
				'label' => aioseo()->helpers->decodeHtmlEntities( $topic['label'] ),
				'value' => aioseo()->helpers->decodeHtmlEntities( $topic['value'] )
			];
		}, $knowsAbout );
		$knowsAbout = array_values( array_filter( $knowsAbout, function( $topic ) use ( $globalKnowsAbout ) {
			return in_array( $topic['value'], $globalKnowsAbout, true );
		} ) );

		$authorMetaData = aioseo()->helpers->sanitize( $authorMetaData, false, [ 'authorBio' ] );

		$normalizedMetaData = [
			'enabled'                => isset( $authorMetaData['enabled'] ) ? (bool) $authorMetaData['enabled'] : true,
			'alumniOf'               => $authorMetaData['alumniOf'] ?? [],
			'jobTitle'               => $authorMetaData['jobTitle'] ?? '',
			'worksFor'               => $authorMetaData['worksFor'] ?? '',
			'knowsAbout'             => $knowsAbout,
			'award'                  => $authorMetaData['award'] ?? [],
			'knowsLanguage'          => $authorMetaData['knowsLanguage'] ?? [],
			'authorExcerpt'          => $authorMetaData['authorExcerpt'] ?? '#author_bio',
			'authorBio'              => wp_kses_post( $authorMetaData['authorBio'] ?? '' ),
			'authorImage'            => esc_url( $authorMetaData['authorImage'] ?? '' ),
			'authorCustomUrl'        => ! empty( $authorMetaData['authorCustomUrl'] ) ? esc_url( $authorMetaData['authorCustomUrl'] ) : '',
			'injectAuthorBioDefault' => isset( $authorMetaData['injectAuthorBioDefault'] ) ? (bool) $authorMetaData['injectAuthorBioDefault'] : true,
			'injectAuthorBio'        => isset( $authorMetaData['injectAuthorBio'] ) ? (bool) $authorMetaData['injectAuthorBio'] : false
		];

		$data[ $userId ] = $normalizedMetaData;

		return $normalizedMetaData;
	}

	/**
	 * Migrates old author metadata to new formats.
	 *
	 * @since 1.1.0
	 *
	 * @param  array $authorMetaData The author meta data.
	 * @param  int   $userId         The user ID.
	 * @return array                 The migrated author meta data.
	 */
	private function migrateOldData( $authorMetaData, $userId ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! is_array( $authorMetaData ) ) {
			$authorMetaData = [];
		}

		if ( ! empty( $authorMetaData['alumniOf'] ) && is_string( $authorMetaData['alumniOf'] ) ) {
			$authorMetaData['alumniOf'] = [
				[
					'name' => sanitize_text_field( $authorMetaData['alumniOf'] ),
					'url'  => isset( $authorMetaData['alumniOfUrl'] ) ? sanitize_text_field( $authorMetaData['alumniOfUrl'] ) : ''
				]
			];
		}

		return $authorMetaData;
	}

	/**
	 * Returns SVG data URLs with social icons.
	 *
	 * @since 1.0.0
	 *
	 * @return array The SVG data URLs.
	 */
	public function getSocialIcons() {
		// phpcs:disable Generic.Files.LineLength.MaxExceeded
		$icons = [
			'facebookPageUrl' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzM0M18xMDE2KSI+CjxwYXRoIGQ9Ik03Ljk5OTk5IDBDMTIuNDE4MyAwIDE2IDMuNTgxNzMgMTYgNy45OTk5OUMxNiAxMi4wOTAyIDEyLjkzMDMgMTUuNDYzIDguOTY5MjEgMTUuOTQxNFYxMC40NDQ3TDExLjEzMzQgMTAuNDQ0N0wxMS41ODIzIDhIOC45NjkyMVY3LjEzNTM5QzguOTY5MjEgNi40ODk0NSA5LjA5NTkxIDYuMDQyMjYgOS4zODY1NyA1Ljc1NjU2QzkuNjc3MjYgNS40NzA4NCAxMC4xMzE5IDUuMzQ2NjIgMTAuNzg3OCA1LjM0NjYyQzEwLjk1MzggNS4zNDY2MiAxMS4xMDY2IDUuMzQ4MjcgMTEuMjQyMiA1LjM1MTU3QzExLjQzOTQgNS4zNTYzOCAxMS42MDAxIDUuMzY0NjcgMTEuNzEyIDUuMzc2NDRWMy4xNjAzMkMxMS42NjczIDMuMTQ3ODkgMTEuNjE0NSAzLjEzNTQ3IDExLjU1NTQgMy4xMjMyNEMxMS40MjE0IDMuMDk1NTQgMTEuMjU0OCAzLjA2ODgzIDExLjA3NTcgMy4wNDUzN0MxMC43MDE2IDIuOTk2MzYgMTAuMjcyOSAyLjk2MTU0IDkuOTcyOTIgMi45NjE1NEM4Ljc2MTYgMi45NjE1NCA3Ljg0NjE0IDMuMjIwNjggNy4yMDcxMyAzLjc1NzQ2QzYuNDM1OTIgNC40MDUyNyA2LjA2NzM5IDUuNDU3NDggNi4wNjczOSA2Ljk0NjU5VjcuOTk5OTlINC40MTc3MlYxMC40NDQ3SDYuMDY3MzlWMTUuNzY0NEMyLjU4Mjg4IDE0Ljg5OTkgMCAxMS43NTE4IDAgNy45OTk5OUMwIDMuNTgxNzMgMy41ODE3MyAwIDcuOTk5OTkgMFoiIGZpbGw9IiM0MzQ5NjAiLz4KPC9nPgo8ZGVmcz4KPGNsaXBQYXRoIGlkPSJjbGlwMF8zNDNfMTAxNiI+CjxyZWN0IHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0id2hpdGUiLz4KPC9jbGlwUGF0aD4KPC9kZWZzPgo8L3N2Zz4K',
			'instagramUrl'    => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyLjI2MzUgNC42ODE0MUMxMS43MzM3IDQuNjgyNDUgMTEuMzAyOSA0LjI1MzQ3IDExLjMwMTggMy43MjM2NUMxMS4zMDA4IDMuMTkzODQgMTEuNzI5OCAyLjc2MzA1IDEyLjI1OTggMi43NjIwMUMxMi43ODk5IDIuNzYwOTcgMTMuMjIwNyAzLjE5MDIxIDEzLjIyMTcgMy43MjAwMkMxMy4yMjI1IDQuMjQ5ODQgMTIuNzkzNSA0LjY4MDM4IDEyLjI2MzUgNC42ODE0MVoiIGZpbGw9IiM0MzQ5NjAiLz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik04LjAwNzY1IDEyLjEwNzNDNS43MzkzOSAxMi4xMTE3IDMuODk2NzMgMTAuMjc2NiAzLjg5MjM0IDguMDA3NzdDMy44ODc5MiA1LjczOTQ4IDUuNzIzNTcgMy44OTY1NCA3Ljk5MTg0IDMuODkyMTNDMTAuMjYwNiAzLjg4NzczIDEyLjEwMzUgNS43MjM5MyAxMi4xMDc5IDcuOTkxOTdDMTIuMTEyMyAxMC4yNjA4IDEwLjI3NjIgMTIuMTAyOSA4LjAwNzY1IDEyLjEwNzNaTTcuOTk0NjkgNS4zMzM1N0M2LjUyMjQ0IDUuMzM2MTYgNS4zMzA2MyA2LjUzMjM5IDUuMzMzMjIgOC4wMDQ5MkM1LjMzNjA3IDkuNDc3NzIgNi41MzI1NSAxMC42NjkzIDguMDA0OCAxMC42NjY0QzkuNDc3NTggMTAuNjYzNiAxMC42Njk0IDkuNDY3NjEgMTAuNjY2NSA3Ljk5NDgxQzEwLjY2MzcgNi41MjIwMiA5LjQ2NzIyIDUuMzMwNzIgNy45OTQ2OSA1LjMzMzU3WiIgZmlsbD0iIzQzNDk2MCIvPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTIuNzQ2MTQgMC40MzA5ODZDMy4yNTQxNyAwLjIzMTkxNyAzLjgzNTU2IDAuMDk1NTc0OCA0LjY4Njc3IDAuMDU1Mzk4OEM1LjU0MDA3IDAuMDE0NDQ4NCA1LjgxMjQ4IDAuMDA1MTE0MDEgNy45ODQ1OCAwLjAwMDk2ODQxNEMxMC4xNTcyIC0wLjAwMzE3NzE5IDEwLjQyOTYgMC4wMDUxMTI0MSAxMS4yODI5IDAuMDQyOTU1NkMxMi4xMzQ0IDAuMDc5NzYyIDEyLjcxNiAwLjIxNDAzMiAxMy4yMjUxIDAuNDExMDI5QzEzLjc1MTggMC42MTM5ODMgMTQuMTk4NyAwLjg4NzE4OCAxNC42NDQgMS4zMzA2OUMxNS4wODkzIDEuNzc0NyAxNS4zNjM1IDIuMjIwMDEgMTUuNTY5IDIuNzQ1OTRDMTUuNzY3OSAzLjI1NDUgMTUuOTA0MiAzLjgzNTM3IDE1Ljk0NDYgNC42ODcxMkMxNS45ODUxIDUuNTQwMTUgMTUuOTk0OSA1LjgxMjMxIDE1Ljk5OTEgNy45ODQ3QzE2LjAwMzIgMTAuMTU2OCAxNS45OTQ0IDEwLjQyOTUgMTUuOTU3MSAxMS4yODMzQzE1LjkyIDEyLjEzNDMgMTUuNzg2IDEyLjcxNjIgMTUuNTg5IDEzLjIyNUMxNS4zODU1IDEzLjc1MTcgMTUuMTEyOCAxNC4xOTg2IDE0LjY2OTQgMTQuNjQzOUMxNC4yMjU5IDE1LjA4OTUgMTMuNzggMTUuMzYzNSAxMy4yNTQxIDE1LjU2OTNDMTIuNzQ1NiAxNS43Njc4IDEyLjE2NDcgMTUuOTA0MiAxMS4zMTM1IDE1Ljk0NDlDMTAuNDYwMiAxNS45ODUzIDEwLjE4NzggMTUuOTk0OSA4LjAxNDkxIDE1Ljk5OUM1Ljg0MzA2IDE2LjAwMzIgNS41NzA2NSAxNS45OTQ5IDQuNzE3MzcgMTUuOTU3M0MzLjg2NTg4IDE1LjkyIDMuMjgzOTggMTUuNzg2IDIuNzc1MTcgMTUuNTg5MkMyLjI0ODQ4IDE1LjM4NTUgMS44MDE2MSAxNS4xMTMxIDEuMzU2MyAxNC42NjkzQzAuOTEwNzQgMTQuMjI1NiAwLjYzNjI1NyAxMy43OCAwLjQzMDk3MiAxMy4yNTQxQzAuMjMxOTA1IDEyLjc0NTggMC4wOTYwNzE0IDEyLjE2NDYgMC4wNTUzODE4IDExLjMxMzdDMC4wMTQ2OTA2IDEwLjQ2MDEgMC4wMDUxMDMzOCAxMC4xODc0IDAuMDAwOTU5Mzc2IDguMDE1MjlDLTAuMDAzMjAwNjIgNS44NDI5IDAuMDA1MzU3NzggNS41NzA3NCAwLjA0MjY3NzggNC43MTc0NEMwLjA4MDI2NjYgMy44NjU3IDAuMjEzNzU4IDMuMjg0MDQgMC40MTA3NTMgMi43NzQ3MUMwLjYxNDIxOSAyLjI0ODI2IDAuODg2ODk3IDEuODAxNjUgMS4zMzA5MSAxLjM1NjA5QzEuNzc0NCAwLjkxMDc3NiAyLjIyMDIyIDAuNjM2MDEyIDIuNzQ2MTQgMC40MzA5ODZaTTMuMjk0MzYgMTQuMjQ1M0MzLjU3NjYzIDE0LjM1MzkgNC4wMDAxNSAxNC40ODM1IDQuNzgwMDkgMTQuNTE3NEM1LjYyNDA1IDE0LjU1NCA1Ljg3Njc2IDE0LjU2MiA4LjAxMjMyIDE0LjU1NzlDMTAuMTQ4NiAxNC41NTQgMTAuNDAxNCAxNC41NDQ5IDExLjI0NTEgMTQuNTA1MkMxMi4wMjQyIDE0LjQ2ODIgMTIuNDQ3OCAxNC4zMzcgMTIuNzI5MiAxNC4yMjcxQzEzLjEwMjggMTQuMDgxMiAxMy4zNjg3IDEzLjkwNjcgMTMuNjQ4MSAxMy42MjcxQzEzLjkyNzUgMTMuMzQ2MyAxNC4xMDA0IDEzLjA3OTYgMTQuMjQ1MSAxMi43MDYxQzE0LjM1MzkgMTIuNDIzNiAxNC40ODMzIDExLjk5OTggMTQuNTE3MiAxMS4yMTk4QzE0LjU1NDMgMTAuMzc2NCAxNC41NjIgMTAuMTIzNCAxNC41NTc5IDcuOTg3M0MxNC41NTQgNS44NTE3MSAxNC41NDQ5IDUuNTk4NzMgMTQuNTA0OCA0Ljc1NTAzQzE0LjQ2OCAzLjk3NTYgMTQuMzM3MSAzLjU1MjA1IDE0LjIyNjkgMy4yNzA4MkMxNC4wODEgMi44OTY3OSAxMy45MDcxIDIuNjMxMzcgMTMuNjI2NiAyLjM1MTY5QzEzLjM0NjEgMi4wNzIgMTMuMDc5NCAxLjg5OTYzIDEyLjcwNTQgMS43NTVDMTIuNDIzNiAxLjY0NTg4IDExLjk5OTYgMS41MTY3OSAxMS4yMjAyIDEuNDgyODRDMTAuMzc2MiAxLjQ0NTc3IDEwLjEyMzIgMS40MzgyNSA3Ljk4NzE3IDEuNDQyNEM1Ljg1MTYyIDEuNDQ2NTUgNS41OTg5MSAxLjQ1NTEgNC43NTUyMSAxLjQ5NTAyQzMuOTc1NTMgMS41MzIwOCAzLjU1MjUxIDEuNjYyOTggMy4yNzA1IDEuNzczMTRDMi44OTcyNSAxLjkxOTA3IDIuNjMxMzEgMi4wOTI0OCAyLjM1MTY0IDIuMzczMkMyLjA3MjQ3IDIuNjUzOTEgMS44OTk1OSAyLjkyMDEyIDEuNzU0OTYgMy4yOTQ0MUMxLjY0NjYyIDMuNTc2NDMgMS41MTYyMyA0LjAwMDQ4IDEuNDgyOCA0Ljc3OTkxQzEuNDQ1OTkgNS42MjM4NyAxLjQzODIyIDUuODc2ODYgMS40NDIzNiA4LjAxMjQ0QzEuNDQ2MjYgMTAuMTQ4NSAxLjQ1NTMyIDEwLjQwMTUgMS40OTQ5OCAxMS4yNDQ3QzEuNTMxNTMgMTIuMDI0NyAxLjY2MzQ1IDEyLjQ0NzcgMS43NzMxMSAxMi43Mjk5QzEuOTE5MDMgMTMuMTAyOSAyLjA5Mjk2IDEzLjM2ODkgMi4zNzMxNSAxMy42NDg2QzIuNjUzODcgMTMuOTI3MiAyLjkyMDU5IDE0LjEwMDYgMy4yOTQzNiAxNC4yNDUzWiIgZmlsbD0iIzQzNDk2MCIvPgo8L3N2Zz4K',
			'twitterUrl'      => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyLjIxNzUgMS4yNjkyOUgxNC40NjY1TDkuNTUzMSA2Ljg4NDk1TDE1LjMzMzMgMTQuNTI2NkgxMC44MDc1TDcuMjYyNjUgOS44OTE5OEwzLjIwNjU5IDE0LjUyNjZIMC45NTYyNDdMNi4yMTE1OCA4LjUyMDAyTDAuNjY2NjI2IDEuMjY5MjlINS4zMDczN0w4LjUxMTU2IDUuNTA1NTFMMTIuMjE3NSAxLjI2OTI5Wk0xMS40MjgyIDEzLjE4MDVIMTIuNjc0NEw0LjYzMDIyIDIuNTQ0NzFIMy4yOTI5M0wxMS40MjgyIDEzLjE4MDVaIiBmaWxsPSIjNDM0OTYwIi8+Cjwvc3ZnPgo=',
			'linkedinUrl'     => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzM0M185OTUpIj4KPHBhdGggZD0iTTE0LjgxNTYgMEgxLjE4MTI1QzAuNTI4MTI1IDAgMCAwLjUxNTYyNSAwIDEuMTUzMTNWMTQuODQzOEMwIDE1LjQ4MTMgMC41MjgxMjUgMTYgMS4xODEyNSAxNkgxNC44MTU2QzE1LjQ2ODggMTYgMTYgMTUuNDgxMyAxNiAxNC44NDY5VjEuMTUzMTNDMTYgMC41MTU2MjUgMTUuNDY4OCAwIDE0LjgxNTYgMFpNNC43NDY4NyAxMy42MzQ0SDIuMzcxODhWNS45OTY4N0g0Ljc0Njg3VjEzLjYzNDRaTTMuNTU5MzggNC45NTYyNUMyLjc5Njg4IDQuOTU2MjUgMi4xODEyNSA0LjM0MDYyIDIuMTgxMjUgMy41ODEyNUMyLjE4MTI1IDIuODIxODggMi43OTY4OCAyLjIwNjI1IDMuNTU5MzggMi4yMDYyNUM0LjMxODc1IDIuMjA2MjUgNC45MzQzNyAyLjgyMTg4IDQuOTM0MzcgMy41ODEyNUM0LjkzNDM3IDQuMzM3NSA0LjMxODc1IDQuOTU2MjUgMy41NTkzOCA0Ljk1NjI1Wk0xMy42MzQ0IDEzLjYzNDRIMTEuMjYyNVY5LjkyMTg4QzExLjI2MjUgOS4wMzc1IDExLjI0NjkgNy44OTY4NyAxMC4wMjgxIDcuODk2ODdDOC43OTM3NSA3Ljg5Njg3IDguNjA2MjUgOC44NjI1IDguNjA2MjUgOS44NTkzOFYxMy42MzQ0SDYuMjM3NVY1Ljk5Njg3SDguNTEyNVY3LjA0MDYzSDguNTQzNzVDOC44NTkzNyA2LjQ0MDYzIDkuNjM0MzggNS44MDYyNSAxMC43ODc1IDUuODA2MjVDMTMuMTkwNiA1LjgwNjI1IDEzLjYzNDQgNy4zODc1IDEzLjYzNDQgOS40NDM3NVYxMy42MzQ0VjEzLjYzNDRaIiBmaWxsPSIjNDM0OTYwIi8+CjwvZz4KPGRlZnM+CjxjbGlwUGF0aCBpZD0iY2xpcDBfMzQzXzk5NSI+CjxyZWN0IHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0id2hpdGUiLz4KPC9jbGlwUGF0aD4KPC9kZWZzPgo8L3N2Zz4K',
			'pinterestUrl'    => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzM0M18xMDAxKSI+CjxwYXRoIGQ9Ik04IDBDMy41ODE4OSAwIDAgMy41ODE4OSAwIDhDMCAxMS4zOTA5IDIuMTA3IDE0LjI4ODEgNS4wODMxMyAxNS40NTM1QzUuMDEwNyAxNC44MjE0IDQuOTUxNDQgMTMuODQ2OSA1LjEwOTQ2IDEzLjE1NTZDNS4yNTQzMiAxMi41MyA2LjA0NDQ0IDkuMTc4NiA2LjA0NDQ0IDkuMTc4NkM2LjA0NDQ0IDkuMTc4NiA1LjgwNzQxIDguNjk3OTQgNS44MDc0MSA3Ljk5MzQyQzUuODA3NDEgNi44ODA2NiA2LjQ1MjY3IDYuMDUxMDMgNy4yNTU5NyA2LjA1MTAzQzcuOTQwNzQgNi4wNTEwMyA4LjI2OTk2IDYuNTY0NjEgOC4yNjk5NiA3LjE3Njk2QzguMjY5OTYgNy44NjE3MyA3LjgzNTM5IDguODg4ODkgNy42MDQ5NCA5Ljg0MzYyQzcuNDEzOTkgMTAuNjQwMyA4LjAwNjU4IDExLjI5MjIgOC43OTAxMiAxMS4yOTIyQzEwLjIxMjMgMTEuMjkyMiAxMS4zMDUzIDkuNzkwOTUgMTEuMzA1MyA3LjYzMTI4QzExLjMwNTMgNS43MTUyMyA5LjkyOTIyIDQuMzc4NiA3Ljk2MDQ5IDQuMzc4NkM1LjY4MjMgNC4zNzg2IDQuMzQ1NjggNi4wODM5NSA0LjM0NTY4IDcuODQ4NTZDNC4zNDU2OCA4LjUzMzMzIDQuNjA5MDUgOS4yNzA3OCA0LjkzODI3IDkuNjcyNDNDNS4wMDQxMSA5Ljc1MTQ0IDUuMDEwNyA5LjgyMzg3IDQuOTkwOTUgOS45MDI4OEM0LjkzMTY5IDEwLjE1MzEgNC43OTM0MiAxMC42OTk2IDQuNzY3MDggMTAuODExNUM0LjczNDE2IDEwLjk1NjQgNC42NDg1NiAxMC45ODkzIDQuNDk3MTIgMTAuOTE2OUMzLjQ5NjMgMTAuNDQ5NCAyLjg3MDc4IDguOTk0MjQgMi44NzA3OCA3LjgxNTY0QzIuODcwNzggNS4yOTM4MyA0LjcwMTIzIDIuOTc2MTMgOC4xNTgwMiAyLjk3NjEzQzEwLjkzIDIuOTc2MTMgMTMuMDg5NyA0Ljk1MTQ0IDEzLjA4OTcgNy41OTgzNUMxMy4wODk3IDEwLjM1NzIgMTEuMzUxNCAxMi41NzYxIDguOTQxNTYgMTIuNTc2MUM4LjEzMTY5IDEyLjU3NjEgNy4zNjc5IDEyLjE1NDcgNy4xMTExMSAxMS42NTQzQzcuMTExMTEgMTEuNjU0MyA2LjcwOTQ3IDEzLjE4MTkgNi42MTA3IDEzLjU1NzJDNi40MzI5MiAxNC4yNTUxIDUuOTQ1NjggMTUuMTI0MyA1LjYxNjQ2IDE1LjY1NzZDNi4zNjcwOCAxNS44ODgxIDcuMTU3MiAxNi4wMTMyIDcuOTg2ODMgMTYuMDEzMkMxMi40MDQ5IDE2LjAxMzIgMTUuOTg2OCAxMi40MzEzIDE1Ljk4NjggOC4wMTMxN0MxNiAzLjU4MTg5IDEyLjQxODEgMCA4IDBaIiBmaWxsPSIjNDM0OTYwIi8+CjwvZz4KPGRlZnM+CjxjbGlwUGF0aCBpZD0iY2xpcDBfMzQzXzEwMDEiPgo8cmVjdCB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIGZpbGw9IndoaXRlIi8+CjwvY2xpcFBhdGg+CjwvZGVmcz4KPC9zdmc+Cg==',
			'tiktokUrl'       => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwLjgyMjkgMUg4LjQ1NTNWMTAuNTM2MkM4LjQ1NTMgMTEuNjcyNSA3LjU0NDcgMTIuNjA1OCA2LjQxMTQ3IDEyLjYwNThDNS4yNzgyNSAxMi42MDU4IDQuMzY3NjIgMTEuNjcyNSA0LjM2NzYyIDEwLjUzNjJDNC4zNjc2MiA5LjQyMDMgNS4yNTgwMSA4LjUwNzIzIDYuMzUwNzcgOC40NjY2N1Y2LjA3MjQ3QzMuOTQyNjYgNi4xMTMwMyAyIDguMDgxMTYgMiAxMC41MzYyQzIgMTMuMDExNiAzLjk4MzE0IDE1IDYuNDMxNzIgMTVDOC44ODAyNiAxNSAxMC44NjM0IDEyLjk5MTMgMTAuODYzNCAxMC41MzYyVjUuNjQ2MzdDMTEuNzUzOCA2LjI5NTY2IDEyLjg0NjUgNi42ODExNiAxNCA2LjcwMTQ2VjQuMzA3MjVDMTIuMjE5MiA0LjI0NjM4IDEwLjgyMjkgMi43ODU1MSAxMC44MjI5IDFaIiBmaWxsPSIjNDM0OTYwIi8+Cjwvc3ZnPgo=',
			'tumblrUrl'       => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyLjE0MjkgMTZIOS40NDM1NEM3LjAxNTQ2IDE2IDUuMjA2MDQgMTQuNzYxOCA1LjIwNjA0IDExLjgwMDRWNy4wNTc0MUgzVjQuNDg4NzFDNS40Mjc2NSAzLjg2NDM4IDYuNDQzMzcgMS43OTM3NCA2LjU2MDA1IDBIOS4wODA4N1Y0LjA3MjQ5SDEyLjAyMjdWNy4wNTc0MUg5LjA4MTMxVjExLjE4NzRDOS4wODEzMSAxMi40MjUxIDkuNzExNzMgMTIuODUzMSAxMC43MTUzIDEyLjg1MzFIMTIuMTM5NEwxMi4xNDI5IDE2WiIgZmlsbD0iIzQzNDk2MCIvPgo8L3N2Zz4K',
			'youtubeUrl'      => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzM0M18xMDA0KSI+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNMTUuMTY0MyAzLjIyMjc1QzE1LjQxMjYgMy40NzI1OSAxNS41OTA5IDMuNzgzMjYgMTUuNjgxMyA0LjEyMzY1QzE2LjAxNTkgNS4zODAwMSAxNi4wMTU5IDguMDAwMDEgMTYuMDE1OSA4LjAwMDAxQzE2LjAxNTkgOC4wMDAwMSAxNi4wMTU5IDEwLjYyIDE1LjY4MTMgMTEuODc2NEMxNS41OTA5IDEyLjIxNjggMTUuNDEyNiAxMi41Mjc0IDE1LjE2NDMgMTIuNzc3M0MxNC45MTYxIDEzLjAyNzEgMTQuNjA2NiAxMy4yMDc0IDE0LjI2NjggMTMuM0MxMy4wMTU5IDEzLjYzNjQgOC4wMTU4NyAxMy42MzY0IDguMDE1ODcgMTMuNjM2NEM4LjAxNTg3IDEzLjYzNjQgMy4wMTU4NyAxMy42MzY0IDEuNzY0OTYgMTMuM0MxLjQyNTE2IDEzLjIwNzQgMS4xMTU2NCAxMy4wMjcxIDAuODY3Mzk0IDEyLjc3NzNDMC42MTkxNDcgMTIuNTI3NCAwLjQ0MDg3MyAxMi4yMTY4IDAuMzUwNDE1IDExLjg3NjRDMC4wMTU4NjkyIDEwLjYyIDAuMDE1ODY5MSA4LjAwMDAxIDAuMDE1ODY5MSA4LjAwMDAxQzAuMDE1ODY5MSA4LjAwMDAxIDAuMDE1ODY5MiA1LjM4MDAxIDAuMzUwNDE1IDQuMTIzNjVDMC40NDA4NzMgMy43ODMyNiAwLjYxOTE0NyAzLjQ3MjU5IDAuODY3Mzk0IDMuMjIyNzVDMS4xMTU2NCAyLjk3MjkxIDEuNDI1MTYgMi43OTI2NSAxLjc2NDk2IDIuNzAwMDFDMy4wMTU4NyAyLjM2MzY1IDguMDE1ODcgMi4zNjM2NSA4LjAxNTg3IDIuMzYzNjVDOC4wMTU4NyAyLjM2MzY1IDEzLjAxNTkgMi4zNjM2NSAxNC4yNjY4IDIuNzAwMDFDMTQuNjA2NiAyLjc5MjY1IDE0LjkxNjEgMi45NzI5MSAxNS4xNjQzIDMuMjIyNzVaTTEwLjU2MTMgOC4wMDAwM0w2LjM3OTUyIDUuNjIwOTRWMTAuMzc5MUwxMC41NjEzIDguMDAwMDNaIiBmaWxsPSIjNDM0OTYwIi8+CjwvZz4KPGRlZnM+CjxjbGlwUGF0aCBpZD0iY2xpcDBfMzQzXzEwMDQiPgo8cmVjdCB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIGZpbGw9IndoaXRlIi8+CjwvY2xpcFBhdGg+CjwvZGVmcz4KPC9zdmc+Cg==',
			'wordPressUrl'    => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNiAyNiIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2Ij4KICAgIDxwYXRoCiAgICAgICAgZmlsbD0iIzQzNDk2MCIKICAgICAgICBkPSJNIDEzLjAzMTI1IDAgQyA1Ljg4MjgxMyAwIDAuMDYyNSA1LjgyMDMxMyAwLjA2MjUgMTIuOTY4NzUgQyAwLjA2MjUgMjAuMTE3MTg4IDUuODgyODEzIDI1LjkzNzUgMTMuMDMxMjUgMjUuOTM3NSBDIDIwLjE3OTY4OCAyNS45Mzc1IDI2IDIwLjExNzE4OCAyNiAxMi45Njg3NSBDIDI2IDUuODIwMzEzIDIwLjE3OTY4OCAwIDEzLjAzMTI1IDAgWiBNIDEzLjAzMTI1IDAuNTkzNzUgQyAxOS44NTE1NjMgMC41OTM3NSAyNS40MDYyNSA2LjE0ODQzOCAyNS40MDYyNSAxMi45Njg3NSBDIDI1LjQwNjI1IDE5Ljc4OTA2MyAxOS44NTE1NjMgMjUuMzQzNzUgMTMuMDMxMjUgMjUuMzQzNzUgQyA2LjIxMDkzOCAyNS4zNDM3NSAwLjY1NjI1IDE5Ljc4OTA2MyAwLjY1NjI1IDEyLjk2ODc1IEMgMC42NTYyNSA2LjE0ODQzOCA2LjIxMDkzOCAwLjU5Mzc1IDEzLjAzMTI1IDAuNTkzNzUgWiBNIDEzLjAzMTI1IDEuODQzNzUgQyA5LjE0NDUzMSAxLjg0Mzc1IDUuNzM4MjgxIDMuODI0MjE5IDMuNzUgNi44NDM3NSBDIDQuMDExNzE5IDYuODUxNTYzIDQuMjU3ODEzIDYuODc1IDQuNDY4NzUgNi44NzUgQyA1LjYzMjgxMyA2Ljg3NSA3LjQzNzUgNi43MTg3NSA3LjQzNzUgNi43MTg3NSBDIDguMDM1MTU2IDYuNjgzNTk0IDguMDk3NjU2IDcuNTg1OTM4IDcuNSA3LjY1NjI1IEMgNy41IDcuNjU2MjUgNi44ODY3MTkgNy43MTQ4NDQgNi4yMTg3NSA3Ljc1IEwgMTAuMjgxMjUgMTkuNzgxMjUgTCAxMi43MTg3NSAxMi41IEwgMTAuOTY4NzUgNy43NSBDIDEwLjM3MTA5NCA3LjcxNDg0NCA5LjgxMjUgNy42NTYyNSA5LjgxMjUgNy42NTYyNSBDIDkuMjEwOTM4IDcuNjIxMDk0IDkuMjc3MzQ0IDYuNjgzNTk0IDkuODc1IDYuNzE4NzUgQyA5Ljg3NSA2LjcxODc1IDExLjcxODc1IDYuODc1IDEyLjgxMjUgNi44NzUgQyAxMy45NzY1NjMgNi44NzUgMTUuNzgxMjUgNi43MTg3NSAxNS43ODEyNSA2LjcxODc1IEMgMTYuMzgyODEzIDYuNjgzNTk0IDE2LjQ0MTQwNiA3LjU4NTkzOCAxNS44NDM3NSA3LjY1NjI1IEMgMTUuODQzNzUgNy42NTYyNSAxNS4yMzA0NjkgNy43MTQ4NDQgMTQuNTYyNSA3Ljc1IEwgMTguNTkzNzUgMTkuNjg3NSBMIDE5LjY4NzUgMTYgQyAyMC4yNSAxNC41NTQ2ODggMjAuNTMxMjUgMTMuMzU5Mzc1IDIwLjUzMTI1IDEyLjQwNjI1IEMgMjAuNTMxMjUgMTEuMDMxMjUgMjAuMDUwNzgxIDEwLjA4MjAzMSAxOS42MjUgOS4zNDM3NSBDIDE5LjA2MjUgOC40MjU3ODEgMTguNTMxMjUgNy42MzY3MTkgMTguNTMxMjUgNi43MTg3NSBDIDE4LjUzMTI1IDUuNjk5MjE5IDE5LjMxMjUgNC43NSAyMC40MDYyNSA0Ljc1IEMgMjAuNDU3MDMxIDQuNzUgMjAuNTExNzE5IDQuNzQ2MDk0IDIwLjU2MjUgNC43NSBDIDE4LjU4MjAzMSAyLjkzNzUgMTUuOTI1NzgxIDEuODQzNzUgMTMuMDMxMjUgMS44NDM3NSBaIE0gMjIuNzgxMjUgNy42MjUgQyAyMi44MzIwMzEgNy45ODA0NjkgMjIuODc1IDguMzcxMDk0IDIyLjg3NSA4Ljc4MTI1IEMgMjIuODc1IDkuOTEwMTU2IDIyLjY2NDA2MyAxMS4xNjQwNjMgMjIuMDMxMjUgMTIuNzUgTCAxOC42MjUgMjIuNTYyNSBDIDIxLjkyOTY4OCAyMC42MzI4MTMgMjQuMTU2MjUgMTcuMDcwMzEzIDI0LjE1NjI1IDEyLjk2ODc1IEMgMjQuMTU2MjUgMTEuMDM1MTU2IDIzLjY0ODQzOCA5LjIwNzAzMSAyMi43ODEyNSA3LjYyNSBaIE0gMi44NzUgOC40Mzc1IEMgMi4yNTc4MTMgOS44MjAzMTMgMS45MDYyNSAxMS4zNTU0NjkgMS45MDYyNSAxMi45Njg3NSBDIDEuOTA2MjUgMTcuMzcxMDk0IDQuNDc2NTYzIDIxLjE2NDA2MyA4LjE4NzUgMjIuOTY4NzUgWiBNIDEzLjIxODc1IDEzLjkzNzUgTCA5LjkwNjI1IDIzLjYyNSBDIDEwLjkwMjM0NCAyMy45MTc5NjkgMTEuOTM3NSAyNC4wOTM3NSAxMy4wMzEyNSAyNC4wOTM3NSBDIDE0LjMyNDIxOSAyNC4wOTM3NSAxNS41NjI1IDIzLjg0Mzc1IDE2LjcxODc1IDIzLjQzNzUgQyAxNi42OTE0MDYgMjMuMzkwNjI1IDE2LjY3OTY4OCAyMy4zNjcxODggMTYuNjU2MjUgMjMuMzEyNSBaIiAvPgo8L3N2Zz4=',
		];
		// phpcs:enable Generic.Files.LineLength.MaxExceeded

		return apply_filters( 'aioseo_eeat_social_icons', $icons );
	}
}