<?php

use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\System\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Simulate a token validation
$tokenString = '01ksg6nyd5ecxa8ar71z7akxtm|27430616234edbfc724b6f4a85231f97e01e366148924f1679dbde406f56f6dd'; // This is NOT the plain token, it's the hash. Wait.
// Actually I need the plain token.

$user = User::where('email', 'super@admin.com')->first();
$token = $user->createToken('test_token');
echo "Plain Token: " . $token->plainTextToken . "\n";

[$id, $plain] = explode('|', $token->plainTextToken);
$model = App\Models\PersonalAccessToken::find($id);

if ($model) {
    echo "Token model found: " . $model->id . "\n";
    if (hash_equals($model->token, hash('sha256', $plain))) {
        echo "Token hash matches!\n";
    } else {
        echo "Token hash mismatch!\n";
    }
} else {
    echo "Token model NOT found for ID: $id\n";
}
