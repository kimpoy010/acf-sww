<?php

namespace Tests\Unit;

use App\Rules\StrongPassword;
use Tests\TestCase;

class StrongPasswordTest extends TestCase
{
    private function passes(string $password, ?string $username = null): bool
    {
        $failed = false;
        (new StrongPassword($username))->validate('password', $password, function () use (&$failed) {
            $failed = true;
        });

        return ! $failed;
    }

    public function test_a_password_meeting_every_rule_passes(): void
    {
        $this->assertTrue($this->passes('Str0ngP@ssw'));
    }

    public function test_a_password_under_eight_characters_fails(): void
    {
        $this->assertFalse($this->passes('Str0ng!'));
    }

    public function test_a_password_without_an_uppercase_letter_fails(): void
    {
        $this->assertFalse($this->passes('str0ngpassw'));
    }

    public function test_a_password_without_a_lowercase_letter_fails(): void
    {
        $this->assertFalse($this->passes('STR0NGPASSW'));
    }

    public function test_a_password_without_a_number_fails(): void
    {
        $this->assertFalse($this->passes('StrongPassword'));
    }

    public function test_a_password_with_three_identical_characters_in_a_row_fails(): void
    {
        $this->assertFalse($this->passes('Str0nggg'));
    }

    public function test_a_password_with_an_ascending_sequential_run_fails(): void
    {
        $this->assertFalse($this->passes('MyAbc1word'));
    }

    public function test_a_password_with_a_descending_sequential_run_fails(): void
    {
        $this->assertFalse($this->passes('MyCba1word'));
    }

    public function test_a_password_with_an_ascending_numeric_sequential_run_fails(): void
    {
        $this->assertFalse($this->passes('Password123'));
    }

    public function test_a_password_containing_the_username_fails(): void
    {
        $this->assertFalse($this->passes('Juandelacruz1', 'juandelacruz'));
    }

    public function test_a_username_check_is_case_insensitive(): void
    {
        $this->assertFalse($this->passes('JUANDELACRUZ1a', 'juandelacruz'));
    }

    public function test_a_username_under_three_characters_is_never_checked(): void
    {
        // A short username (e.g. 'ab') would false-positive on almost any
        // password, so it's simply never checked.
        $this->assertTrue($this->passes('Str0ngPassab', 'ab'));
    }

    public function test_no_username_given_skips_the_check_entirely(): void
    {
        $this->assertTrue($this->passes('Str0ngPassw'));
    }

    public function test_a_symbol_is_not_required(): void
    {
        $this->assertTrue($this->passes('Str0ngPassw'));
    }
}
