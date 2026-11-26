<?php

$account = env('BANK_LETTER_ACCOUNT', '');

return [
    'en' => [
        'holder' => env('BANK_LETTER_HOLDER_EN', ''),
        'signer' => env('BANK_LETTER_SIGNER_EN', ''),
        'role' => env('BANK_LETTER_ROLE_EN', ''),
        'account' => $account,
    ],
    'ar' => [
        'holder' => env('BANK_LETTER_HOLDER_AR', ''),
        'signer' => env('BANK_LETTER_SIGNER_AR', ''),
        'role' => env('BANK_LETTER_ROLE_AR', ''),
        'account' => $account,
    ],
];
