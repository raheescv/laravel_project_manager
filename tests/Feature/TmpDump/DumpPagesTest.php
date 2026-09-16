<?php

use App\Models\UserHasBranch;
use Tests\Support\PosWorld;

it('dumps pages', function (): void {
    $world = PosWorld::create();
    UserHasBranch::create(['user_id' => $world->user->id, 'branch_id' => $world->branch->id]);
    $world->addBranch('Market City', 'MC');
    UserHasBranch::create(['user_id' => $world->user->id, 'branch_id' => \App\Models\Branch::where('code', 'MC')->value('id')]);

    foreach (['dashboard' => '/dashboard', 'sales' => '/sale', 'users' => '/users'] as $name => $path) {
        $response = $this->actingAs($world->user)->withSession(['tenant_id' => $world->tenant->id])->get($world->url($path));
        file_put_contents('/private/tmp/claude-501/-Users-Shared-sites-personal-main-projects-project-manager/e17a1d17-4f7d-4bcc-a664-f9857643d2c1/scratchpad/page-'.$name.'.html', $response->status().PHP_EOL.$response->getContent());
    }
    expect(true)->toBeTrue();
});
