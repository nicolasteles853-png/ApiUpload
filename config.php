<?php
// Chave secreta da API
define("API_KEY", "33c0c284315e538a5c166a8d0ccda4ccaeb360a14d4fc11094535e091e5e0664");

// Limite máximo de upload em bytes (100 MB)
define("MAX_UPLOAD_SIZE", 100 * 1024 * 1024);

// Tipos de arquivo proibidos
define("BLOCKED_EXT", ["php","phtml","phar","sh","exe","bat","js","cgi","pl"]);
