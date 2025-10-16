<?php

namespace Tests\Feature\Booking;

use App\Helpers\Helper;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_before_deadline_without_penalty(): void
    {
        $type = Type::factory()->create();
        $status = RoomStatus::factory()->create();
        $room = Room::factory()->create([
            'type_id' => $type->id,
            'room_status_id' => $status->id,
            'price' => 1000000,
        ]);

        $user = User::factory()->isCustomer()->create([
            'email_verified_at' => now(),
        ]);
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'check_out' => Carbon::now()->addDays(12)->format('Y-m-d'),
            'status' => 'Reservation',
        ]);

        $response = $this->actingAs($user)->post(route('transaction.cancel', $transaction), [
            'reason' => 'Change of plans',
        ]);

        $response->assertRedirect();
        $transaction->refresh();

        $this->assertEquals('Cancelled', $transaction->status);
        $this->assertNull(optional($transaction->payments()->latest()->first())->price);
    }
}
