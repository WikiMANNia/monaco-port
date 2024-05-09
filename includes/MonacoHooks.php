<?php

use MediaWiki\MediaWikiServices;

class MonacoHooks {

	/**
	 * Add the theme selector to user preferences.
	 *
	 * @param User $user
	 * @param array &$defaultPreferences
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public static function onGetPreferences( $user, &$preferences ) {

		$monacoConfig = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'monaco' );

		$ctx = RequestContext::getMain();
		$skin = $ctx->getSkin();
		$skinName = $skin->getSkinName();
		$themes = SkinMonaco::getSkinMonacoThemeList();
		$theme_key = SkinMonaco::getThemeKey();
		$user = $ctx->getUser();
		$allowedThemes = $monacoConfig->get( 'MonacoAllowusetheme' );
		$defaultTheme = $monacoConfig->get( 'MonacoTheme' );

		// Braindead code needed to make the theme *names* show up
		// Without this they show up as "0", "1", etc. in the UI
		// First themes will be translated in i18n and then sorted.

		// Get translated theme names
		$themeArray_for_sort = [];
		foreach ( $themes as $theme ) {
			$themeDisplayNameMsg = $ctx->msg( "theme-name-$skinName-$theme" );
			if ( $themeDisplayNameMsg->isDisabled() ) {
				// No i18n available for this -> use the key as-is
				$themeDisplayName = ucfirst( $theme );
			} else {
				// Use i18n; it's much nicer to display formatted theme names if and when
				// a theme name contains spaces, uppercase characters, etc.
				$themeDisplayName = $themeDisplayNameMsg->text();
			}
			$themeArray_for_sort[$theme] = $themeDisplayName;
		}

		// Sort this list and and a default element.
		asort( $themeArray_for_sort );
		$theme = 'default';
		$themeDisplayNameMsg = $ctx->msg( "theme-name-$skinName-$theme" );
		$themeDisplayName = 
			$themeDisplayNameMsg->isDisabled()
			? $theme
			: $themeDisplayName = $themeDisplayNameMsg->text();
		// Ensure that 'default' is always the 1st array item
		$themeArray = [ $themeDisplayName => $theme ];

		// Add the rest of the items.
		foreach ( $themeArray_for_sort as $theme => $themeDisplayName ) {
			$themeArray[$themeDisplayName] = $theme;
		}

		$usersTheme = $user->getOption( $theme_key, $defaultTheme );
		$showIf = [ '!==', 'skin', 'monaco' ];

		// The entry 'theme' conflicts with Extension:Theme.
		$preferences[$theme_key] = $allowedThemes
			?	[
					'type' => 'select',
					'options' => $themeArray,
					'default' => $usersTheme,
					'label-message' => 'monaco-theme-prefs-label',
					'section' => 'rendering/skin',
					'hide-if' => $showIf
				]
			:	// If the selection of themes is deactiveted,
				// show only an informative message instead
				[
					'type' => 'info',
					'label-message' => 'monaco-theme-prefs-label',
					'default' => $ctx->msg( 'theme-selection-deactivated' )->text(),
					'section' => 'rendering/skin',
					'hide-if' => $showIf
				];
	}
}
