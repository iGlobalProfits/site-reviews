<?php

/**
 * @package   GeminiLabs\SiteReviews
 * @copyright Copyright (c) 2016, Paul Ryley
 * @license   GPLv2 or later
 * @since     1.0.0
 * -------------------------------------------------------------------------------------------------
 */

namespace GeminiLabs\SiteReviews\Html\Fields;

use GeminiLabs\SiteReviews\Html\Fields\Radio;

class Yesno extends Radio
{
	/**
	 * @return string
	 */
	public function render()
	{
		$defaultValue = 0;

		$this->args['options'] = [
			'no'  => __( 'No', 'site-reviews' ),
			'yes' => __( 'Yes', 'site-reviews' ),
		];

		return parent::render( $defaultValue );
	}
}
