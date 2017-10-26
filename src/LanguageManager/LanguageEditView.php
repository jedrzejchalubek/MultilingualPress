<?php # -*- coding: utf-8 -*-
declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\LanguageManager;

use Inpsyde\MultilingualPress\API\Languages;
use Inpsyde\MultilingualPress\Common\Labels;
use Inpsyde\MultilingualPress\Common\Type\NullLanguage;
use Inpsyde\MultilingualPress\Database\Table\LanguagesTable;

final class LanguageEditView {
	/**
	 * @var \Inpsyde\MultilingualPress\API\Languages
	 */
	private $languages;

	/**
	 * @var \Inpsyde\MultilingualPress\Common\Labels
	 */
	private $labels;

	/**
	 * LanguageEditView constructor.
	 *
	 * @param Languages $languages
	 * @param Labels    $labels
	 */
	public function __construct( Languages $languages, Labels $labels )
	{
		$this->languages = $languages;
		$this->labels    = $labels;
	}

	/**
	 * @param string $language_id
	 *
	 * @return void
	 */
	public function render( int $language_id )
	{
		$language = $this->languages->get_language_by( LanguagesTable::COLUMN_ID, $language_id );

		if ( is_a( $language, NullLanguage::class ) ) {
			// now what?
			return;
		}

		// temporary
		print '<pre>' . print_r( $language, true ) . '</pre>';
		print '<pre>' . print_r( $this->labels->all(), true ) . '</pre>';
	}
}