<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * The result of classifying one Meta error.
 */
final class Classification
{
    /**
     * @param string $matchedBy One of 'pair', 'subcode', 'code', 'transient_flag', 'none'.
     */
    public function __construct(
        public readonly ErrorCategory $category,
        public readonly Retryability $retryability,
        public readonly RecoveryAction $recovery,
        public readonly Confidence $confidence,
        public readonly string $hint,
        public readonly string $matchedBy,
        public readonly ?string $code = null,
        public readonly ?string $subcode = null,
        public readonly ?string $product = null,
        public readonly ?string $source = null,
    ) {
    }

    /** True when sending the same request again, after any delay, can succeed. */
    public function isRetryable(): bool
    {
        return $this->retryability === Retryability::Retryable
            || $this->retryability === Retryability::RetryableWithBackoff;
    }

    public function shouldBackOff(): bool
    {
        return $this->retryability === Retryability::RetryableWithBackoff;
    }

    public function requiresReconnect(): bool
    {
        return $this->retryability === Retryability::RequiresReconnect;
    }

    public function isKnown(): bool
    {
        return $this->matchedBy !== 'none';
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'category'     => $this->category->value,
            'retryability' => $this->retryability->value,
            'recovery'     => $this->recovery->value,
            'confidence'   => $this->confidence->value,
            'hint'         => $this->hint,
            'matched_by'   => $this->matchedBy,
            'code'         => $this->code,
            'subcode'      => $this->subcode,
            'product'      => $this->product,
            'source'       => $this->source,
        ];
    }
}
