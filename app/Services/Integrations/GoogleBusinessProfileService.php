<?php

namespace App\Services\Integrations;

use App\Models\GoogleBusinessReview;
use App\Models\Integration;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBusinessProfileService
{
    public function __construct(private readonly ActivityLogService $activityLogService) {}

    public function testConnection(): array
    {
        try {
            $integration = Integration::query()->where('name', 'google_business_profile')->first();

            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Integration disabled.', 'data' => []];
            }

            $accessToken = $this->fetchAccessToken($integration);
            if ($accessToken === null) {
                return ['success' => false, 'message' => 'Unable to get GBP access token.', 'data' => []];
            }

            $accountId = (string) $integration->getCredential('account_id');
            $locationId = (string) $integration->getCredential('location_id');
            if ($accountId === '' || $locationId === '') {
                return ['success' => false, 'message' => 'Missing required credentials.', 'data' => []];
            }

            $url = sprintf('https://mybusiness.googleapis.com/v4/accounts/%s/locations/%s/reviews', $accountId, $locationId);
            $response = Http::withToken($accessToken)
                ->timeout(12)
                ->connectTimeout(5)
                ->get($url, ['pageSize' => 1]);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_test_failure', 'integrations', 'Google Business Profile test failed.');

                return ['success' => false, 'message' => 'Google Business Profile connection failed.', 'data' => ['status' => $response->status()]];
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_test_success', 'integrations', 'Google Business Profile test passed.');

            return ['success' => true, 'message' => 'Google Business Profile connection successful.', 'data' => ['status' => $response->status()]];
        } catch (\Throwable $exception) {
            Log::error('Google Business Profile test failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_test_failure', 'integrations', 'Google Business Profile test failed.');

            return ['success' => false, 'message' => 'Google Business Profile test failed.', 'data' => []];
        }
    }

    public function syncReviews(): array
    {
        try {
            $integration = Integration::query()->where('name', 'google_business_profile')->first();
            if (! $integration instanceof Integration || ! $integration->is_enabled) {
                return ['success' => false, 'message' => 'Google Business Profile integration is disabled.', 'count' => 0];
            }

            $accessToken = $this->fetchAccessToken($integration);
            if ($accessToken === null) {
                return ['success' => false, 'message' => 'Unable to get GBP access token.', 'count' => 0];
            }

            $accountId = (string) $integration->getCredential('account_id');
            $locationId = (string) $integration->getCredential('location_id');
            if ($accountId === '' || $locationId === '') {
                return ['success' => false, 'message' => 'Missing account/location IDs.', 'count' => 0];
            }

            $url = sprintf('https://mybusiness.googleapis.com/v4/accounts/%s/locations/%s/reviews', $accountId, $locationId);
            $response = Http::withToken($accessToken)
                ->timeout(20)
                ->connectTimeout(5)
                ->get($url, ['pageSize' => 50, 'orderBy' => 'updateTime desc']);

            if (! $response->successful()) {
                $this->activityLogService->log('integration_sync_failure', 'integrations', 'Google Business Profile review sync failed.');

                return ['success' => false, 'message' => 'Google review sync request failed.', 'count' => 0];
            }

            $reviews = $response->json('reviews') ?? [];
            $count = 0;
            foreach ($reviews as $review) {
                $name = (string) ($review['name'] ?? '');
                $reviewId = trim((string) last(explode('/', $name)));
                if ($reviewId === '') {
                    continue;
                }

                $stars = (string) ($review['starRating'] ?? '');
                $ratingMap = [
                    'ONE' => 1,
                    'TWO' => 2,
                    'THREE' => 3,
                    'FOUR' => 4,
                    'FIVE' => 5,
                ];
                $starRating = $ratingMap[$stars] ?? 0;

                GoogleBusinessReview::query()->updateOrCreate(
                    ['review_id' => $reviewId],
                    [
                        'integration_id' => $integration->id,
                        'reviewer_name' => (string) data_get($review, 'reviewer.displayName', ''),
                        'star_rating' => $starRating,
                        'comment' => (string) ($review['comment'] ?? ''),
                        'review_time' => $review['createTime'] ?? null,
                        'raw_payload' => $review,
                    ]
                );
                $count++;
            }

            $integration->forceFill(['last_used_at' => now()])->save();
            $this->activityLogService->log('integration_sync_success', 'integrations', "Google review sync completed with {$count} items.");

            return ['success' => true, 'message' => 'Google reviews synced.', 'count' => $count];
        } catch (\Throwable $exception) {
            Log::error('Google review sync failed.', ['error' => $exception->getMessage()]);
            $this->activityLogService->log('integration_sync_failure', 'integrations', 'Google review sync failed.');

            return ['success' => false, 'message' => 'Google review sync failed.', 'count' => 0];
        }
    }

    private function fetchAccessToken(Integration $integration): ?string
    {
        $clientId = (string) env('MEDCA_GMB_CLIENT_ID');
        $clientSecret = (string) env('MEDCA_GMB_CLIENT_SECRET');
        $refreshToken = (string) $integration->getCredential('oauth_refresh_token');

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            return null;
        }

        $response = Http::asForm()
            ->timeout(12)
            ->connectTimeout(5)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $token = (string) ($response->json('access_token') ?? '');

        return $token !== '' ? $token : null;
    }
}
