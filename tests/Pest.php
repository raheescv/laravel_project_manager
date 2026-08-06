<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
| Plain (non-Laravel) unit tests run in the same process as app-booting tests.
| When a Laravel test case tears down, it flushes its Application but leaves it
| memoized in Facade::$resolvedInstance — so the next plain test that boots an
| Eloquent model whose traits touch a facade (e.g. Auditable -> Config) blows up
| with "Target class [config] does not exist", depending purely on file order.
| Detect that stale state and clear it so facade-guarded boot code (like
| bootAuditable) sees "no app" and skips cleanly.
*/
pest()->beforeEach(function (): void {
    $app = Illuminate\Support\Facades\Facade::getFacadeApplication();
    if ($app && ! $app->bound('config')) {
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication(null);
    }
})->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
