<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * The primary corrective step for an error. The classification's `hint`
 * carries the specifics.
 */
enum RecoveryAction: string
{
    case Retry                     = 'retry';
    case RetryWithBackoff          = 'retry_with_backoff';
    case Reconnect                 = 'reconnect';                   // redo the OAuth connection
    case Reauthenticate            = 'reauthenticate';              // the user must sign in again
    case RequestProviderPermission = 'request_provider_permission'; // App Review or a scope request
    case AdjustRequest             = 'adjust_request';              // fix the inputs, then retry
    case Wait                      = 'wait';
    case UserAction                = 'user_action';                 // something inside Meta's own UI
    case None                      = 'none';
}
