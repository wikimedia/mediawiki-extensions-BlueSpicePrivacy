<?php
namespace BlueSpice\Privacy\Auth\Provider;

use BlueSpice\Privacy\Auth\PrefSaver;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AbstractPasswordPrimaryAuthenticationProvider;
use BlueSpice\Privacy\Auth\Request\SignupAuthenticationRequest;
use MediaWiki\Auth\AuthenticationRequest;

class SignupPrimaryAuthenticationProvider extends AbstractPasswordPrimaryAuthenticationProvider {
	/**
	 *
	 * @param string $action
	 * @param array $options
	 * @return array
	 */
	public function getAuthenticationRequests( $action, array $options ) {
		switch ( $action ) {
			case AuthManager::ACTION_CREATE:
				return [ new SignupAuthenticationRequest() ];
				break;
			default:
				return [];
				break;
		}
	}

	/**
	 *
	 * @return string
	 */
	public function accountCreationType() {
		return self::TYPE_CREATE;
	}

	/**
	 *
	 * @param \User $user
	 * @param \User $creator
	 * @param array $reqs
	 * @return AuthenticationResponse
	 */
	public function beginPrimaryAccountCreation( $user, $creator, array $reqs ) {
		return AuthenticationResponse::newAbstain();
	}

	/**
	 *
	 * @param array $reqs
	 * @return AuthenticationResponse
	 */
	public function beginPrimaryAuthentication( array $reqs ) {
		return AuthenticationResponse::newAbstain();
	}

	/**
	 *
	 * @param AuthenticationRequest $req
	 * @param bool $checkData
	 * @return \StatusValue
	 */
	public function providerAllowsAuthenticationDataChange( AuthenticationRequest $req,
		$checkData = true ) {
		return \StatusValue::newGood( 'ignored' );
	}

	/**
	 *
	 * @param AuthenticationRequest $req
	 * @return void
	 */
	public function providerChangeAuthenticationData( AuthenticationRequest $req ) {
		return;
	}

	/**
	 *
	 * @param string $username
	 * @param int $flags
	 * @return bool
	 */
	public function testUserExists( $username, $flags = \User::READ_NORMAL ) {
		// Dont do actual check, just let other providers do the checking
		return false;
	}

	/**
	 *
	 * @param \User $user
	 * @param \User $creator
	 * @param AuthenticationResponse $response
	 */
	public function postAccountCreation( $user, $creator, AuthenticationResponse $response ) {
		$prefSaver = PrefSaver::getInstance();

		foreach ( $prefSaver->getData() as $name => $value ) {
			$user->setOption( $name, $value );
		}
		$user->saveSettings();
	}

}
