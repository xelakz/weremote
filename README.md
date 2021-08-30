#Instructions
- extract the zip and put them in your local server
- create database `weremote` on your local database server
- inside the root directory, run `composer update`
- update values for database connection and dropbox token in .env file
    ```
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=weremote
        DB_USERNAME=root
        DB_PASSWORD=
    ```
    ```
    DROPBOX_TOKEN="sl.A3g-yiCTPMkfv1clRNvdCkrCgiQuqAyDqDU1ESnnb_vkHt7u8fsZHcRaMmwxTBLjWt67MKr7e952JqawONd_1fGsICFgbjXJhdpjZh0AqcTza59riAEhmQUKEcDiiPGncPTgN_8"
    ```
- run `php artisan migrate` to automatically create the tables needed.

#Execution
The 4 tasks can be executed using cli with following commands per task
- `php artisan dropbox --report=1` for report 1
- `php artisan dropbox --report=2` for report 2
- `php artisan dropbox --report=3` for report 3
- `php artisan dropbox --report=4` for report 4
