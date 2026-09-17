<?php

namespace App\Actions\Student\CanteenMenu;

use App\Models\CanteenMenu;
use App\Models\Product;
use Exception;

/**
 * Save a meal product's weekly menu (Students → Canteen Menu). Blank courses and
 * blank dishes are dropped, so an empty grid removes what parents see.
 *
 * @param  array<int, array{name?: ?string, note?: ?string, dishes?: array<int|string, array{name?: ?string, description?: ?string}>}>  $courses
 */
class SaveAction
{
    public function execute(int $productId, array $courses, int $userId): array
    {
        try {
            $product = Product::where('type', 'product')->find($productId);
            if (! $product) {
                throw new Exception("Product not found with the specified ID: $productId.", 1);
            }

            $clean = [];
            foreach ($courses as $index => $course) {
                $name = trim((string) ($course['name'] ?? ''));
                $dishes = [];
                foreach ((array) ($course['dishes'] ?? []) as $weekday => $dish) {
                    $weekday = (int) $weekday;
                    $dishName = trim((string) ($dish['name'] ?? ''));
                    if ($weekday < 1 || $weekday > 7 || $dishName === '') {
                        continue;
                    }
                    $dishes[(string) $weekday] = [
                        'name' => mb_substr($dishName, 0, 80),
                        'description' => filled($dish['description'] ?? null) ? mb_substr(trim((string) $dish['description']), 0, 200) : null,
                    ];
                }

                if ($name === '' && ! $dishes) {
                    continue;
                }
                if ($name === '') {
                    throw new Exception('Give course '.($index + 1).' a name, for example "Main dish".', 1);
                }

                $clean[] = [
                    'name' => mb_substr($name, 0, 60),
                    'note' => filled($course['note'] ?? null) ? mb_substr(trim((string) $course['note']), 0, 80) : null,
                    'dishes' => $dishes,
                ];
            }

            if (count($clean) > CanteenMenu::MAX_COURSES) {
                throw new Exception('A menu can have up to '.CanteenMenu::MAX_COURSES.' courses.', 1);
            }

            $menu = CanteenMenu::updateOrCreate(['product_id' => $product->id], ['courses' => $clean, 'updated_by' => $userId]);

            $return['success'] = true;
            $return['message'] = 'Menu saved for '.$product->name;
            $return['data'] = $menu;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
