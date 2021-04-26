<?php

namespace Convo\Core\Adapters\Alexa;

interface IAlexaResponseType
{
    public const LIST_RESPONSE = 'LIST_RESPONSE';
    public const SIMPLE_RESPONSE = 'SIMPLE_RESPONSE';
    public const MEDIA_RESPONSE = 'MEDIA_RESPONSE';
    public const CARD_RESPONSE = 'CARD_RESPONSE';
    public const EMPTY_RESPONSE = 'EMPTY_RESPONSE';
    public const VIDEO_RESPONSE = 'VIDEO_RESPONSE';
}