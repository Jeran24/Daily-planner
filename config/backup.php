<?php

return [
    'mysql_dump_binary' => env('DB_DUMP_BINARY', 'mysqldump'),
    'postgres_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
];
