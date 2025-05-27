<?php

namespace Tests\Unit\User;

use App\Actions\User\DeleteAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteActionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_a_user()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $action = new DeleteAction();
        $result = $action->execute($user->id);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Successfully Deleted User', $result['message']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_user_does_not_exist()
    {
        // Arrange
        $nonExistentId = 999;

        // Act
        $action = new DeleteAction();
        $result = $action->execute($nonExistentId);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', strtolower($result['message']));
    }
}
