<?php

use App\Mcp\Servers\XConnectorServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp', XConnectorServer::class)
    ->middleware('auth:api');
