## Setup

1. Open terminal, Clone the project to your local directory
2. Go to directory
3. Install composer depedency > composer install
4. Open the app with code editor, duplicate .env.example then rename file to .env
5. Make sure to create a new database and add your database credentials to your .env file:
    ```
    DB_HOST=localhost
    DB_DATABASE=homestead
    DB_USERNAME=homestead
    DB_PASSWORD=secret
    ```

5. Back to terminal, then run `php artisan migrate` to migrate the database
6. Run `php artisan db:seed` to add seeder data
6. Run `php artisan serve` for Run APP