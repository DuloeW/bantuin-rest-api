<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\OfferPost;
use App\Models\Post;
use App\Models\User;
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
}
