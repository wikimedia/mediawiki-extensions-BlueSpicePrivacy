<?php

namespace BlueSpice\Privacy;

interface ICookieConsentProvider {

	/**
	 * RL module that is loaded as high up in <head> as possible
	 *
	 * @return string
	 */
	public function getRLRegistrationModule();

	/**
	 * Get consent groups based on user preferences
	 *
	 * @return array
	 */
	public function getGroups();

	/**
	 * Name of the cookie where cookie preferences are set
	 *
	 * @return string
	 */
	public function getCookieName();

	/**
	 * Mapping of groups and belonging cookie names
	 *
	 * Eg. return [
	 * 		"necessary" => [
	 * 			[
	 * 				"type" => "exact"|"regex",
	 * 				"name" => "my_cookie"
	 * 			]
	 * 		]
	 * ]
	 * @return array
	 */
	public function getGroupMapping();

	/**
	 * RL module used to handle cookies
	 *
	 * @return string
	 */
	public function getRLHandlerModule();

	/**
	 * Class that inherits bs.privacy.cookieConsent.BaseHandler
	 *
	 * Handler module is loaded separately not to interfere with
	 * "load as soon as possible" strategy of registering module
	 *
	 * @return string
	 */
	public function getHandlerClass();

	/**
	 * Config to be passed to the client
	 *
	 * @return array
	 */
	public function getHandlerConfig();
}
