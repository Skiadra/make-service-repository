# Laravel Custom Commands

This package provides a set of custom Artisan commands for Laravel applications.

## Installation

To install the package, follow these steps:

1. **Add the Repository**  
   Run the following command to add the custom commands repository to your Composer configuration:

   ```
   composer config repositories.laravel-custom-commands vcs https://github.com/Skiadra/make-service-repository.git
   ```

2. **Install**  
   Run the following command to install:

   ```
   composer require skiadra/make-service-repository:dev-main
   ```

## Custom Commands

This package includes the following custom Artisan commands:

### `php artisan make:service`

Creates a new service class.

#### Options:
- `--bind=BindClassName`: Specify the class name to bind the service.
- `--dir=DirName`: Specify a directory to create the service in.

### `php artisan make:repository`

Creates a new repository class.

#### Options:
- `--bind=BindClassName`: Specify the class name to bind the repository.
- `--dir=DirName`: Specify a directory to create the repository in.

## Usage

After installation, you can run the commands as needed. For example:

```
php artisan make:service --bind=UserService --dir=User
```

```
php artisan make:repository --bind=UserRepository --dir=User
```

## License

This package is licensed under the MIT License.
