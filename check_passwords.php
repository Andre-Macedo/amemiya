<?php

use Illuminate\Support\Facades\Hash;
use Modules\System\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$emails = ['super@admin.com', 'admin@amemiya.com'];
$passwords = ['admin123', '12345678'];

foreach ($emails as $i => $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $matches = Hash::check($passwords[$i], $user->password);
        echo "Email: $email | Password: {$passwords[$i]} | Matches: " . ($matches ? 'YES' : 'NO') . "\n";
    } else {
        echo "Email: $email | NOT FOUND\n";
    }
}
