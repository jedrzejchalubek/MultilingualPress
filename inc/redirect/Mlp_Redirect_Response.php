<?php
/**
 * Send redirect headers.
 *
 * @version    2014.09.26
 * @author     Inpsyde GmbH, toscho
 * @license    GPL
 * @package    MultilingualPress
 * @subpackage Redirect
 */
class Mlp_Redirect_Response implements Mlp_Redirect_Response_Interface {

	/**
	 * @type Mlp_Language_Negotiation_Interface
	 */
	private $negotiation;

	/**
	 * Constructor.
	 *
	 * @param Mlp_Language_Negotiation_Interface $negotiation
	 */
	public function __construct( Mlp_Language_Negotiation_Interface $negotiation ) {

		$this->negotiation = $negotiation;
	}

	/**
	 * Redirect if needed.
	 *
	 * @return void
	 */
	public function redirect() {

		if ( ! empty ( $_GET[ 'noredirect' ] ) ) {
			$this->save_session( $_GET[ 'noredirect' ] );
			return;
		}

		$match = $this->negotiation->get_redirect_match();

		if ( $match[ 'site_id' ] === get_current_blog_id() )
			return;

		$url             = $match[ 'url' ];
		$current_blog_id = get_current_blog_id();

		/**
		 * Filter the redirect URL.
		 *
		 * You might add support for other types than singular posts and home here.
		 * If you return an empty string, the redirect will not happen. The result will be validated with esc_url().
		 *
		 * @param string $url             Redirect URL.
		 * @param array  $match           Redirect match. {
		 *                                'priority' => int
		 *                                'url'      => string
		 *                                'language' => string
		 *                                'site_id'  => int
		 *                                }
		 * @param int    $current_blog_id Current blog ID.
		 *
		 * @return string
		 */
		$url = apply_filters( 'mlp_redirect_url', $url, $match, $current_blog_id );

		if ( empty ( $url ) )
			return; // no URL found

		$this->save_session( $match[ 'language' ] );

		wp_redirect( $url ); // finally!
		exit;
	}

	/**
	 * save no redirect data in session
	 *
	 * @return void
     */
	function save_session( $language ) {

		// check to only session_start if no session is active
		if ( ! isset ( $_SESSION ) && ! session_id() )
			session_start();

		if ( isset ( $_SESSION[ 'noredirect' ] ) ) {
			$existing = (array) $_SESSION[ 'noredirect' ];

			if ( in_array( $language, $existing ) )
				return;

		}
		$_SESSION[ 'noredirect' ][] = $language;
	}

}
