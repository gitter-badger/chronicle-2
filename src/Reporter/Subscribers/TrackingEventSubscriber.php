<?php
declare(strict_types=1);

namespace Plexikon\Chronicle\Reporter\Subscribers;

use Plexikon\Chronicle\Support\Contract\Reporter\Reporter;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageContext;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageSubscriber;
use Plexikon\Chronicle\Support\Contract\Tracker\MessageTracker;

final class TrackingEventSubscriber implements MessageSubscriber
{
    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->subscribeToDispatchEvent($tracker);

        $this->subscribeToFinalizeEvent($tracker);
    }

    private function subscribeToDispatchEvent(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (MessageContext $context): void {
            $message = $context->getMessage();
            $event = $message->isMessaging() ? $message->eventWithHeaders() : $message->event();

            foreach ($context->messageHandlers() as $messageHandler) {
                $messageHandler($event);
            }

            $context->setMessageHandled(true);
        });
    }

    private function subscribeToFinalizeEvent(MessageTracker $tracker)
    {
        $tracker->listen(Reporter::FINALIZE_EVENT, function (MessageContext $context): void {
            if ($context->hasException()) {
                throw $context->getException();
            }
        });
    }
}
