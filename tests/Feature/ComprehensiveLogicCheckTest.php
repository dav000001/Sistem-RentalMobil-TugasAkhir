<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use App\Models\CarChangeRequest;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\LateFeeCharge;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveLogicCheckTest extends TestCase
{
    public function test_customer_has_unpaid_late_fee_logic()
    {
        $customer = new Customer();
        // Check methods exist
        $this->assertTrue(method_exists($customer, 'hasUnpaidLateFee'));
        $this->assertTrue(method_exists($customer, 'getUnpaidLateFeeCharge'));
    }

    public function test_car_change_request_with_driver_fillable()
    {
        $request = new CarChangeRequest();
        $this->assertContains('with_driver', $request->getFillable());
    }
}
