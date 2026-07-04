<?php

namespace Tests\Feature;

use App\Models\AutoReplyRule;
use App\Models\Branch;
use App\Models\Review;
use App\Models\User;
use App\Services\AutoReplyEngine;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReplyEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );

        $this->branch = Branch::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Main',
        ]);
    }

    private function makeReview(array $overrides = []): Review
    {
        return Review::create([
            'company_id' => $this->admin->company_id,
            'branch_id' => $this->branch->id,
            'platform' => 'google',
            'external_id' => uniqid(),
            'author_name' => 'Ahmed',
            'rating' => 5,
            'content' => 'Great coffee!',
            'sentiment' => 'positive',
            'review_date' => now(),
            ...$overrides,
        ]);
    }

    public function test_matching_rule_creates_pending_reply_when_approval_required(): void
    {
        AutoReplyRule::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Happy',
            'min_rating' => 4, 'max_rating' => 5,
            'require_approval' => true,
            'templates' => ['Thanks {name} for visiting {branch}!'],
        ]);

        $reply = app(AutoReplyEngine::class)->process($this->makeReview());

        $this->assertNotNull($reply);
        $this->assertSame('pending_approval', $reply->status); // FR-17
        $this->assertSame('Thanks Ahmed for visiting Main!', $reply->content);
    }

    public function test_rule_outside_rating_range_does_not_match(): void
    {
        AutoReplyRule::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Happy only',
            'min_rating' => 4, 'max_rating' => 5,
            'templates' => ['Thanks!'],
        ]);

        $reply = app(AutoReplyEngine::class)->process($this->makeReview(['rating' => 2, 'sentiment' => 'negative']));

        $this->assertNull($reply);
    }

    public function test_highest_priority_rule_wins(): void
    {
        AutoReplyRule::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Low', 'min_rating' => 1, 'max_rating' => 5,
            'priority' => 1, 'templates' => ['low'],
        ]);
        AutoReplyRule::create([
            'company_id' => $this->admin->company_id,
            'name' => 'High', 'min_rating' => 1, 'max_rating' => 5,
            'priority' => 50, 'templates' => ['high'],
        ]);

        $reply = app(AutoReplyEngine::class)->process($this->makeReview());

        $this->assertSame('high', $reply->content);
    }

    public function test_already_replied_review_is_skipped(): void
    {
        AutoReplyRule::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Any', 'min_rating' => 1, 'max_rating' => 5,
            'templates' => ['hi'],
        ]);

        $review = $this->makeReview(['is_replied' => true]);

        $this->assertNull(app(AutoReplyEngine::class)->process($review));
    }
}
