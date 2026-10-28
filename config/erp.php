<?php

return [

    /*
    | Domain of the logins (emails) that the admin panel generates for new users,
    | e.g. otkir.gulomov@brainup.uz. See App\Services\CredentialGenerator.
    */

    'login_domain' => env('ERP_LOGIN_DOMAIN', 'brainup.uz'),

];
