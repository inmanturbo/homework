<?php

namespace Inmanturbo\Homework\Models;

use Inmanturbo\Homework\Concerns\SkipsAuthorizationForFirstPartyClients;
use Laravel\Passport\Client as PassportClient;

class Client extends PassportClient
{
    use SkipsAuthorizationForFirstPartyClients;
}
