<?php
declare(strict_types=1);
namespace TYPO3\CMS\Backend\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render FlashMessages
 */
class FlashMessageController
{
    /**
     * Renders the FlashMessages from queue and returns them as JSON.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getQueuedFlashMessagesAction(ServerRequestInterface $request): ResponseInterface
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $flashMessages = $defaultFlashMessageQueue->getAllMessagesAndFlush();

        $messages = [];
        foreach ($flashMessages as $flashMessage) {
            $messages[] = [
                'title' => $flashMessage->getTitle(),
                'message' => $flashMessage->getMessage(),
                'severity' => $flashMessage->getSeverity()
            ];
        }

        return GeneralUtility::makeInstance(JsonResponse::class)->setPayload($messages);
    }
}
