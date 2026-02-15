<?php

namespace Database\Seeders;

use App\Models\DailySalesSummary;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Restaurant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🍽️  Seeding Multi-Restaurant Database...');

        // ============================================================
        // 1. RESTAURANTS (3 cabang)
        // ============================================================
        $this->command->info('📍 Creating restaurants...');

        $restaurants = collect([
            Restaurant::create([
                'name' => 'Warung Nusantara - Pusat',
                'address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
                'phone' => '021-5551234',
                'email' => 'pusat@warungnusantara.com',
                'tax_id' => '01.234.567.8-012.000',
                'is_active' => true,
            ]),
            Restaurant::create([
                'name' => 'Warung Nusantara - Bandung',
                'address' => 'Jl. Braga No. 45, Bandung',
                'phone' => '022-4441234',
                'email' => 'bandung@warungnusantara.com',
                'tax_id' => '01.234.567.8-013.000',
                'is_active' => true,
            ]),
            Restaurant::create([
                'name' => 'Warung Nusantara - Surabaya',
                'address' => 'Jl. Tunjungan No. 78, Surabaya',
                'phone' => '031-3331234',
                'email' => 'surabaya@warungnusantara.com',
                'tax_id' => '01.234.567.8-014.000',
                'is_active' => true,
            ]),
        ]);

        // ============================================================
        // 2. USERS (per restaurant + owner/admin global)
        // ============================================================
        $this->command->info('👤 Creating users...');

        // Owner (restaurant_id = 1 sebagai pusat)
        $owner = User::factory()->owner()->create([
            'restaurant_id' => $restaurants[0]->id,
            'username' => 'owner',
            'full_name' => 'Pak Budi (Owner)',
            'email' => 'owner@warungnusantara.com',
            'phone' => '0812-0000-0001',
        ]);

        // Admin
        $admin = User::factory()->admin()->create([
            'restaurant_id' => $restaurants[0]->id,
            'username' => 'admin',
            'full_name' => 'Siti Admin',
            'email' => 'admin@warungnusantara.com',
            'phone' => '0812-0000-0002',
        ]);

        // Per-restaurant staff
        $managers = collect();
        $waiters = collect();
        $kitchens = collect();

        foreach ($restaurants as $i => $restaurant) {
            $branchCode = $i + 1;

            // Manager
            $manager = User::factory()->manager()->create([
                'restaurant_id' => $restaurant->id,
                'username' => "manager{$branchCode}",
                'full_name' => "Manager Cabang {$branchCode}",
                'email' => "manager{$branchCode}@warungnusantara.com",
            ]);
            $managers->push($manager);

            // 2 Waiters per branch
            for ($w = 1; $w <= 2; $w++) {
                $waiter = User::factory()->waiter()->create([
                    'restaurant_id' => $restaurant->id,
                    'username' => "waiter{$branchCode}{$w}",
                    'full_name' => "Pelayan {$w} - Cabang {$branchCode}",
                    'email' => "waiter{$branchCode}{$w}@warungnusantara.com",
                ]);
                $waiters->push($waiter);
            }

            // 2 Kitchen staff per branch
            for ($k = 1; $k <= 2; $k++) {
                $kitchen = User::factory()->kitchen()->create([
                    'restaurant_id' => $restaurant->id,
                    'username' => "kitchen{$branchCode}{$k}",
                    'full_name' => "Koki {$k} - Cabang {$branchCode}",
                    'email' => "kitchen{$branchCode}{$k}@warungnusantara.com",
                ]);
                $kitchens->push($kitchen);
            }
        }

        // ============================================================
        // 3. MENU CATEGORIES (per restaurant)
        // ============================================================
        $this->command->info('📂 Creating menu categories...');

        $categoryNames = [
            ['name' => 'Makanan Utama', 'description' => 'Berbagai macam makanan utama nusantara', 'sort_order' => 1],
            ['name' => 'Minuman', 'description' => 'Minuman segar dan hangat', 'sort_order' => 2],
            ['name' => 'Snack & Gorengan', 'description' => 'Cemilan dan gorengan', 'sort_order' => 3],
            ['name' => 'Dessert', 'description' => 'Hidangan penutup manis', 'sort_order' => 4],
            ['name' => 'Paket Hemat', 'description' => 'Paket combo hemat', 'sort_order' => 5],
        ];

        $categories = collect();
        foreach ($restaurants as $restaurant) {
            foreach ($categoryNames as $cat) {
                $category = MenuCategory::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]);
                $categories->push($category);
            }
        }

        // ============================================================
        // 4. MENU ITEMS (per category per restaurant)
        // ============================================================
        $this->command->info('🍛 Creating menu items...');

        $menusByCategory = [
            'Makanan Utama' => [
                ['name' => 'Nasi Goreng Spesial', 'price' => 25000, 'cost' => 12000, 'time' => 15],
                ['name' => 'Mie Goreng Jawa', 'price' => 23000, 'cost' => 10000, 'time' => 12],
                ['name' => 'Ayam Bakar Kecap', 'price' => 35000, 'cost' => 18000, 'time' => 20],
                ['name' => 'Rendang Sapi', 'price' => 40000, 'cost' => 22000, 'time' => 10],
                ['name' => 'Soto Ayam', 'price' => 22000, 'cost' => 10000, 'time' => 15],
                ['name' => 'Nasi Padang Komplit', 'price' => 35000, 'cost' => 17000, 'time' => 10],
                ['name' => 'Ikan Bakar Sambal', 'price' => 38000, 'cost' => 20000, 'time' => 25],
                ['name' => 'Gado-Gado Jakarta', 'price' => 20000, 'cost' => 9000, 'time' => 10],
            ],
            'Minuman' => [
                ['name' => 'Es Teh Manis', 'price' => 5000, 'cost' => 1500, 'time' => 3],
                ['name' => 'Es Jeruk Peras', 'price' => 8000, 'cost' => 3000, 'time' => 3],
                ['name' => 'Jus Alpukat', 'price' => 15000, 'cost' => 7000, 'time' => 5],
                ['name' => 'Kopi Hitam', 'price' => 8000, 'cost' => 3000, 'time' => 5],
                ['name' => 'Teh Tarik', 'price' => 12000, 'cost' => 5000, 'time' => 5],
                ['name' => 'Air Mineral', 'price' => 5000, 'cost' => 1500, 'time' => 1],
            ],
            'Snack & Gorengan' => [
                ['name' => 'Pisang Goreng', 'price' => 10000, 'cost' => 4000, 'time' => 8],
                ['name' => 'Tahu Goreng Crispy', 'price' => 10000, 'cost' => 4000, 'time' => 8],
                ['name' => 'Kentang Goreng', 'price' => 15000, 'cost' => 6000, 'time' => 10],
                ['name' => 'Sate Ayam (10 tusuk)', 'price' => 30000, 'cost' => 15000, 'time' => 15],
            ],
            'Dessert' => [
                ['name' => 'Es Campur', 'price' => 12000, 'cost' => 5000, 'time' => 5],
                ['name' => 'Kolak Pisang', 'price' => 10000, 'cost' => 4000, 'time' => 5],
                ['name' => 'Es Cendol', 'price' => 10000, 'cost' => 4000, 'time' => 5],
            ],
            'Paket Hemat' => [
                ['name' => 'Paket Nasi Goreng + Es Teh', 'price' => 28000, 'cost' => 13000, 'time' => 15],
                ['name' => 'Paket Ayam Bakar + Nasi + Es Jeruk', 'price' => 42000, 'cost' => 20000, 'time' => 20],
                ['name' => 'Paket Rendang + Nasi + Teh Tarik', 'price' => 48000, 'cost' => 25000, 'time' => 15],
            ],
        ];

        $menuItems = collect();
        foreach ($categories as $category) {
            $catName = $category->name;
            if (!isset($menusByCategory[$catName])) continue;

            foreach ($menusByCategory[$catName] as $idx => $menu) {
                $item = MenuItem::create([
                    'restaurant_id' => $category->restaurant_id,
                    'category_id' => $category->id,
                    'name' => $menu['name'],
                    'description' => 'Disajikan hangat dengan cita rasa khas nusantara.',
                    'price' => $menu['price'],
                    'cost_price' => $menu['cost'],
                    'image_url' => null,
                    'is_available' => true,
                    'is_featured' => $idx < 2,
                    'preparation_time' => $menu['time'],
                ]);
                $menuItems->push($item);
            }
        }

        // ============================================================
        // 5. ORDERS (30 per restaurant, berbagai status)
        // ============================================================
        $this->command->info('📝 Creating orders...');

        $orderCounter = 1;
        foreach ($restaurants as $ri => $restaurant) {
            $branchWaiters = $waiters->filter(fn($u) => $u->restaurant_id === $restaurant->id)->values();
            $branchKitchens = $kitchens->filter(fn($u) => $u->restaurant_id === $restaurant->id)->values();
            $branchMenuItems = $menuItems->filter(fn($m) => $m->restaurant_id === $restaurant->id)->values();

            // Status distribution: 5 pending, 3 cooking, 5 completed, 15 paid, 2 cancelled
            $statuses = array_merge(
                array_fill(0, 5, 'pending'),
                array_fill(0, 3, 'cooking'),
                array_fill(0, 5, 'completed'),
                array_fill(0, 15, 'paid'),
                array_fill(0, 2, 'cancelled'),
            );

            foreach ($statuses as $si => $status) {
                $waiter = $branchWaiters->random();
                $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad($orderCounter, 4, '0', STR_PAD_LEFT);
                $orderCounter++;

                $createdAt = now()->subDays(rand(0, 14))->subHours(rand(0, 12));

                $order = Order::create([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $waiter->id,
                    'customer_name' => fake()->name(),
                    'customer_phone' => fake()->numerify('08##-####-####'),
                    'order_number' => $orderNumber,
                    'status' => $status,
                    'total_amount' => 0,
                    'notes' => $si % 3 === 0 ? fake()->sentence() : null,
                    'completed_at' => in_array($status, ['completed', 'paid']) ? $createdAt->copy()->addMinutes(rand(10, 30)) : null,
                    'paid_at' => $status === 'paid' ? $createdAt->copy()->addMinutes(rand(30, 60)) : null,
                    'cancelled_at' => $status === 'cancelled' ? $createdAt->copy()->addMinutes(rand(5, 15)) : null,
                    'cancellation_reason' => $status === 'cancelled' ? fake()->randomElement(['Pelanggan membatalkan', 'Stok habis', 'Kesalahan input']) : null,
                    'payment_amount' => null,
                    'change_amount' => null,
                    'payment_method' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Create 1-4 order items
                $totalAmount = 0;
                $itemCount = rand(1, 4);
                $selectedItems = $branchMenuItems->random(min($itemCount, $branchMenuItems->count()));

                foreach ($selectedItems as $menuItem) {
                    $qty = rand(1, 3);
                    $subtotal = $menuItem->price * $qty;
                    $totalAmount += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $qty,
                        'price_at_time' => $menuItem->price,
                        'subtotal' => $subtotal,
                        'notes' => rand(0, 3) === 0 ? fake()->randomElement(['Pedas', 'Tidak pedas', 'Extra sambal', 'Tanpa bawang']) : null,
                    ]);
                }

                // Update total & payment
                $paymentData = ['total_amount' => $totalAmount];
                if ($status === 'paid') {
                    $paymentAmount = ceil($totalAmount / 10000) * 10000 + (rand(0, 2) * 10000);
                    $paymentData['payment_amount'] = $paymentAmount;
                    $paymentData['change_amount'] = $paymentAmount - $totalAmount;
                    $paymentData['payment_method'] = fake()->randomElement(['cash', 'qris', 'transfer', 'debit']);
                }
                $order->update($paymentData);

                // Status logs
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'old_status' => null,
                    'new_status' => 'pending',
                    'changed_by' => $waiter->id,
                    'changed_at' => $createdAt,
                    'notes' => 'Order dibuat.',
                ]);

                if (in_array($status, ['cooking', 'completed', 'paid'])) {
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'old_status' => 'pending',
                        'new_status' => 'cooking',
                        'changed_by' => $branchKitchens->random()->id,
                        'changed_at' => $createdAt->copy()->addMinutes(2),
                        'notes' => 'Mulai dimasak.',
                    ]);
                }

                if (in_array($status, ['completed', 'paid'])) {
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'old_status' => 'cooking',
                        'new_status' => 'completed',
                        'changed_by' => $branchKitchens->random()->id,
                        'changed_at' => $createdAt->copy()->addMinutes(rand(10, 25)),
                        'notes' => 'Selesai dimasak.',
                    ]);
                }

                if ($status === 'paid') {
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'old_status' => 'completed',
                        'new_status' => 'paid',
                        'changed_by' => $waiter->id,
                        'changed_at' => $createdAt->copy()->addMinutes(rand(30, 55)),
                        'notes' => 'Pembayaran diterima.',
                    ]);
                }

                if ($status === 'cancelled') {
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'old_status' => 'pending',
                        'new_status' => 'cancelled',
                        'changed_by' => $waiter->id,
                        'changed_at' => $order->cancelled_at,
                        'notes' => $order->cancellation_reason,
                    ]);
                }
            }
        }

        // ============================================================
        // 6. INVENTORY (per restaurant)
        // ============================================================
        $this->command->info('📦 Creating inventory items & transactions...');

        $ingredients = [
            ['name' => 'Beras', 'unit' => 'kg', 'min' => 10, 'stock' => 50, 'cost' => 12000],
            ['name' => 'Minyak Goreng', 'unit' => 'liter', 'min' => 5, 'stock' => 20, 'cost' => 18000],
            ['name' => 'Telur Ayam', 'unit' => 'butir', 'min' => 30, 'stock' => 100, 'cost' => 2500],
            ['name' => 'Daging Ayam', 'unit' => 'kg', 'min' => 5, 'stock' => 15, 'cost' => 35000],
            ['name' => 'Daging Sapi', 'unit' => 'kg', 'min' => 3, 'stock' => 8, 'cost' => 130000],
            ['name' => 'Cabai Merah', 'unit' => 'kg', 'min' => 2, 'stock' => 5, 'cost' => 45000],
            ['name' => 'Bawang Merah', 'unit' => 'kg', 'min' => 3, 'stock' => 10, 'cost' => 32000],
            ['name' => 'Bawang Putih', 'unit' => 'kg', 'min' => 2, 'stock' => 8, 'cost' => 28000],
            ['name' => 'Kecap Manis', 'unit' => 'botol', 'min' => 5, 'stock' => 12, 'cost' => 14000],
            ['name' => 'Gula Pasir', 'unit' => 'kg', 'min' => 3, 'stock' => 15, 'cost' => 16000],
            ['name' => 'Teh Celup', 'unit' => 'box', 'min' => 5, 'stock' => 20, 'cost' => 8000],
            ['name' => 'Kopi Bubuk', 'unit' => 'kg', 'min' => 2, 'stock' => 6, 'cost' => 55000],
            ['name' => 'Susu Kental Manis', 'unit' => 'kaleng', 'min' => 10, 'stock' => 2, 'cost' => 11000], // low stock!
            ['name' => 'Garam', 'unit' => 'kg', 'min' => 2, 'stock' => 10, 'cost' => 5000],
            ['name' => 'Santan Kelapa', 'unit' => 'liter', 'min' => 3, 'stock' => 1, 'cost' => 15000], // low stock!
        ];

        foreach ($restaurants as $restaurant) {
            $branchManager = $managers->firstWhere('restaurant_id', $restaurant->id);

            foreach ($ingredients as $ingredient) {
                $item = InventoryItem::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $ingredient['name'],
                    'unit' => $ingredient['unit'],
                    'min_stock' => $ingredient['min'],
                    'current_stock' => $ingredient['stock'],
                    'unit_cost' => $ingredient['cost'],
                    'supplier_name' => 'PT ' . fake()->company(),
                    'supplier_phone' => fake()->numerify('021-####-####'),
                    'is_active' => true,
                ]);

                // Create 3-5 transactions per item
                $transCount = rand(3, 5);
                for ($t = 0; $t < $transCount; $t++) {
                    $type = fake()->randomElement(['in', 'in', 'out']); // more stock-in
                    $qty = $type === 'in' ? rand(5, 30) : rand(1, 10);

                    InventoryTransaction::create([
                        'restaurant_id' => $restaurant->id,
                        'inventory_item_id' => $item->id,
                        'type' => $type,
                        'quantity' => $qty,
                        'unit_cost' => $ingredient['cost'],
                        'total_cost' => $qty * $ingredient['cost'],
                        'reference_type' => $type === 'in' ? 'purchase' : 'daily_usage',
                        'reference_id' => null,
                        'notes' => $type === 'in' ? 'Pembelian dari supplier' : 'Pemakaian harian',
                        'created_by' => $branchManager->id,
                        'created_at' => now()->subDays(rand(0, 30)),
                    ]);
                }
            }
        }

        // ============================================================
        // 7. DAILY SALES SUMMARIES (30 hari terakhir per restaurant)
        // ============================================================
        $this->command->info('📊 Creating daily sales summaries...');

        foreach ($restaurants as $restaurant) {
            for ($d = 30; $d >= 0; $d--) {
                $date = Carbon::today()->subDays($d);
                $totalOrders = rand(15, 60);
                $grossSales = $totalOrders * rand(25000, 45000);
                $totalDiscount = round($grossSales * (rand(0, 5) / 100), 2);
                $totalTax = round(($grossSales - $totalDiscount) * 0.1, 2);

                DailySalesSummary::create([
                    'restaurant_id' => $restaurant->id,
                    'date' => $date->format('Y-m-d'),
                    'total_orders' => $totalOrders,
                    'total_items_sold' => $totalOrders * rand(2, 4),
                    'gross_sales' => $grossSales,
                    'net_sales' => $grossSales - $totalDiscount,
                    'total_discount' => $totalDiscount,
                    'total_tax' => $totalTax,
                ]);
            }
        }

        // ============================================================
        // 8. SETTINGS (per restaurant)
        // ============================================================
        $this->command->info('⚙️  Creating settings...');

        $defaultSettings = [
            ['key' => 'tax_percentage', 'value' => '10', 'type' => 'number', 'desc' => 'Persentase pajak (PB1)'],
            ['key' => 'service_charge', 'value' => '5', 'type' => 'number', 'desc' => 'Service charge (%)'],
            ['key' => 'currency', 'value' => 'IDR', 'type' => 'string', 'desc' => 'Mata uang'],
            ['key' => 'receipt_header', 'value' => 'Warung Nusantara', 'type' => 'string', 'desc' => 'Header struk'],
            ['key' => 'receipt_footer', 'value' => 'Terima kasih atas kunjungan Anda!', 'type' => 'string', 'desc' => 'Footer struk'],
            ['key' => 'auto_print_receipt', 'value' => 'true', 'type' => 'boolean', 'desc' => 'Cetak struk otomatis'],
            ['key' => 'low_stock_alert', 'value' => 'true', 'type' => 'boolean', 'desc' => 'Notifikasi stok menipis'],
            ['key' => 'notification_sound', 'value' => 'true', 'type' => 'boolean', 'desc' => 'Suara notifikasi'],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($defaultSettings as $setting) {
                Setting::create([
                    'restaurant_id' => $restaurant->id,
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['desc'],
                ]);
            }
        }

        // ============================================================
        // 9. NOTIFICATIONS (per user)
        // ============================================================
        $this->command->info('🔔 Creating notifications...');

        $allStaff = User::all();
        foreach ($allStaff as $user) {
            // 3-8 notifications per user
            $notifCount = rand(3, 8);
            $notifTypes = [
                ['type' => 'new_order', 'title' => 'Pesanan Baru!', 'message' => 'Ada pesanan baru dari pelanggan menunggu diproses.'],
                ['type' => 'order_completed', 'title' => 'Pesanan Selesai!', 'message' => 'Pesanan telah selesai dimasak, silakan antarkan ke pelanggan.'],
                ['type' => 'low_stock', 'title' => 'Stok Menipis!', 'message' => 'Beberapa bahan baku stoknya di bawah minimum.'],
                ['type' => 'order_cancelled', 'title' => 'Pesanan Dibatalkan', 'message' => 'Ada pesanan yang dibatalkan oleh pelanggan.'],
                ['type' => 'daily_report', 'title' => 'Laporan Harian', 'message' => 'Laporan penjualan harian sudah tersedia.'],
            ];

            for ($n = 0; $n < $notifCount; $n++) {
                $notif = $notifTypes[array_rand($notifTypes)];
                $isRead = (bool) rand(0, 1);
                $createdAt = now()->subHours(rand(1, 168)); // last 7 days

                Notification::create([
                    'restaurant_id' => $user->restaurant_id,
                    'user_id' => $user->id,
                    'type' => $notif['type'],
                    'title' => $notif['title'],
                    'message' => $notif['message'],
                    'data' => json_encode(['order_id' => rand(1, 90)]),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? $createdAt->copy()->addMinutes(rand(1, 60)) : null,
                    'created_at' => $createdAt,
                ]);
            }
        }

        // ============================================================
        // SUMMARY
        // ============================================================
        $this->command->newLine();
        $this->command->info('✅ Seeding completed!');
        $this->command->newLine();
        $this->command->table(
            ['Table', 'Count'],
            [
                ['Restaurants', Restaurant::count()],
                ['Users', User::count()],
                ['Menu Categories', MenuCategory::count()],
                ['Menu Items', MenuItem::count()],
                ['Orders', Order::count()],
                ['Order Items', OrderItem::count()],
                ['Order Status Logs', OrderStatusLog::count()],
                ['Inventory Items', InventoryItem::count()],
                ['Inventory Transactions', InventoryTransaction::count()],
                ['Daily Sales Summaries', DailySalesSummary::count()],
                ['Settings', Setting::count()],
                ['Notifications', Notification::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Login Credentials (password: password123):');
        $this->command->table(
            ['Username', 'Role', 'Restaurant'],
            [
                ['owner', 'owner', 'Pusat'],
                ['admin', 'admin', 'Pusat'],
                ['manager1', 'manager', 'Pusat'],
                ['manager2', 'manager', 'Bandung'],
                ['manager3', 'manager', 'Surabaya'],
                ['waiter11', 'waiter', 'Pusat'],
                ['waiter12', 'waiter', 'Pusat'],
                ['waiter21', 'waiter', 'Bandung'],
                ['kitchen11', 'kitchen', 'Pusat'],
                ['kitchen21', 'kitchen', 'Bandung'],
            ]
        );
    }
}
