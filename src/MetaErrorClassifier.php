<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * Turns a Meta error into a Classification.
 *
 * Precedence: (code, subcode) pair, then subcode alone, then code alone. When
 * nothing matches, Meta's own `is_transient` flag is honoured; otherwise the
 * result is Unknown at low confidence, so callers can tell a guess from a fact.
 */
final class MetaErrorClassifier
{
    /**
     * Classify from a decoded Graph API error response, or its JSON body.
     * Accepts both `{"error": {...}}` and the inner error object itself.
     *
     * @param array<string, mixed>|string $response
     */
    public static function fromResponse(array|string $response): Classification
    {
        if (is_string($response)) {
            $decoded  = json_decode($response, true);
            $response = is_array($decoded) ? $decoded : [];
        }

        $error = isset($response['error']) && is_array($response['error'])
            ? $response['error']
            : $response;

        return self::classify(
            $error['code'] ?? null,
            $error['error_subcode'] ?? null,
            isset($error['is_transient']) ? (bool) $error['is_transient'] : null,
        );
    }

    public static function classify(
        int|string|null $code,
        int|string|null $subcode = null,
        ?bool $isTransient = null,
        ?string $product = null,
    ): Classification {
        $code    = self::normalize($code);
        $subcode = self::normalize($subcode);

        if (($row = MetaErrorCatalog::pair($code, $subcode)) !== null) {
            return self::build($row, 'pair', $code, $subcode, $row['confidence'] ?? Confidence::High);
        }

        if (($row = MetaErrorCatalog::subcode($subcode)) !== null) {
            return self::build($row, 'subcode', $code, $subcode, $row['confidence'] ?? Confidence::High);
        }

        if (($row = MetaErrorCatalog::code($code, $product)) !== null) {
            return self::build($row, 'code', $code, $subcode, $row['confidence'] ?? Confidence::Medium);
        }

        if ($isTransient === true) {
            return new Classification(
                ErrorCategory::Transient,
                Retryability::RetryableWithBackoff,
                RecoveryAction::RetryWithBackoff,
                Confidence::Medium,
                'Meta flagged this error as temporary (is_transient) — retry with backoff.',
                'transient_flag',
                $code,
                $subcode,
                $product,
            );
        }

        return new Classification(
            ErrorCategory::Unknown,
            Retryability::Unknown,
            RecoveryAction::None,
            Confidence::Low,
            'This Meta error code is not in the catalog.',
            'none',
            $code,
            $subcode,
            $product,
        );
    }

    private static function normalize(int|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param array{category: ErrorCategory, retryability: Retryability, recovery: RecoveryAction, confidence: ?Confidence, hint: string, source: ?string, product: ?string} $row */
    private static function build(array $row, string $matchedBy, ?string $code, ?string $subcode, Confidence $confidence): Classification
    {
        return new Classification(
            $row['category'],
            $row['retryability'],
            $row['recovery'],
            $confidence,
            $row['hint'],
            $matchedBy,
            $code,
            $subcode,
            $row['product'],
            $row['source'],
        );
    }
}
