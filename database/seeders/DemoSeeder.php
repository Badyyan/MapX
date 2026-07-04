<?php

namespace Database\Seeders;

use App\Models\AutoReplyRule;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\LocalRankSnapshot;
use App\Models\PlatformConnection;
use App\Models\Post;
use App\Models\PostStatus;
use App\Models\PresenceMetric;
use App\Models\QrCampaign;
use App\Models\QrFeedback;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\TrackedKeyword;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\CompanyProvisioner;
use App\Services\SentimentClassifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo mode (BRD 5.8): a fully populated tenant with synthetic data so the
 * whole product can be explored without connecting real accounts.
 *
 * Login: demo@mapx.app / password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(CompanyProvisioner::class);
        $classifier = app(SentimentClassifier::class);

        $admin = $provisioner->provision(
            [
                'name' => 'Aroma Coffee Roasters',
                'legal_name' => 'Aroma Trading Co. LLC',
                'industry' => 'Restaurants & Cafes',
                'website' => 'https://aroma.example.sa',
                'phone' => '+966112345678',
                'email' => 'hello@aroma.example.sa',
                'is_demo' => true,
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'demo@mapx.app',
                'password' => 'password',
            ],
        );

        $company = $admin->company;

        // Paid subscription so nothing is locked in the demo.
        $company->subscription->update([
            'plan' => 'monthly',
            'status' => 'active',
            'branch_limit' => null,
            'current_period_start' => now()->subDays(12),
            'current_period_end' => now()->addDays(18),
        ]);

        // Extra team members on the template roles.
        $roles = $company->roles()->pluck('id', 'slug');
        User::create(['company_id' => $company->id, 'role_id' => $roles['manager'], 'name' => 'Sara Al-Qahtani', 'email' => 'manager@mapx.app', 'password' => 'password']);
        User::create(['company_id' => $company->id, 'role_id' => $roles['agent'], 'name' => 'Fahad Al-Otaibi', 'email' => 'agent@mapx.app', 'password' => 'password']);

        // Branches.
        $branchData = [
            ['name' => 'Aroma — Olaya', 'city' => 'Riyadh', 'address' => 'Olaya St, Al Olaya', 'lat' => 24.6944, 'lng' => 46.6850, 'verification_status' => 'verified'],
            ['name' => 'Aroma — Al Nakheel', 'city' => 'Riyadh', 'address' => 'King Fahd Rd, Al Nakheel', 'lat' => 24.7570, 'lng' => 46.6390, 'verification_status' => 'pending'],
            ['name' => 'Aroma — Jeddah Corniche', 'city' => 'Jeddah', 'address' => 'Corniche Rd, Ash Shati', 'lat' => 21.6003, 'lng' => 39.1099, 'verification_status' => 'unverified'],
        ];

        $branches = collect($branchData)->map(fn ($data) => Branch::create([
            ...$data,
            'company_id' => $company->id,
            'description' => 'Specialty coffee, fresh bakes and a calm place to work.',
            'region' => $data['city'] === 'Riyadh' ? 'Riyadh Province' : 'Makkah Province',
            'categories' => ['Coffee shop', 'Cafe', 'Bakery'],
            'phone' => '+96611'.random_int(1000000, 9999999),
            'whatsapp_number' => '+9665'.random_int(10000000, 99999999),
            'website' => 'https://aroma.example.sa',
            'email' => 'branch@aroma.example.sa',
            'hours' => collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
                ->mapWithKeys(fn ($day) => [$day => ['open' => '07:00', 'close' => '23:00']])->all(),
            'status' => 'active',
        ]));

        // Platform connections for every branch.
        foreach ($branches as $branch) {
            foreach (['google', 'facebook', 'instagram', 'waze', 'uber', 'careem', 'bolt'] as $platform) {
                PlatformConnection::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'platform' => $platform,
                    'status' => 'connected',
                    'sync_status' => $platform === 'instagram' && $branch->verification_status !== 'verified' ? 'out_of_sync' : 'synced',
                    'external_id' => $platform.'_'.Str::random(10),
                    'data_completeness' => $branch->completenessScore(),
                    'last_synced_at' => now()->subHours(random_int(1, 48)),
                ]);
            }
        }

        // Reviews: EN + AR across platforms and 6 months.
        $samples = [
            [5, 'Amazing coffee and very friendly staff. The V60 is the best in Riyadh!', 'en'],
            [5, 'قهوة ممتازة وخدمة رائعة، المكان نظيف وهادئ. أنصح فيه بشدة', 'ar'],
            [4, 'Great atmosphere for working, wifi is fast. Gets crowded in the evening.', 'en'],
            [4, 'المكان جميل والقهوة لذيذة، لكن الأسعار مرتفعة قليلاً', 'ar'],
            [5, 'Best cinnamon rolls in town. Highly recommend the Spanish latte.', 'en'],
            [3, 'Coffee was good but we waited 20 minutes for two drinks.', 'en'],
            [2, 'الخدمة بطيئة جداً والطلب تأخر كثير، للأسف تجربة مخيبة', 'ar'],
            [1, 'Ordered a flat white, got a cold latte. Staff was rude when I asked to fix it.', 'en'],
            [5, 'مكان مريح للعمل والدراسة، الانترنت سريع والموظفين محترمين', 'ar'],
            [4, 'Solid specialty coffee. Parking is difficult on weekends.', 'en'],
            [2, 'Very slow service and the table was dirty. Disappointed.', 'en'],
            [5, 'أفضل قهوة مختصة جربتها، الكرواسون طازج والجو هادي', 'ar'],
        ];

        $reviewers = ['Mohammed A.', 'Sara K.', 'Abdullah M.', 'Noura S.', 'Faisal T.', 'Lina H.', 'Omar B.', 'Reem D.', 'خالد العتيبي', 'منيرة القحطاني', 'سلطان الحربي', 'هند الشمري'];

        foreach ($branches as $branch) {
            foreach (range(1, 40) as $i) {
                [$rating, $content, $language] = $samples[array_rand($samples)];
                $platform = collect(['google', 'google', 'google', 'facebook'])->random();
                $date = now()->subDays(random_int(0, 180))->subMinutes(random_int(0, 1440));

                $review = Review::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'platform' => $platform,
                    'external_id' => Str::uuid()->toString(),
                    'author_name' => $reviewers[array_rand($reviewers)],
                    'rating' => $rating,
                    'content' => $content,
                    'language' => $language,
                    'sentiment' => $classifier->classify($content, $rating),
                    'topics' => $classifier->extractTopics($content, 3),
                    'review_date' => $date,
                    'is_replied' => $replied = (bool) random_int(0, 1),
                    'created_at' => $date,
                ]);

                if ($replied) {
                    ReviewReply::create([
                        'review_id' => $review->id,
                        'user_id' => $admin->id,
                        'content' => $language === 'ar'
                            ? 'شكراً جزيلاً على تقييمك! نسعد بخدمتك دائماً.'
                            : 'Thank you so much for your feedback! We hope to see you again soon.',
                        'source' => collect(['manual', 'ai', 'auto_rule'])->random(),
                        'status' => 'sent',
                        'sent_at' => $date->copy()->addHours(random_int(1, 24)),
                    ]);
                }
            }
        }

        // A couple of auto-replies waiting for approval.
        Review::where('company_id', $company->id)->where('is_replied', false)->limit(3)->get()
            ->each(fn ($review) => ReviewReply::create([
                'review_id' => $review->id,
                'content' => 'Thank you for sharing your experience! Our team is always working to improve.',
                'source' => 'auto_rule',
                'status' => 'pending_approval',
            ]));

        // Auto-reply rules.
        AutoReplyRule::create([
            'company_id' => $company->id,
            'name' => 'Thank happy customers',
            'min_rating' => 4, 'max_rating' => 5,
            'sentiment' => 'positive',
            'platforms' => ['google', 'facebook'],
            'delay_minutes' => 60,
            'require_approval' => false,
            'priority' => 10,
            'templates' => [
                'Thank you {name}! We are thrilled you enjoyed your visit to {branch}. ☕',
                'شكراً {name}! يسعدنا أن تجربتك في {branch} كانت رائعة.',
            ],
        ]);
        AutoReplyRule::create([
            'company_id' => $company->id,
            'name' => 'Escalate negative reviews',
            'min_rating' => 1, 'max_rating' => 2,
            'delay_minutes' => 0,
            'require_approval' => true,
            'priority' => 20,
            'templates' => [
                'We are truly sorry, {name}. Our manager will contact you shortly to make this right.',
            ],
        ]);

        // QR campaigns + internal feedback.
        foreach ($branches->take(2) as $branch) {
            $campaign = QrCampaign::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'Table QR — '.$branch->name,
                'slug' => Str::lower(Str::random(10)),
                'rating_scale' => 10,
                'threshold' => 8,
                'is_active' => true,
                'scans_count' => $scans = random_int(150, 400),
                'positive_count' => (int) ($scans * 0.55),
                'negative_count' => (int) ($scans * 0.12),
            ]);

            foreach (range(1, 6) as $i) {
                QrFeedback::create([
                    'qr_campaign_id' => $campaign->id,
                    'branch_id' => $branch->id,
                    'company_id' => $company->id,
                    'rating' => random_int(1, 7),
                    'comment' => collect([
                        'The order took too long and my coffee was cold.',
                        'الطلب تأخر والموظف ما كان متعاون',
                        'Music was too loud, hard to have a meeting.',
                        'المقاعد غير مريحة والمكان مزدحم',
                    ])->random(),
                    'customer_name' => $reviewers[array_rand($reviewers)],
                    'customer_phone' => '+9665'.random_int(10000000, 99999999),
                    'status' => collect(['new', 'in_progress', 'resolved'])->random(),
                    'created_at' => now()->subDays(random_int(0, 30)),
                ]);
            }
        }

        // Posts.
        $posts = [
            ['title' => 'Ramadan evening offer', 'content' => "🌙 Ramadan Kareem! Join us after Iftar for our special date-caramel latte and 20% off all pastries.\n\n📍 All Aroma branches — see you tonight!", 'status' => 'published'],
            ['title' => 'New single-origin beans', 'content' => "☕ New arrival: Ethiopian Yirgacheffe, roasted in-house this week. Floral, citrusy, unforgettable.\n\nAsk your barista for a pour-over today.", 'status' => 'published'],
            ['title' => 'Weekend brunch launch', 'content' => "🥐 Weekend brunch is here! Shakshuka, sourdough toast and our signature Spanish latte, every Fri–Sat from 9 AM.", 'status' => 'scheduled'],
        ];

        foreach ($posts as $index => $data) {
            $post = Post::create([
                'company_id' => $company->id,
                'user_id' => $admin->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'platforms' => ['google', 'facebook', 'instagram'],
                'branch_ids' => $branches->pluck('id')->all(),
                'status' => $data['status'],
                'ai_generated' => $index === 1,
                'scheduled_at' => $data['status'] === 'scheduled' ? now()->addDays(2)->setTime(9, 0) : null,
                'published_at' => $data['status'] === 'published' ? now()->subDays(7 * ($index + 1)) : null,
            ]);

            if ($data['status'] === 'published') {
                foreach ($branches as $branch) {
                    foreach ($post->platforms as $platform) {
                        PostStatus::create([
                            'post_id' => $post->id,
                            'branch_id' => $branch->id,
                            'platform' => $platform,
                            'status' => 'published',
                            'external_id' => $platform.'_post_'.Str::random(8),
                            'published_at' => $post->published_at,
                        ]);
                    }
                }
            }
        }

        // Presence metrics: 60 days of calls / routes / clicks per branch.
        foreach ($branches as $branch) {
            foreach (range(0, 59) as $daysAgo) {
                $date = now()->subDays($daysAgo)->toDateString();
                foreach (['calls' => [2, 15], 'routes' => [5, 40], 'website_clicks' => [3, 25]] as $metric => [$min, $max]) {
                    PresenceMetric::create([
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'platform' => 'google',
                        'metric' => $metric,
                        'value' => random_int($min, $max),
                        'date' => $date,
                    ]);
                }
            }
        }

        // Rank tracking: keywords + 30 days of positions.
        foreach (['specialty coffee riyadh', 'coffee shop near me', 'best latte jeddah'] as $keyword) {
            TrackedKeyword::create(['company_id' => $company->id, 'keyword' => $keyword, 'radius_km' => 5]);
        }

        foreach ($branches as $branch) {
            foreach (['specialty coffee riyadh', 'coffee shop near me', 'best latte jeddah'] as $keyword) {
                $position = random_int(8, 18);
                foreach (range(29, 0) as $daysAgo) {
                    $position = max(1, min(30, $position + random_int(-2, 1))); // slow improvement
                    LocalRankSnapshot::create([
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'keyword' => $keyword,
                        'platform' => 'google',
                        'position' => $position,
                        'radius_km' => 5,
                        'tracked_at' => now()->subDays($daysAgo)->toDateString(),
                    ]);
                }
            }
        }

        // Invoices.
        foreach (range(1, 3) as $monthsAgo) {
            Invoice::create([
                'company_id' => $company->id,
                'subscription_id' => $company->subscription->id,
                'number' => 'INV-'.now()->subMonths($monthsAgo)->format('Ym').'-'.strtoupper(Str::random(6)),
                'amount' => 3 * 99.00,
                'currency' => 'SAR',
                'branch_count' => 3,
                'period_start' => now()->subMonths($monthsAgo)->toDateString(),
                'period_end' => now()->subMonths($monthsAgo - 1)->toDateString(),
                'status' => 'paid',
                'paid_at' => now()->subMonths($monthsAgo),
                'gateway' => 'manual',
                'gateway_reference' => 'demo-'.Str::random(8),
            ]);
        }

        // A verification request thread.
        $verification = VerificationRequest::create([
            'company_id' => $company->id,
            'branch_id' => $branches[1]->id,
            'status' => 'in_review',
            'notes' => 'Google postcard never arrived. Requesting video verification instead.',
        ]);
        $verification->messages()->createMany([
            ['user_id' => $admin->id, 'is_support' => false, 'message' => 'We have been waiting 3 weeks for the postcard. Can you help?'],
            ['user_id' => null, 'is_support' => true, 'message' => 'We escalated this with Google support and requested video verification. Expect an update within 2 business days.'],
        ]);
    }
}
