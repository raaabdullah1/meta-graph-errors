# meta-graph-errors

Meta's Graph API, Instagram, Messenger and WhatsApp Cloud API return numeric error codes that are reused across products and often unhelpful. This library maps them to something your code can act on: what kind of failure it is, whether retrying can help, and what to do next.

```php
use MetaGraphErrors\MetaErrorClassifier;

$error = MetaErrorClassifier::fromResponse($response->json());

if ($error->requiresReconnect()) {
    // 190:463, 190:467: the token is dead, send the user through OAuth again
} elseif ($error->shouldBackOff()) {
    // rate limits and transient failures: retry after a delay
} else {
    echo $error->hint; // plain-language explanation
}
```

## Install

```bash
composer require raaabdullah1/meta-graph-errors
```

Requires PHP 8.1 or newer. No other dependencies.

## Laravel example

```php
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use MetaGraphErrors\MetaErrorClassifier;

try {
    Http::withToken($pageToken)
        ->post("https://graph.facebook.com/v21.0/{$pageId}/feed", ['message' => $text])
        ->throw();
} catch (RequestException $e) {
    $error = MetaErrorClassifier::fromResponse($e->response->json() ?? []);

    if ($error->requiresReconnect()) {
        // mark the connection as expired and ask the user to reconnect
    } elseif ($error->shouldBackOff()) {
        // release the job back to the queue with a delay
    } else {
        report($error->hint); // log it, or show it to the user
    }
}
```

## What you get back

`classify()` and `fromResponse()` return a `Classification`:

| Field | Type | Meaning |
|---|---|---|
| `category` | `ErrorCategory` | `Authentication`, `ProviderPermission`, `RateLimit`, `Validation`, `PolicyViolation`, `ResourceState`, `Transient`, `Unknown` |
| `retryability` | `Retryability` | `Retryable`, `RetryableWithBackoff`, `RequiresUserAction`, `RequiresReconnect`, `RequiresProviderAction`, `NotRetryable`, `Unknown` |
| `recovery` | `RecoveryAction` | the primary corrective step, such as `Reconnect`, `AdjustRequest`, `Wait` |
| `confidence` | `Confidence` | `High`, `Medium` or `Low` |
| `hint` | `string` | a short explanation |
| `matchedBy` | `string` | `pair`, `subcode`, `code`, `transient_flag` or `none` |
| `product`, `source` | `?string` | which Meta product the row is about, and where the meaning came from |

Helpers: `isRetryable()`, `shouldBackOff()`, `requiresReconnect()`, `isKnown()`, `toArray()`.

```php
// From a decoded response, a JSON string, or just the numbers
MetaErrorClassifier::fromResponse($decodedArray);
MetaErrorClassifier::fromResponse($jsonString);
MetaErrorClassifier::classify(code: 190, subcode: 463);
```

## How matching works

Meta reuses error codes across products, so matching goes from most to least specific:

1. the exact `(code, error_subcode)` pair, such as `190:463` (token expired)
2. the `error_subcode` alone, such as `2534022` (Instagram messaging window closed)
3. the `code` alone, such as `613` (rate limit)
4. Meta's own `is_transient` flag, if nothing above matched
5. otherwise `Unknown` at `Low` confidence

It never guesses from a message string. An error it doesn't recognise comes back as unknown, so you can tell "we know this" from "we don't".

## Coverage

109 catalogued rows covering the Graph API OAuth and permission errors, Pages publishing, Instagram media and publishing limits, Messenger and Instagram messaging windows, WhatsApp Cloud API send, template and media errors, Ads and Catalog rate limits, Facebook Live eligibility and Creator Marketplace validation. Each row has a `source` label naming the documentation area it came from, or `observed` when it was seen in real API responses rather than documented.

Meta adds and changes error codes without notice. If you hit one that is missing or wrong, please open an issue or a PR with a link to Meta's documentation or a sample response.

## Development

```bash
composer install
composer test
```

## License

MIT
