<?php

namespace Convo\Core\Adapters\Google\Common;

interface IResponseType
{
    public const SIMPLE_RESPONSE = 'SIMPLE_RESPONSE';
    public const BASIC_CARD = 'BASIC_CARD';
    public const MEDIA_RESPONSE = 'MEDIA_RESPONSE';
    public const STRUCTURED_RESPONSE = 'STRUCTURED_RESPONSE';
    public const CAROUSEL_BROWSE = 'CAROUSEL_BROWSE';
    public const CAROUSEL = 'CAROUSEL';
    public const LIST = 'LIST';
    public const CONFIRMATION = 'CONFIRMATION';
    public const TABLE_CARD = 'TABLE_CARD';
    public const HTML_RESPONSE = 'HTML_RESPONSE';
    public const SIGN_IN_RESPONSE = 'SIGN_IN_RESPONSE';
}
