<?php

namespace Tests\Feature\Booking;

use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_other_customer_booking(): void
    {
        $type = Type::factory()->create();
        $status = RoomStatus::factory()->create();
        $room = Room::factory()->create([
            'type_id' => $type->id,
            'room_status_id' => $status->id,
        ]);

        $ownerUser = User::factory()->isCustomer()->create([
            'email_verified_at' => now(),
        ]);
        $owner = Customer::factory()->create([
            'user_id' => $ownerUser->id,
        ]);

        $otherUser = User::factory()->isCustomer()->create([
            'email_verified_at' => now(),
        ]);

        $booking = Transaction::create([
            'user_id' => $ownerUser->id,
            'customer_id' => $owner->id,
            'room_id' => $room->id,
            'check_in' => Carbon::now()->addWeek()->format('Y-m-d'),
            'check_out' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
            'status' => 'Reservation',
        ]);

        $response = $this->actingAs($otherUser)->get(route('transaction.show', $booking));

        $response->assertStatus(403);
    }
}
