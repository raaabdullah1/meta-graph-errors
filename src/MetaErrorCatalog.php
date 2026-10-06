<?php

declare(strict_types=1);

namespace MetaGraphErrors;

/**
 * Catalog of documented Meta Graph API error knowledge, kept as data so the
 * catalog grows by adding rows, not by adding classifier logic.
 *
 * Entries are keyed by (code, subcode). Meta reuses codes across products, so a
 * (code, subcode) pair always outranks a subcode-only row, which always
 * outranks a code-only family row.
 *
 * Each row's `source` is a short label for where the meaning came from:
 * Meta's documentation area (graph-api, business-messaging, social-integrations,
 * marketing-commerce, rest-api, videos) or `observed` for behaviour seen in
 * real API responses rather than documented.
 *
 * HONESTY RULE: a row exists only when a source backs it. Anything else is
 * left unclassified at low confidence; the catalog never invents certainty from
 * a message string. Hints are written for this library, not copied from Meta.
 *
 * Row shape (pairs and subcodes, 7 elements):
 *   [product, category, retryability, recovery, confidence, hint, source]
 * Row shape (code families, 6 elements, confidence is decided by the classifier):
 *   [product, category, retryability, recovery, hint, source]
 */
final class MetaErrorCatalog
{
    private const GRAPH     = 'graph-api';
    private const MESSAGING = 'business-messaging';
    private const SOCIAL    = 'social-integrations';
    private const MARKETING = 'marketing-commerce';
    private const REST      = 'rest-api';
    private const VIDEOS    = 'videos';
    private const OBSERVED  = 'observed';

    /**
     * Exact (code, subcode) families — the disambiguation layer.
     * Key: "code:subcode".
     */
    private const PAIRS = [
        // ── Messaging-window closures (Business Messaging) ─────────
        '10:2018276' => ['graph', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Outside the 24-hour messaging window — the customer must message the Page again (or use an approved message tag).', self::MESSAGING],
        '10:2534044' => ['instagram', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Outside Instagram\'s messaging window — the customer must message again (or an approved Human Agent flow applies).', self::MESSAGING . '+observed'],

        // ── Facebook Live eligibility gates (Videos) ───────────────
        '200:1363120' => ['live', ErrorCategory::PolicyViolation, Retryability::RequiresProviderAction, RecoveryAction::UserAction, Confidence::High, 'Meta requires the profile to be at least 60 days old for Live.', self::VIDEOS],
        '200:1363144' => ['live', ErrorCategory::PolicyViolation, Retryability::RequiresProviderAction, RecoveryAction::UserAction, Confidence::High, 'Meta requires at least 100 followers for Live on this profile.', self::VIDEOS],

        // ── Pages publishing (Social Integrations) ─────────────────
        '200:2069030' => ['pages', ErrorCategory::ResourceState, Retryability::NotRetryable, RecoveryAction::AdjustRequest, Confidence::High, 'Endpoint unsupported for this object.', self::SOCIAL],
        '200:2069031' => ['pages', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'A field in the request is unsupported.', self::SOCIAL],
        '190:2069032' => ['pages', ErrorCategory::Authentication, Retryability::RequiresReconnect, RecoveryAction::Reconnect, Confidence::High, 'A Page access token is required for this operation.', self::SOCIAL],
        '200:2069033' => ['pages', ErrorCategory::PolicyViolation, Retryability::NotRetryable, RecoveryAction::None, Confidence::High, 'This UI feature is deprecated/unavailable.', self::SOCIAL],
        '1:2853006'   => ['pages', ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::UserAction, Confidence::High, 'Viewer lacks permission — a Page admin must grant access.', self::SOCIAL],

        // ── Instagram publishing/media (Social Integrations) ───────
        '36000:2207004' => ['instagram', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Image is too large — resize and retry.', self::SOCIAL],
        '-2:2207003'    => ['instagram', ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Media download timed out.', self::SOCIAL],
        '-2:2207020'    => ['instagram', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Media expired — generate a new container.', self::SOCIAL],
        '-1:2207001'    => ['instagram', ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Instagram server error — retry.', self::SOCIAL],
        '-1:2207032'    => ['instagram', ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::AdjustRequest, Confidence::High, 'Media creation failed — recreate the media.', self::SOCIAL],
        '-1:2207053'    => ['instagram', ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::AdjustRequest, Confidence::High, 'Unknown upload error — generate a new container.', self::SOCIAL],
        '1:2207057'     => ['instagram', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid thumbnail offset.', self::SOCIAL],
        '4:2207051'     => ['instagram', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Suspected spam restriction on this Instagram account.', self::SOCIAL],
        '9:2207042'     => ['instagram', ErrorCategory::RateLimit, Retryability::RequiresUserAction, RecoveryAction::Wait, Confidence::High, 'Daily publishing limit reached — try again tomorrow.', self::SOCIAL],
        '24:2207006'    => ['instagram', ErrorCategory::ResourceState, Retryability::RequiresReconnect, RecoveryAction::Reconnect, Confidence::Medium, 'Media not found — may be deleted, or the token lacks access.', self::SOCIAL],
        '24:2207008'    => ['instagram', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Media builder expired — retry, recreating the container.', self::SOCIAL],
        '25:2207050'    => ['instagram', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Instagram account is restricted — sign in to Instagram and resolve the account action.', self::SOCIAL],
        '100:2207023'   => ['instagram', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Unknown media type.', self::SOCIAL],

        // ── OAuth subcodes on code 190 (Graph API auth table) ──────
        '190:463' => ['graph', ErrorCategory::Authentication, Retryability::RequiresReconnect, RecoveryAction::Reconnect, Confidence::High, 'Access token expired — reconnect the Meta account.', self::GRAPH],
        '190:467' => ['graph', ErrorCategory::Authentication, Retryability::RequiresReconnect, RecoveryAction::Reconnect, Confidence::High, 'Access token invalidated — reconnect the Meta account.', self::GRAPH],
        '190:458' => ['graph', ErrorCategory::Authentication, Retryability::RequiresUserAction, RecoveryAction::Reauthenticate, Confidence::High, 'User no longer has the app installed — they must re-authorize.', self::GRAPH],
        '190:459' => ['graph', ErrorCategory::Authentication, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'User is checkpointed on Meta — they must resolve it in the Meta app.', self::GRAPH],
        '190:460' => ['graph', ErrorCategory::Authentication, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'User changed their Meta password — sessions were closed.', self::GRAPH],
        '190:464' => ['graph', ErrorCategory::Authentication, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Unconfirmed Meta user — the user must confirm their account.', self::GRAPH],
        '190:466' => ['graph', ErrorCategory::Authentication, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'User must accept the latest Meta session terms.', self::OBSERVED],
        '190:492' => ['pages', ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::UserAction, Confidence::High, 'The connected Meta user does not have a role on this Page — a Page admin must grant access.', self::GRAPH],

        // ── Ads / Marketing API (Graph API ads tables) ─────────────
        '17:2446079'  => ['ads', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Ads API rate limit reached — back off and retry.', self::GRAPH],
        '80003:2446079' => ['ads', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Custom Audience rate limit — back off.', self::GRAPH],
        '80004:2446079' => ['ads', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Ads Management rate limit — back off.', self::GRAPH],
        '80008:2446079' => ['whatsapp', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'WhatsApp Business Management API rate limit — back off.', self::GRAPH],
        '80009:2446079' => ['catalog', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Catalog Management rate limit — back off.', self::GRAPH],
        '80014:2446079' => ['catalog', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, Confidence::High, 'Catalog Batch rate limit — back off.', self::GRAPH],
        '100:1752129'   => ['ads', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid ads parameter.', self::GRAPH],
    ];

    /**
     * Subcode-only families — discriminating regardless of the code.
     * Key: subcode.
     */
    private const SUBCODES = [
        '2534022' => ['instagram', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Outside Instagram\'s messaging window — the customer must message again.', self::OBSERVED],
        '1689001' => ['messenger', ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::RequestProviderPermission, Confidence::High, 'Requires pages_utility_messaging (Advanced Access) and a registered Page-owned template.', self::OBSERVED],

        // ── Messenger marketing-messages template validation (Rest API) ──
        '2300021' => ['messenger', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'A video_id is required when media_type is video.', self::REST],
        '2300022' => ['messenger', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'An image_url is required when media_type is image.', self::REST],
        '2300023' => ['messenger', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid button type in the marketing template.', self::REST],
        '2300024' => ['messenger', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Button URL cannot be empty.', self::REST],
        '2300025' => ['messenger', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Button title cannot be empty.', self::REST],
        '2300026' => ['messenger', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Too many buttons — maximum of 3.', self::REST],

        // ── Creator Marketplace / creator intelligence (MARKETING AND COMMERCE) ──
        '3961010' => ['creator_marketplace', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Could not load the requested creator — the creator ID may be invalid.', self::MARKETING],
        '3961014' => ['creator_marketplace', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Creator does not meet eligibility requirements.', self::MARKETING],
        '3961015' => ['creator_marketplace', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, Confidence::High, 'Creator has not been invited to this brand\'s marketplace.', self::MARKETING],
        '3961016' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid metric type requested.', self::MARKETING],
        '3961017' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid period type requested.', self::MARKETING],
        '3961018' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid time range type requested.', self::MARKETING],
        '3961019' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid content type requested.', self::MARKETING],
        '3961021' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid filter value.', self::MARKETING],
        '3961022' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Minimum follower count exceeds maximum.', self::MARKETING],
        '3961024' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid time range value.', self::MARKETING],
        '3961025' => ['creator_marketplace', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, Confidence::High, 'Invalid min/max value range.', self::MARKETING],
    ];

    /**
     * Code-only families. `product` scopes a code to a product where the
     * same code means something different elsewhere — a null product
     * entry is the cross-product documented meaning.
     * Key: "code" or "code|product".
     */
    private const CODES = [
        // ── Cross-product Graph codes (Graph API error table) ──────
        '1'      => [null, ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Unknown/transient Graph error — retry.', self::GRAPH],
        '2'      => [null, ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Temporary service disruption — retry.', self::GRAPH],
        '-1'     => [null, ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Provider-internal error — retry.', self::OBSERVED],
        '-2'     => [null, ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Provider download/timeout — retry.', self::OBSERVED],
        '3'      => [null, ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::RequestProviderPermission, 'Capability/permission not granted for this call.', self::GRAPH],
        '4'      => [null, ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'App-level rate limit — back off and retry.', self::GRAPH],
        '9'      => [null, ErrorCategory::RateLimit, Retryability::RequiresUserAction, RecoveryAction::Wait, 'Daily/frequency limit reached — wait before retrying.', self::SOCIAL],
        '10'     => [null, ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::RequestProviderPermission, 'Application lacks permission for this action.', self::GRAPH],
        '17'     => [null, ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'User-level rate limit — back off and retry.', self::GRAPH],
        '32'     => [null, ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Page-level rate limit — back off and retry.', self::GRAPH],
        '100'    => [null, ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Invalid parameter.', self::GRAPH],
        '102'    => [null, ErrorCategory::Authentication, Retryability::RequiresReconnect, RecoveryAction::Reconnect, 'Session invalid — re-authenticate.', self::GRAPH],
        '104'    => [null, ErrorCategory::Authentication, Retryability::RequiresReconnect, RecoveryAction::Reconnect, 'Access token signature invalid — reconnect.', self::GRAPH],
        '190'    => [null, ErrorCategory::Authentication, Retryability::RequiresReconnect, RecoveryAction::Reconnect, 'Access token expired/invalid (no subcode) — reconnect.', self::GRAPH],
        '200'    => [null, ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::RequestProviderPermission, 'Permissions error or access denied.', self::GRAPH],
        '341'    => [null, ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Application action limit reached — back off.', self::GRAPH],
        '368'    => [null, ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Temporarily blocked for policy violations.', self::GRAPH],
        '506'    => ['pages', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Duplicate posts cannot be published consecutively — change the content.', self::GRAPH],
        '613'    => [null, ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Too many calls — back off and retry.', self::GRAPH],
        '1609005' => ['pages', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Error posting link — Meta could not scrape the URL.', self::GRAPH],

        // ── Messenger (Business Messaging error table) ─────────────
        '551'     => ['messenger', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Person unavailable — they may have blocked the Page or deactivated their account.', self::MESSAGING],
        '1545041' => ['messenger', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Messaging window closed — the 24-hour standard window expired; use a message tag or one-time notification.', self::MESSAGING],

        // ── Ads / Marketing API (Graph API ads tables) ─────────────
        '2635'   => ['ads', ErrorCategory::Validation, Retryability::NotRetryable, RecoveryAction::AdjustRequest, 'Deprecated Ads API version — the app must upgrade its configured API version.', self::GRAPH],
        '2500'   => ['ads', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Error parsing the graph query.', self::GRAPH],
        '3018'   => ['ads', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'The start date of the time range is beyond 37 months.', self::GRAPH],
        '270'    => ['ads', ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::RequestProviderPermission, 'Ads API request not allowed for the app\'s access level — an app access-level upgrade is required, not a reconnect.', self::GRAPH],
        '80004'  => ['ads', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Too many calls to this ad account — wait and retry.', self::GRAPH],
        '1815199' => ['ads', ErrorCategory::ProviderPermission, Retryability::RequiresProviderAction, RecoveryAction::UserAction, 'The ad account has no access to this Instagram account — assign the asset in Meta Business settings.', self::GRAPH],
        '1885557' => ['ads', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'The ad is promoting an unavailable post — it was deleted or permissions changed.', self::GRAPH],

        // ── WhatsApp (Business Messaging error tables) ─────────────
        '131000' => ['whatsapp', ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Generic WhatsApp infrastructure failure after retries.', self::MESSAGING],
        '131021' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Sender and recipient phone number are the same.', self::MESSAGING],
        '131026' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Unable to deliver — recipient may not be on WhatsApp, must accept Meta\'s terms, or needs a newer client.', self::MESSAGING],
        '131037' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'The business phone number\'s display name is not approved — update it in Meta.', self::MESSAGING],
        '131042' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Payment-method problem on the WhatsApp Business account — resolve billing in Meta.', self::MESSAGING],
        '131045' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Phone number registration problem on the WABA.', self::MESSAGING],
        '131047' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Re-engagement required — more than 24h since the customer last messaged.', self::MESSAGING],
        '131048' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Outside the customer service window — a template message is required.', self::MESSAGING],
        '131051' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Unsupported message type for this recipient/flow.', self::MESSAGING],
        '131052' => ['whatsapp', ErrorCategory::Transient, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'Unable to download the media sent by the user — retry.', self::MESSAGING],
        '131053' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Unable to upload the media — check the MIME type.', self::MESSAGING],
        '131056' => ['whatsapp', ErrorCategory::RateLimit, Retryability::RetryableWithBackoff, RecoveryAction::RetryWithBackoff, 'WhatsApp pair-rate throttle — back off (Meta suggests ~4^X seconds).', self::MESSAGING],
        '132000' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Wrong number of variable parameters for the template.', self::MESSAGING],
        '132001' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Template does not exist in the specified language.', self::MESSAGING],
        '132005' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Translated template text is too long.', self::MESSAGING],
        '132007' => ['whatsapp', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Template content violates a WhatsApp policy.', self::MESSAGING],
        '132012' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Variable parameter values formatted incorrectly.', self::MESSAGING],
        '132015' => ['whatsapp', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Template paused for low quality — resolve in Meta.', self::MESSAGING],
        '132016' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Variable parameter is not translatable.', self::MESSAGING],
        '132021' => ['whatsapp', ErrorCategory::Validation, Retryability::RequiresUserAction, RecoveryAction::AdjustRequest, 'Template name conflict on the WABA.', self::MESSAGING],
        '132023' => ['whatsapp', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Template paused — resolve the policy issue in Meta.', self::MESSAGING],
        '132043' => ['whatsapp', ErrorCategory::PolicyViolation, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Template locked for a policy violation — resolve in Meta.', self::MESSAGING],
        '132068' => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Flow is in a blocked/blocked-state — resolve the flow in Meta.', self::MESSAGING],
        '1026'   => ['whatsapp', ErrorCategory::ResourceState, Retryability::RequiresUserAction, RecoveryAction::UserAction, 'Receiver incapable — the recipient cannot receive this message.', self::MESSAGING],
    ];


    /**
     * Exact (code, subcode) match.
     *
     * @return array{category: ErrorCategory, retryability: Retryability, recovery: RecoveryAction, confidence: ?Confidence, hint: string, source: ?string, product: ?string}|null
     */
    public static function pair(?string $code, ?string $subcode): ?array
    {
        if ($code === null || $subcode === null) {
            return null;
        }

        $entry = self::PAIRS["{$code}:{$subcode}"] ?? null;

        return $entry === null ? null : self::shaped($entry);
    }

    /**
     * Subcode-only match, discriminating regardless of the code.
     *
     * @return array{category: ErrorCategory, retryability: Retryability, recovery: RecoveryAction, confidence: ?Confidence, hint: string, source: ?string, product: ?string}|null
     */
    public static function subcode(?string $subcode): ?array
    {
        if ($subcode === null) {
            return null;
        }

        $entry = self::SUBCODES[$subcode] ?? null;

        return $entry === null ? null : self::shaped($entry);
    }

    /**
     * Code-family match. A "code|product" row wins over the cross-product row
     * when the caller supplies a product.
     *
     * @return array{category: ErrorCategory, retryability: Retryability, recovery: RecoveryAction, confidence: ?Confidence, hint: string, source: ?string, product: ?string}|null
     */
    public static function code(?string $code, ?string $product = null): ?array
    {
        if ($code === null) {
            return null;
        }

        if ($product !== null && isset(self::CODES["{$code}|{$product}"])) {
            return self::shaped(self::CODES["{$code}|{$product}"]);
        }

        $entry = self::CODES[$code] ?? null;

        return $entry === null ? null : self::shaped($entry);
    }

    /**
     * Every raw row, for documentation and coverage tests.
     *
     * @return array{pairs: array, subcodes: array, codes: array}
     */
    public static function entries(): array
    {
        return [
            'pairs'    => self::PAIRS,
            'subcodes' => self::SUBCODES,
            'codes'    => self::CODES,
        ];
    }

    /**
     * Normalize either row shape. Pair/subcode rows carry a Confidence at
     * index 4; code-family rows do not, so confidence is detected by type.
     *
     * @return array{category: ErrorCategory, retryability: Retryability, recovery: RecoveryAction, confidence: ?Confidence, hint: string, source: ?string, product: ?string}
     */
    private static function shaped(array $entry): array
    {
        $hasConfidence = ($entry[4] ?? null) instanceof Confidence;

        return [
            'category'     => $entry[1],
            'retryability' => $entry[2],
            'recovery'     => $entry[3],
            'confidence'   => $hasConfidence ? $entry[4] : null,
            'hint'         => $hasConfidence ? $entry[5] : $entry[4],
            'source'       => $hasConfidence ? ($entry[6] ?? null) : ($entry[5] ?? null),
            'product'      => $entry[0],
        ];
    }
}
