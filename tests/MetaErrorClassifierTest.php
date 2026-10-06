<?php

declare(strict_types=1);

namespace MetaGraphErrors\Tests;

use MetaGraphErrors\Confidence;
use MetaGraphErrors\ErrorCategory;
use MetaGraphErrors\MetaErrorClassifier;
use MetaGraphErrors\RecoveryAction;
use MetaGraphErrors\Retryability;
use PHPUnit\Framework\TestCase;

final class MetaErrorClassifierTest extends TestCase
{
    public function test_expired_token_pair_asks_for_a_reconnect(): void
    {
        $c = MetaErrorClassifier::classify(190, 463);

        $this->assertSame(ErrorCategory::Authentication, $c->category);
        $this->assertSame(Retryability::RequiresReconnect, $c->retryability);
        $this->assertSame(RecoveryAction::Reconnect, $c->recovery);
        $this->assertSame(Confidence::High, $c->confidence);
        $this->assertSame('pair', $c->matchedBy);
        $this->assertTrue($c->requiresReconnect());
        $this->assertFalse($c->isRetryable());
    }

    public function test_pair_outranks_the_code_family(): void
    {
        // 190 alone is "reconnect"; 190:459 is a checkpointed user who must act in Meta's app.
        $pair = MetaErrorClassifier::classify(190, 459);
        $code = MetaErrorClassifier::classify(190);

        $this->assertSame(Retryability::RequiresUserAction, $pair->retryability);
        $this->assertSame('pair', $pair->matchedBy);
        $this->assertSame(Retryability::RequiresReconnect, $code->retryability);
        $this->assertSame('code', $code->matchedBy);
    }

    public function test_code_family_rows_are_medium_confidence(): void
    {
        $c = MetaErrorClassifier::classify(613);

        $this->assertSame(ErrorCategory::RateLimit, $c->category);
        $this->assertSame(Confidence::Medium, $c->confidence);
        $this->assertTrue($c->isRetryable());
        $this->assertTrue($c->shouldBackOff());
    }

    public function test_subcode_only_row_matches_whatever_the_code_is(): void
    {
        $c = MetaErrorClassifier::classify(10, 2534022);

        $this->assertSame('subcode', $c->matchedBy);
        $this->assertSame('instagram', $c->product);
        $this->assertSame(ErrorCategory::ResourceState, $c->category);
    }

    public function test_whatsapp_codes(): void
    {
        $window = MetaErrorClassifier::classify(131047);
        $this->assertSame(ErrorCategory::ResourceState, $window->category);
        $this->assertSame('whatsapp', $window->product);

        $throttle = MetaErrorClassifier::classify('131056');
        $this->assertSame(ErrorCategory::RateLimit, $throttle->category);
        $this->assertTrue($throttle->shouldBackOff());
    }

    public function test_instagram_daily_publish_limit_is_a_wait_not_a_retry(): void
    {
        $c = MetaErrorClassifier::classify(9, 2207042);

        $this->assertSame(ErrorCategory::RateLimit, $c->category);
        $this->assertSame(RecoveryAction::Wait, $c->recovery);
        $this->assertFalse($c->isRetryable());
    }

    public function test_from_response_accepts_a_decoded_error_envelope(): void
    {
        $c = MetaErrorClassifier::fromResponse([
            'error' => [
                'message'       => 'Error validating access token',
                'type'          => 'OAuthException',
                'code'          => 190,
                'error_subcode' => 467,
            ],
        ]);

        $this->assertSame('190', $c->code);
        $this->assertSame('467', $c->subcode);
        $this->assertSame(Retryability::RequiresReconnect, $c->retryability);
    }

    public function test_from_response_accepts_a_json_string_and_a_bare_error_object(): void
    {
        $json = MetaErrorClassifier::fromResponse('{"error":{"code":4,"message":"Application request limit reached"}}');
        $bare = MetaErrorClassifier::fromResponse(['code' => 4]);

        $this->assertSame(ErrorCategory::RateLimit, $json->category);
        $this->assertSame(ErrorCategory::RateLimit, $bare->category);
    }

    public function test_unknown_code_is_unknown_at_low_confidence(): void
    {
        $c = MetaErrorClassifier::classify(987654321);

        $this->assertSame(ErrorCategory::Unknown, $c->category);
        $this->assertSame(Retryability::Unknown, $c->retryability);
        $this->assertSame(Confidence::Low, $c->confidence);
        $this->assertFalse($c->isKnown());
    }

    public function test_is_transient_flag_is_honoured_only_when_nothing_matches(): void
    {
        $unmatched = MetaErrorClassifier::fromResponse(['error' => ['code' => 987654321, 'is_transient' => true]]);
        $this->assertSame('transient_flag', $unmatched->matchedBy);
        $this->assertTrue($unmatched->shouldBackOff());
        $this->assertSame(Confidence::Medium, $unmatched->confidence);

        // A catalog row beats the flag: 190 is a reconnect even if Meta marks it transient.
        $matched = MetaErrorClassifier::fromResponse(['error' => ['code' => 190, 'is_transient' => true]]);
        $this->assertSame('code', $matched->matchedBy);
        $this->assertSame(Retryability::RequiresReconnect, $matched->retryability);
    }

    public function test_garbage_input_does_not_throw(): void
    {
        $this->assertFalse(MetaErrorClassifier::fromResponse('not json')->isKnown());
        $this->assertFalse(MetaErrorClassifier::fromResponse([])->isKnown());
        $this->assertFalse(MetaErrorClassifier::classify('', '')->isKnown());
    }

    public function test_to_array_is_json_friendly(): void
    {
        $array = MetaErrorClassifier::classify(190, 463)->toArray();

        $this->assertSame('authentication', $array['category']);
        $this->assertSame('requires_reconnect', $array['retryability']);
        $this->assertNotFalse(json_encode($array));
    }
}
