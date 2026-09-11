<?php
define('LARAVEL_START', microtime(true));
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

try {
    // Simulate what Filament does when building the table
    $resource = App\Filament\Admin\Resources\VendorPlanPaymentResource::class;
    
    // Check if table method works
    echo "Testing table build..." . PHP_EOL;
    
    // Test action creation
    $action = Tables\Actions\Action::make('confirm_paid')
        ->label('Test')
        ->visible(fn ($record) => true)
        ->form([
            Forms\Components\Select::make('method')
                ->options(['a' => 'A'])
                ->required(),
        ])
        ->action(fn () => null);
    
    echo "Action created OK" . PHP_EOL;
    
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    if ($e->getPrevious()) {
        echo 'Caused by: ' . $e->getPrevious()->getMessage() . PHP_EOL;
    }
}
