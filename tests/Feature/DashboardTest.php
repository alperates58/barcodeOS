<?php

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->seed();
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('currentPlan.slug', 'free')
                ->where('usageSummary.daily_generation_limit.limit', 10)
                ->where('usageSummary.daily_generation_limit.used', 0)
                ->where('usageSummary.monthly_generation_limit.limit', 300)
                ->where('usageSummary.monthly_generation_limit.used', 0)
                ->where('recentBarcodes', []));
    }

    public function test_dashboard_shows_real_empty_state_when_history_is_empty(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('recentBarcodes', []));
    }
}
