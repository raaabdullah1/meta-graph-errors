<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * What kind of failure this is, independent of Meta's numeric code.
 */
enum ErrorCategory: string
{
    case Authentication     = 'authentication';      // expired, invalid or revoked token
    case ProviderPermission = 'provider_permission'; // missing scope, role or App Review approval
    case RateLimit          = 'rate_limit';          // throttled
    case Validation         = 'validation';          // malformed request or invalid field
    case PolicyViolation    = 'policy_violation';    // Meta policy or community block
    case ResourceState      = 'resource_state';      // object gone, wrong state, messaging window closed
    case Transient          = 'transient';           // upstream hiccup
    case Unknown            = 'unknown';
}
