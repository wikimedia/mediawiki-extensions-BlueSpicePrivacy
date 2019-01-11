<?php

namespace BlueSpice\Privacy\Notifications;

use BlueSpice\Privacy\Notifications\PresentationModel\AnonymizationDone;
use BlueSpice\Privacy\Notifications\PresentationModel\RequestAnonymizationDenied;
use BlueSpice\Privacy\Notifications\PresentationModel\RequestDeletionDenied;
use BlueSpice\Privacy\Notifications\PresentationModel\RequestDeletionApproved;
use BlueSpice\Privacy\Notifications\PresentationModel\DeletionFailed;
use BlueSpice\Privacy\Notifications\PresentationModel\RequestSubmitted;

class Registrator {

	/**
	 *
	 * @param \BlueSpice\NotificationManager $notificationsManager
	 */
	public static function registerNotifications(
		\BlueSpice\NotificationManager $notificationsManager ) {
		$echoNotifier = $notificationsManager->getNotifier();
		$echoNotifier->registerNotificationCategory( 'bs-privacy-cat' );

		$notificationsManager->registerNotification(
			'bs-privacy-anonymization-done',
			[
				'category' => 'bs-privacy-cat',
				'presentation-model' => AnonymizationDone::class
			]
		);

		$notificationsManager->registerNotification(
			'bs-privacy-request-anonymization-denied',
			[
				'category' => 'bs-privacy-cat',
				'presentation-model' => RequestAnonymizationDenied::class
			]
		);

		$notificationsManager->registerNotification(
			'bs-privacy-request-deletion-denied',
			[
				'category' => 'bs-privacy-cat',
				'presentation-model' => RequestDeletionDenied::class
			]
		);

		$notificationsManager->registerNotification(
			'bs-privacy-request-deletion-approved',
			[
				'category' => 'bs-privacy-cat',
				'presentation-model' => RequestDeletionApproved::class,
				'immediate' => true
			]
		);

		$notificationsManager->registerNotification(
			'bs-privacy-deletion-failed',
			[
				'category' => 'bs-privacy-cat',
				'presentation-model' => DeletionFailed::class
			]
		);

		$notificationsManager->registerNotification(
			'bs-privacy-request-submitted',
			[
				'category' => 'bs-privacy-cat',
				'presentation-model' => RequestSubmitted::class
			]
		);
	}
}
