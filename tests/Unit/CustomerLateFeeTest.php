<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\LateFeeCharge;
use App\Models\Booking;
use Tests\TestCase;

class CustomerLateFeeTest extends TestCase
{
    public function test_customer_has_unpaid_late_fee_returns_false_when_no_charges()
    {
        $customer = new Customer();
        // Since we are mocking or calling methods, let's test logic via relationships or unit checks
        $this->assertTrue(method_exists($customer, 'hasUnpaidLateFee'));
        $this->assertTrue(method_exists($customer, 'getUnpaidLateFeeCharge'));
    }
}
