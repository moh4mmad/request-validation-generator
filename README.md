# Request Validation Generator

Request Validation Generator is a Laravel package that automatically generates request validation files based on your migration column data types, column lengths, uniqueness, and foreign keys.

## Installation

You can install the package via Composer. Run the following command:

```bash
composer require your-vendor/request-validation-generator
```

After installing the package, you need to publish the configuration file and migration files. Run the following command to publish the package assets:

```bash
php artisan vendor:publish --tag=request-validations
```

This command will publish the package's configuration file and migration files to your application, allowing you to customize the behavior and modify the generated request validation files if needed.

## Usage

To generate request validation files for your migrations, run the following Artisan command:

```bash
php artisan generate:request-validations
```

The command will scan your migration files and generate request validation files based on the column data types, lengths, uniqueness, and foreign keys. The generated files will be placed in the `app/Http/Requests` directory.

### Example

Let's say you have a migration file `20220101000000_create_users_table.php` with the following columns:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->timestamps();
});
```

Running the `generate:request-validations` command will generate a request validation file `app/Http/Requests/User/Request.php` with the following content:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'email_verified_at' => ['nullable', 'date'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

The generated request validation file provides the validation rules for each column in the migration. You can use this file in your controllers or form requests for validating incoming requests.

## Contributing

Contributions are welcome! If you find any issues or have suggestions for improvements, please open an issue or submit a pull request.

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

```

Make sure to replace `your-vendor/request-validation-generator` with the actual package name. This updated README.md file now includes the `php artisan vendor:publish --tag=request-validations` command to provide users with the necessary step to publish the package assets.

Feel free to further customize the content as per your package's specific details. Let me know if there's anything else I can assist you with!
```
