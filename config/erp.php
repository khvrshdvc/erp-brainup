<?php

return [

    /*
    | Domain of the logins (emails) that the admin panel generates for new users,
    | e.g. otkir.gulomov@brainup.uz. See App\Services\CredentialGenerator.
    */

    'login_domain' => env('ERP_LOGIN_DOMAIN', 'brainup.uz'),

    /*
    | The one account that may open Laravel Pulse, whatever its role.
    */

    'pulse_admin_email' => env('PULSE_ADMIN_EMAIL', 'utkirbek@brainup.uz'),

];
