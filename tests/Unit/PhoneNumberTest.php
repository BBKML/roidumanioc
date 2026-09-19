<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalizes_a_local_number_with_separators(): void
    {
        $this->assertSame('0712345678', PhoneNumber::normalize('07 12 34 56 78'));
        $this->assertSame('0712345678', PhoneNumber::normalize('07.12.34.56.78'));
        $this->assertSame('0712345678', PhoneNumber::normalize('07-12-34-56-78'));
    }

    public function test_strips_the_plus_225_country_code(): void
    {
        $this->assertSame('0712345678', PhoneNumber::normalize('+225 07 12 34 56 78'));
    }

    public function test_strips_the_00225_country_code(): void
    {
        $this->assertSame('0712345678', PhoneNumber::normalize('00225 07 12 34 56 78'));
    }

    public function test_00225_and_plus_225_and_local_forms_normalize_to_the_same_value(): void
    {
        $local = PhoneNumber::normalize('07 12 34 56 78');

        $this->assertSame($local, PhoneNumber::normalize('+225 07 12 34 56 78'));
        $this->assertSame($local, PhoneNumber::normalize('00225 07 12 34 56 78'));
    }

    public function test_null_and_empty_input_normalize_to_null(): void
    {
        $this->assertNull(PhoneNumber::normalize(null));
        $this->assertNull(PhoneNumber::normalize(''));
        $this->assertNull(PhoneNumber::normalize('   '));
    }
}
