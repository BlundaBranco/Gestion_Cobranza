<?php

namespace Tests\Unit;

use App\Models\Transaction;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_payment_methods_constant_has_expected_options(): void
    {
        $this->assertSame(
            ['efectivo', 'transferencia', 'cheque', 'deposito'],
            array_keys(Transaction::PAYMENT_METHODS)
        );
    }

    public function test_label_accessor_returns_human_name(): void
    {
        $tx = new Transaction(['payment_method' => 'transferencia']);
        $this->assertSame('Transferencia', $tx->payment_method_label);
    }

    public function test_label_accessor_returns_null_when_unset(): void
    {
        $tx = new Transaction();
        $this->assertNull($tx->payment_method_label);
    }

    public function test_label_accessor_returns_null_for_unknown_value(): void
    {
        $tx = new Transaction(['payment_method' => 'bitcoin']);
        $this->assertNull($tx->payment_method_label);
    }

    public function test_payment_method_is_in_fillable(): void
    {
        $tx = new Transaction();
        $this->assertContains('payment_method', $tx->getFillable());
    }
}
