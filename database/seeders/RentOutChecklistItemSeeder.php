<?php

namespace Database\Seeders;

use App\Models\Checklist;
use Illuminate\Database\Seeder;

class RentOutChecklistItemSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        $data = [
            'External Entrance' => [
                'Door & Frame', 'Door Handle', 'Magic Eye', 'Lock Key Card Reader', 'Door Bell Switch',
            ],
            'Entrance Corridor' => [
                'Door Bell', 'Light Switches', 'Electrical Sockets', 'Key Card Holder', 'Main Door Double Lock',
                'Inner Door Frame', 'Wall', 'Ceiling', 'Lighting', 'Tiles', 'Skirting', 'Molding',
                'Main Electrical Breakers Board', 'DP Mirror & Frame',
            ],
            'Guest Toilet' => [
                'Door & Frame', 'Light Switches', 'Electrical Sockets', 'Mirror & Frame', 'Mirror LED Light',
                'Tumblers & Soap Dish', 'Tumblers Holders', 'Wash Basin Mixer', 'Wash Basin', 'Counter/Marble Top',
                'Vanity Counter/Drawers', 'Seat Cover', 'WC Seat', 'Flushing System', 'Shattaf Set',
                'Towel Rack/Rail', 'Towels (Bath/Hand/Face)', 'Ceiling', 'Tiles', 'Floor Drainage Cover', 'Dust Bin',
            ],
            'Kitchen' => [
                'Door & Frame', 'Light Switches', 'Electrical Sockets', 'Spotlight/s', 'Ceiling', 'Wall',
                'Counter Marble free from scratches and craks', 'Cabinets', 'Drawers/Rails', 'Kitchen Sink',
                'Kitchen Sink Mixer', 'Fridge (In & Out)', 'Microwave', 'Oven', 'Ceramic Hob', 'Kitchen Hood',
                'Washing Machine', 'Electric Kettle', 'Cutlery Set (Cups,Spoon, Fork, Knife & Tray)', 'Big Empty Vase',
                'Waste Bin (30 Ltr)', 'Fire Extiguisher', 'Fire Blanket', 'Floor Drainage Cover',
            ],
            'Living Room' => [
                'Light Switches', 'AC Thermostat', 'Sockets (Elec., Tel. & Internet)', 'Bulbs/Spot Lights/Chandlier',
                'LED Lights', 'Walls', 'Tiles & Grouting', 'Ceiling', 'Skirting & Moldings', 'Single Seater Sofa',
                'Double Seater Sofa', 'Triple Seater Sofa', 'Cushion/s', 'Coffee Table', 'Side Tables', 'Table Lamp/s',
                'Standing Lamp', 'Landline Telephone', 'Carpet', 'Bar Stools', 'Dinning Glass Table', 'Dinning Chairs',
                'Dinning Chest Cabinet/Drawers', 'TV & Remote', 'TV Table', 'TV Wall Frame & LED Lights',
                'Decorative Wall Frames & Mirrors', 'Serving Tray', 'Dining Table Vase/s', 'Tree Pot', 'Flower Pot',
                'Floor Vase', 'Ceramic/Glass Tray', 'Table Display Figurines', 'Clock', 'Windows/Door (Glass, Handle)',
                'Curtains Rails/Rod', 'Heavy Curtain', 'Light Curtain', 'Dust Bin (Swing type)',
            ],
            'Bedroom Bathroom' => [
                'Door & Frame', 'Light Switches', 'Electrical Sockets', 'Mirror & Frame', 'Mirror LED Light',
                'Tumblers Holders', 'Tumblers & Soap Dish', 'Wash Basin Mixer', 'Wash Basin', 'Counter/Marble Top',
                'Vanity Counter/Drawers', 'Seat Cover', 'WC Seat', 'Flushing System', 'Shattaf Set',
                'Shower Glass Partition & Frame', 'Shower Mixer/Bathub Mixer/Shower Head/s', 'Shower Drainage cover',
                'Towel Rack/Rail', 'Towels (Bath/Hand/Face)', 'Bath Tub', 'Ceiling', 'Tiles', 'Floor Drainage Cover', 'Dust Bin',
            ],
            'Bedroom' => [
                'Door & Frame', 'Light Switches', 'Sockets (Elec., Tel. & Internet)', 'Bulbs/Spot Lights/Chandlier',
                'LED Light/s', 'Walls', 'Ceiling', 'Skirting & Moldings', 'Tiles', 'AC Thermostat', 'Bed Base',
                'Head Rest', 'Mattress', 'Linens', 'Pillows', 'Bedside Table/s', 'Bedside Lamp/s', 'Carpet',
                'Cabinets', 'Safebox', 'Dressing Table', 'Dressing Chair', 'Dressing Mirror', 'Dresser Vase/s',
                'Decorative Wall Frame/s', 'Table Display Figurine/s', 'Windows (Glass, Handle)', 'Curtains Rails/Rod',
                'Heavy Curtain', 'Light Curtain',
            ],
            'Masters Bathroom' => [
                'Door & Frame', 'Light Switches', 'Electrical Sockets', 'Mirror & Frame', 'Mirror LED Light',
                'Tumblers Holders', 'Tumblers & Soap Dish', 'Wash Basin Mixer', 'Wash Basin', 'Marble Top',
                'Vanity Counter/Drawers', 'Seat Cover', 'WC Seat', 'Flushing System', 'Shattaf Set',
                'Shower Glass Partition & Frame', 'Shower Mixer/Bathub Mixer/Shower Head/s', 'Shower Drainage cover',
                'Towel Rack/Rail', 'Towels (Bath/Hand/Face)', 'Bath Tub', 'Ceiling', 'Tiles', 'Floor Drainage Cover', 'Dust Bin',
            ],
            'Masters Bedroom' => [
                'Door & Frame', 'Light Switches', 'Sockets (Elec., Tel. & Internet)', 'Bulbs/Spot Lights/Chandlier',
                'LED Light/s', 'Walls', 'Ceiling', 'Skirting & Moldings', 'Tiles', 'AC Thermostat', 'Bed Base',
                'Head Rest', 'Mattress', 'Linens', 'Pillows', 'Bedside Table/s', 'Bedside Lamp/s', 'Carpet', 'Cabinets',
                'Safebox', 'Dressing Table', 'Dressing Chair', 'Dressing Mirror', 'Dresser Vase/s', 'Decorative Wall Frame/s',
                'Table Display Figurine/s', 'Windows (Glass, Handle)', 'Curtains Rails/Rod', 'Heavy Curtain', 'Light Curtain',
            ],
            'Others' => [
                'Clothes Hangers', 'Luggage Rack', 'Weighing Scale',
            ],
        ];
        Checklist::truncate();
        $sort = 0;
        foreach ($data as $category => $items) {
            foreach ($items as $name) {
                $sort++;
                Checklist::withoutGlobalScopes()->withTrashed()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'category' => $category,
                        'name' => $name,
                    ],
                    [
                        'sort_order' => $sort,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
