<?php
namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'ok' => true,
            'php' => PHP_VERSION,
            'db' => \DB::getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME),
        ]);
    }
}
