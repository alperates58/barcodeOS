<?php

namespace Tests\Unit;

use App\Models\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function test_active_and_trialing_statuses_are_paid_access_eligible(): void
    {
        foreach ([Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING] as $status) {
            $subscription = new Subscription(['status' => $status]);

            $this->assertTrue($subscription->isPaidAccessEligible());
        }
    }

    public function test_non_paid_statuses_are_not_paid_access_eligible(): void
    {
        foreach ([
            Subscription::STATUS_PAST_DUE,
            Subscription::STATUS_UNPAID,
            Subscription::STATUS_CANCELED,
            Subscription::STATUS_EXPIRED,
            Subscription::STATUS_INCOMPLETE,
            Subscription::STATUS_INCOMPLETE_EXPIRED,
        ] as $status) {
            $subscription = new Subscription(['status' => $status]);

            $this->assertFalse($subscription->isPaidAccessEligible());
        }
    }
}
