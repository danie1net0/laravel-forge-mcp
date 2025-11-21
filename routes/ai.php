<?php

use App\Mcp\Servers\ForgeServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('forge', ForgeServer::class);
Mcp::web('/mcp/forge', ForgeServer::class);
