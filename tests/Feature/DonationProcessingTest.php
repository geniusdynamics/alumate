<?php

namespace Tests\Feature;

use App\Models\CampaignDonation;
use App\Models\FundraisingCampaign;
use App\Models\RecurringDonation;
use App\Models\TaxReceipt;
use App\Models\User;
use App\Services\DonationProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationProcessingTest extends TestCase
{
    use RefreshDatabase;

    private DonationProcessingService $donationService;

    private User $donor;

    private FundraisingCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donationService = app(DonationProcessingService::class);
        $this->donor = User::factory()->create();
        $this->campaign = FundraisingCampaign::factory()->create();
    }

    public function test_can_process_one_time_donation(): void
    {
        $donationData = [
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'payment_method' => 'stripe',
            'is_recurring' => false,
        ];

        $paymentData = [
            'payment_method_id' => 'pm_test_card',
        ];

        $donation = $this->donationService->processDonation($donationData, $paymentData);

        $this->assertInstanceOf(CampaignDonation::class, $donation);
        $this->assertEquals(100.00, $donation->amount);
        $this->assertEquals('stripe', $donation->payment_method);
        $this->assertFalse($donation->is_recurring);
    }

    public function test_can_setup_recurring_donation(): void
    {
        $donationData = [
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'amount' => 50.00,
            'currency' => 'USD',
            'payment_method' => 'stripe',
            'is_recurring' => true,
            'recurring_frequency' => 'monthly',
        ];

        $paymentData = [
            'payment_method_id' => 'pm_test_card',
        ];

        $donation = $this->donationService->processDonation($donationData, $paymentData);

        $this->assertTrue($donation->is_recurring);
        $this->assertEquals('monthly', $donation->recurring_frequency);

        // Check that recurring donation record was created
        $this->assertDatabaseHas('recurring_donations', [
            'original_donation_id' => $donation->id,
            'donor_id' => $this->donor->id,
            'amount' => 50.00,
            'frequency' => 'monthly',
            'status' => 'active',
        ]);
    }

    public function test_can_process_recurring_payment(): void
    {
        $recurringDonation = RecurringDonation::factory()
            ->active()
            ->dueForPayment()
            ->create([
                'campaign_id' => $this->campaign->id,
                'donor_id' => $this->donor->id,
            ]);

        $donation = $this->donationService->processRecurringPayment($recurringDonation);

        $this->assertInstanceOf(CampaignDonation::class, $donation);
        $this->assertEquals($recurringDonation->amount, $donation->amount);
        $this->assertEquals('completed', $donation->status);

        // Check that recurring donation was updated
        $recurringDonation->refresh();
        $this->assertEquals(1, $recurringDonation->total_payments);
        $this->assertEquals($donation->amount, $recurringDonation->total_amount_collected);
    }

    public function test_can_cancel_recurring_donation(): void
    {
        $recurringDonation = RecurringDonation::factory()
            ->active()
            ->create([
                'donor_id' => $this->donor->id,
            ]);

        $success = $this->donationService->cancelRecurringDonation($recurringDonation, 'Test cancellation');

        $this->assertTrue($success);

        $recurringDonation->refresh();
        $this->assertEquals('cancelled', $recurringDonation->status);
        $this->assertEquals('Test cancellation', $recurringDonation->cancellation_reason);
        $this->assertNotNull($recurringDonation->cancelled_at);
    }

    public function test_can_generate_tax_receipt(): void
    {
        // Create some completed donations for the donor
        CampaignDonation::factory()
            ->completed()
            ->count(3)
            ->create([
                'donor_id' => $this->donor->id,
                'campaign_id' => $this->campaign->id,
                'processed_at' => now()->subMonths(6),
            ]);

        $taxYear = now()->year;
        $receipt = $this->donationService->generateTaxReceipt($this->donor->id, $taxYear);

        $this->assertInstanceOf(TaxReceipt::class, $receipt);
        $this->assertEquals($this->donor->id, $receipt->donor_id);
        $this->assertEquals($taxYear, $receipt->tax_year);
        $this->assertCount(3, $receipt->donations);
    }

    public function test_cannot_generate_duplicate_tax_receipt(): void
    {
        $taxYear = now()->year;

        // Create existing receipt
        TaxReceipt::factory()->create([
            'donor_id' => $this->donor->id,
            'tax_year' => $taxYear,
        ]);

        $receipt = $this->donationService->generateTaxReceipt($this->donor->id, $taxYear);

        $this->assertNull($receipt);
    }

    public function test_can_refund_donation(): void
    {
        $donation = CampaignDonation::factory()
            ->completed()
            ->create([
                'donor_id' => $this->donor->id,
                'amount' => 100.00,
                'payment_method' => 'stripe',
            ]);

        $success = $this->donationService->refundDonation($donation, 50.00, 'Partial refund test');

        $this->assertTrue($success);

        $donation->refresh();
        $this->assertEquals('partially_refunded', $donation->status);
        $this->assertArrayHasKey('refund_amount', $donation->payment_data);
        $this->assertEquals(50.00, $donation->payment_data['refund_amount']);
    }
}
