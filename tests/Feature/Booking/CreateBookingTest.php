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
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreateBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_booking_when_room_available(): void
    {
        Notification::fake();

        $type = Type::factory()->create();
        $status = RoomStatus::factory()->create();
        $room = Room::factory()->create([
            'type_id' => $type->id,
            'room_status_id' => $status->id,
            'capacity' => 2,
            'price' => 1000000,
        ]);

        $employee = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        $customerUser = User::factory()->isCustomer()->create([
            'email_verified_at' => now(),
        ]);
        $customer = Customer::factory()->create([
            'user_id' => $customerUser->id,
        ]);

        $checkIn = Carbon::now()->addDays(7)->format('Y-m-d');
        $checkOut = Carbon::now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($employee)->post(route('transaction.reservation.payDownPayment', [$customer, $room]), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'downPayment' => Helper::getTotalPayment(Helper::getDateDifference($checkIn, $checkOut), $room->price),
        ]);

        $response->assertRedirect(route('transaction.index'));
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    public function test_booking_creation_rejected_when_room_already_reserved_for_dates(): void
    {
        $type = Type::factory()->create();
        $status = RoomStatus::factory()->create();
        $room = Room::factory()->create([
            'type_id' => $type->id,
            'room_status_id' => $status->id,
        ]);

        $employee = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        $firstCustomerUser = User::factory()->isCustomer()->create();
        $firstCustomer = Customer::factory()->create([
            'user_id' => $firstCustomerUser->id,
        ]);

        $checkIn = Carbon::now()->addDays(7)->format('Y-m-d');
        $checkOut = Carbon::now()->addDays(9)->format('Y-m-d');

        Transaction::create([
            'user_id' => $employee->id,
            'customer_id' => $firstCustomer->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'Reservation',
        ]);

        $secondCustomerUser = User::factory()->isCustomer()->create();
        $secondCustomer = Customer::factory()->create([
            'user_id' => $secondCustomerUser->id,
        ]);

        $response = $this->actingAs($employee)->from(route('transaction.reservation.confirmation', [$secondCustomer, $room, $checkIn, $checkOut]))
            ->post(route('transaction.reservation.payDownPayment', [$secondCustomer, $room]), [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'downPayment' => 100000,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('failed');
        $this->assertEquals(1, Transaction::where('room_id', $room->id)->count());
    }
}
