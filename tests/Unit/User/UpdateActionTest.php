<?php

namespace Tests\Unit\User;

use App\Actions\User\UpdateAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_a_user()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        // Act
        $action = new UpdateAction();
        $result = $action->execute($updateData, $user->id);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Successfully Updated User', $result['message']);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_updating_with_existing_email()
    {
        // Arrange
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'existing@example.com',
        ];

        // Act
        $action = new UpdateAction();
        $result = $action->execute($updateData, $user->id);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email has already been taken', strtolower($result['message']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_user_does_not_exist()
    {
        // Arrange
        $nonExistentId = 999;
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        // Act
        $action = new UpdateAction();
        $result = $action->execute($updateData, $nonExistentId);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', strtolower($result['message']));
    }
}
