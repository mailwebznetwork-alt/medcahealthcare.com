<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AutoLogout;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureBackupOperator;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\VerifyPaymentIngestSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            'throttle:60,1',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsurePincodeDetected::class,
        ]);

        $middleware->alias([
            'module' => EnsureModuleAccess::class,
            'active' => EnsureAccountIsActive::class,
            'admin' => AdminMiddleware::class,
            'role' => CheckRole::class,
            'auto.logout' => AutoLogout::class,
            'payment.ingest.signature' => VerifyPaymentIngestSignature::class,
            'backup.operator' => EnsureBackupOperator::class,
            'pincode.detected' => \App\Http\Middleware\EnsurePincodeDetected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
