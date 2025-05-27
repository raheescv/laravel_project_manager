<?php

namespace Tests\Unit\User;

use App\Actions\User\CreateAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateActionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_user()
    {
        // Arrange
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $action = new CreateAction();
        $result = $action->execute($userData);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_email_is_already_taken()
    {
        // Arrange
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ];

        // Act
        $action = new CreateAction();
        $result = $action->execute($userData);

        // Assert
        $this->assertFalse($result['success']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_required_fields_are_missing()
    {
        // Arrange
        $userData = [
            'name' => 'Test User',
            // email is missing
            'password' => 'password123',
        ];

        // Act
        $action = new CreateAction();
        $result = $action->execute($userData);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email', strtolower($result['message']));
    }
}
