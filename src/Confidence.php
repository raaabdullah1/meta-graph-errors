<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * How certain a classification is, so a guess is never presented as a fact.
 *
 *  - High:   an exact (code, subcode) or subcode row.
 *  - Medium: a code-family row (Meta reuses codes across products), or Meta's
 *            own `is_transient` flag with no matching row.
 *  - Low:    nothing matched.
 */
enum Confidence: string
{
    case High   = 'high';
    case Medium = 'medium';
    case Low    = 'low';
}
