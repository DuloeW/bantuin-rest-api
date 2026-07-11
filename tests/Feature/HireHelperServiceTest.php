<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Offer;
use App\Models\OfferPost;
use App\Models\Post;
use App\Models\User;
use App\Service\Offer\FinalizeOfferService;
use App\Service\Offer\HireHelperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HireHelperServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HireHelperService $hireHelperService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hireHelperService = new HireHelperService();
    }

    public function test_book_helper_service_fails_if_helper_has_no_bank_account(): void
    {
        // Create category
        $category = Category::create([
            'title' => 'Cleaning',
            'slug' => 'cleaning',
        ]);

        // Create requester and helper
        $requester = User::factory()->create();
        $helper = User::factory()->create();

        // Create post (type: offer)
        $post = Post::create([
            'user_id' => $helper->id,
            'category_id' => $category->id,
            'title' => 'My Service',
            'description' => 'Service Description',
            'type' => 'offer',
        ]);

        OfferPost::create([
            'post_id' => $post->id,
            'base_price' => 50000,
            'working_hours' => '08:00 - 17:00',
            'address_details' => 'Jl. Merdeka No. 10',
            'location' => 'POINT(-8.4095 115.1889)',
        ]);

        $data = [
            'offered_price' => 60000,
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The helper must register a bank account before their service can be booked.');

        $this->hireHelperService->bookHelperService($post, $data, $requester->id);
    }

    public function test_book_helper_service_succeeds_if_helper_has_bank_account(): void
    {
        // Create category
        $category = Category::create([
            'title' => 'Cleaning',
            'slug' => 'cleaning',
        ]);

        // Create requester and helper
        $requester = User::factory()->create();
        $helper = User::factory()->create();

        // Create helper's bank account
        BankAccount::create([
            'user_id' => $helper->id,
            'bank_code' => 'bca',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'John Doe',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        // Create post (type: offer)
        $post = Post::create([
            'user_id' => $helper->id,
            'category_id' => $category->id,
            'title' => 'My Service',
            'description' => 'Service Description',
            'type' => 'offer',
        ]);

        OfferPost::create([
            'post_id' => $post->id,
            'base_price' => 50000,
            'working_hours' => '08:00 - 17:00',
            'address_details' => 'Jl. Merdeka No. 10',
            'location' => 'POINT(-8.4095 115.1889)',
        ]);

        $data = [
            'offered_price' => 60000,
        ];

        $result = $this->hireHelperService->bookHelperService($post, $data, $requester->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('Helper service booked successfully.', $result['message']);
        $this->assertDatabaseHas('offers', [
            'helper_id' => $helper->id,
            'requester_id' => $requester->id,
            'offered_price' => 60000,
        ]);
    }

    public function test_multiple_requesters_can_book_and_finalize_simultaneously(): void
    {
        $category = Category::create([
            'title' => 'Cleaning',
            'slug' => 'cleaning',
        ]);

        $requesterA = User::factory()->create();
        $requesterB = User::factory()->create();
        $helper = User::factory()->create();

        BankAccount::create([
            'user_id' => $helper->id,
            'bank_code' => 'bca',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'John Doe',
            'is_primary' => true,
            'is_verified' => false,
        ]);

        $post = Post::create([
            'user_id' => $helper->id,
            'category_id' => $category->id,
            'title' => 'My Service',
            'description' => 'Service Description',
            'type' => 'offer',
            'is_multiple' => true,
        ]);

        OfferPost::create([
            'post_id' => $post->id,
            'base_price' => 50000,
            'working_hours' => '08:00 - 17:00',
            'address_details' => 'Jl. Merdeka No. 10',
            'location' => 'POINT(-8.4095 115.1889)',
        ]);

        $data = [
            'offered_price' => 60000,
        ];

        // 1. Requester A books the service
        $resultA = $this->hireHelperService->bookHelperService($post, $data, $requesterA->id);
        $this->assertTrue($resultA['success']);
        $offerA = Offer::find($resultA['data']['id']);

        // 2. Requester B should be able to book the service concurrently
        $resultB = $this->hireHelperService->bookHelperService($post, $data, $requesterB->id);
        $this->assertTrue($resultB['success']);
        $offerB = Offer::find($resultB['data']['id']);

        // 3. Finalize Offer for Requester A
        $finalizeService = new FinalizeOfferService();
        $finalizeData = [
            'deadline' => now()->addDays(2)->toIso8601String(),
            'work_notes' => 'Notes for A',
            'agreed_price' => 60000,
        ];
        $finalizeResultA = $finalizeService->finalize($offerA, $requesterA->id, $finalizeData);
        $this->assertTrue($finalizeResultA['success']);

        // 4. Finalize Offer for Requester B should also succeed (concurrent transactions)
        $finalizeResultB = $finalizeService->finalize($offerB, $requesterB->id, $finalizeData);
        $this->assertTrue($finalizeResultB['success']);

        // Assert database states
        $this->assertDatabaseHas('transactions', [
            'requester_id' => $requesterA->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('transactions', [
            'requester_id' => $requesterB->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('accepted', $offerA->fresh()->status);
        $this->assertEquals('accepted', $offerB->fresh()->status);
    }
}
