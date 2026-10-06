<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * Whether repeating the same request could succeed, and what has to happen first.
 */
enum Retryability: string
{
    case Retryable              = 'retryable';                // safe to retry as-is
    case RetryableWithBackoff   = 'retryable_with_backoff';   // retry after a delay
    case RequiresUserAction     = 'requires_user_action';     // the person must fix input or act in Meta's UI
    case RequiresReconnect      = 'requires_reconnect';       // the OAuth connection must be redone
    case RequiresProviderAction = 'requires_provider_action'; // App Review, Business roles or an appeal
    case NotRetryable           = 'not_retryable';            // permanent, retrying changes nothing
    case Unknown                = 'unknown';
}
