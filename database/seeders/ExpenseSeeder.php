<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        // Check if categories already exist to avoid duplicates
        if (ExpenseCategory::count() === 0) {
            $this->command->info('Creating expense categories...');
            
            $categories = [
                ['name' => 'Rent', 'color' => '#3b82f6'],
                ['name' => 'Utilities', 'color' => '#10b981'],
                ['name' => 'Salaries', 'color' => '#f59e0b'],
                ['name' => 'Equipment', 'color' => '#ef4444'],
                ['name' => 'Supplies', 'color' => '#8b5cf6'],
                ['name' => 'Marketing', 'color' => '#ec4899'],
                ['name' => 'Maintenance', 'color' => '#14b8a6'],
                ['name' => 'Insurance', 'color' => '#f97316'],
            ];

            foreach ($categories as $category) {
                ExpenseCategory::create($category);
            }
            
            $this->command->info('Expense categories created.');
        } else {
            $this->command->info('Expense categories already exist. Skipping creation.');
        }

        // Get admin user
        $user = User::where('role', 'admin')->first();
        
        if (!$user) {
            $this->command->error('No admin user found! Please create a user with role="admin" first.');
            $this->command->info('Creating a temporary admin user...');
            
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@gym.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]);
            
            $this->command->info('Temporary admin user created.');
        }

        // Check if expenses already exist
        if (Expense::count() === 0) {
            $this->command->info('Creating sample expenses...');
            
            // Create sample expenses
            $expenses = [
                ['Electricity bill', 5000, 'Utilities'], // Make sure this matches exactly "Utilities" not "Utility"
                ['Trainer salary', 15000, 'Salaries'],
                ['Gym equipment purchase', 80000, 'Equipment'],
                ['Monthly rent', 25000, 'Rent'],
                ['Cleaning supplies', 2000, 'Supplies'],
                ['Facebook ads', 3000, 'Marketing'],
                ['AC repair', 4500, 'Maintenance'],
                ['Water bill', 1200, 'Utilities'], // Make sure this matches exactly "Utilities"
                ['Protein supplements', 5000, 'Supplies'],
                ['Staff bonus', 5000, 'Salaries'],
            ];

            $created = 0;
            $failed = 0;
            
            foreach ($expenses as $expense) {
                $category = ExpenseCategory::where('name', $expense[2])->first();
                
                if (!$category) {
                    $this->command->warn("Category '{$expense[2]}' not found for expense: {$expense[0]}");
                    $failed++;
                    continue;
                }
                
                Expense::create([
                    'category_id' => $category->id,
                    'description' => $expense[0],
                    'amount' => $expense[1],
                    'date' => now()->subDays(rand(1, 30)),
                    'user_id' => $user->id,
                ]);
                
                $created++;
            }
            
            $this->command->info("Created {$created} expenses. Failed: {$failed}");
        } else {
            $this->command->info('Expenses already exist. Skipping creation.');
        }
    }
}