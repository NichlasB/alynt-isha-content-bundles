<?php
/**
 * Video access decision value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describes how a WordPress adapter should handle a video request.
 */
final class VideoAccessDecision {

	const ACTION_ALLOW    = 'allow';
	const ACTION_REDIRECT = 'redirect';
	const ACTION_DENY     = 'deny';

	/**
	 * Decision action.
	 *
	 * @var string
	 */
	private $action;

	/**
	 * Decision reason code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Redirect URL, when applicable.
	 *
	 * @var string|null
	 */
	private $redirect_url;

	/**
	 * Create a decision.
	 *
	 * @param string      $action       Decision action.
	 * @param string      $code         Decision reason code.
	 * @param string|null $redirect_url Redirect URL.
	 */
	private function __construct( string $action, string $code, ?string $redirect_url = null ) {
		$this->action       = $action;
		$this->code         = $code;
		$this->redirect_url = $redirect_url;
	}

	/**
	 * Create an allow decision.
	 *
	 * @param string $code Reason code.
	 * @return self
	 */
	public static function allow( string $code ): self {
		return new self( self::ACTION_ALLOW, $code );
	}

	/**
	 * Create a redirect decision.
	 *
	 * @param string $code         Reason code.
	 * @param string $redirect_url Redirect URL.
	 * @return self
	 */
	public static function redirect( string $code, string $redirect_url ): self {
		return new self( self::ACTION_REDIRECT, $code, $redirect_url );
	}

	/**
	 * Create a deny decision.
	 *
	 * @param string $code Reason code.
	 * @return self
	 */
	public static function deny( string $code ): self {
		return new self( self::ACTION_DENY, $code );
	}

	/**
	 * Get the decision action.
	 *
	 * @return string
	 */
	public function get_action(): string {
		return $this->action;
	}

	/**
	 * Get the reason code.
	 *
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Get the redirect URL.
	 *
	 * @return string|null
	 */
	public function get_redirect_url(): ?string {
		return $this->redirect_url;
	}

	/**
	 * Determine whether the request may proceed.
	 *
	 * @return bool
	 */
	public function is_allowed(): bool {
		return self::ACTION_ALLOW === $this->action;
	}

	/**
	 * Determine whether the request should redirect.
	 *
	 * @return bool
	 */
	public function is_redirect(): bool {
		return self::ACTION_REDIRECT === $this->action;
	}

	/**
	 * Determine whether the request should be denied without redirecting.
	 *
	 * @return bool
	 */
	public function is_denied(): bool {
		return self::ACTION_DENY === $this->action;
	}
}
