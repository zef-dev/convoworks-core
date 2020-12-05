<?php


namespace Convo\Core\Adapters\Viber;


interface IViberWebhookEventType
{
    const DELIVERED = 'delivered';
    const SEEN = 'seen';
    const FAILED = 'failed';
    const SUBSCRIBED = 'subscribed';
    const UNSUBSCRIBED = 'unsubscribed';
    const CONVERSATION_STARTED = 'conversation_started';
    const WEBHOOK_EVENT = 'webhook';
    const MESSAGE = 'message';
}
