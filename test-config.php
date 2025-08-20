<?php

// Test script to verify configuration
echo "Testing configuration...\n";
echo "Frontend URL: " . config('app.frontend_url', 'NOT_SET') . "\n";
echo "App URL: " . config('app.url', 'NOT_SET') . "\n";
echo "Environment: " . config('app.env', 'NOT_SET') . "\n";

// Test the redirect URL construction
$frontendUrl = config('app.frontend_url', 'https://nasow-portal.vercel.app');
$testUrl = $frontendUrl . '/email/verify?id=123&hash=abc&status=success';
echo "Test redirect URL: " . $testUrl . "\n"; 