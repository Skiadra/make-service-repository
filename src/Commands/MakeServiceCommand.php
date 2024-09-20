<?php

namespace Vendor\CustomCommands\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    // Update the signature to include the --bind and --dir options
    protected $signature = 'make:service {name} {--bind=App\Providers\AppServiceProvider} {--dir= : The directory under Services where the files will be stored}';

    // The console command description
    protected $description = 'Create a new service and its interface, and bind them in a specified provider';

    // Execute the console command
    public function handle()
    {
        $name = $this->argument('name');
        $serviceName = Str::studly($name);
        $interfaceName = "{$serviceName}Interface";
        $dir = $this->option('dir');

        // Get the binding class and prepend the default namespace if necessary
        $bindClass = $this->option('bind');
        if (!str_contains($bindClass, '\\')) {
            $bindClass = "App\\Providers\\{$bindClass}";
        }

        // Define the directory path under Services
        $serviceDir = $dir ? "Services/{$dir}" : "Services";
        $servicePath = app_path("{$serviceDir}/{$serviceName}.php");
        $interfacePath = app_path("{$serviceDir}/{$interfaceName}.php");

        // Create directories if they don't exist
        if (!file_exists(app_path($serviceDir))) {
            mkdir(app_path($serviceDir), 0755, true);
        }

        // Generate the service file
        $this->createServiceFile($servicePath, $serviceName, $interfaceName, $serviceDir);
        // Generate the interface file
        $this->createInterfaceFile($interfacePath, $interfaceName, $serviceDir);
        // Bind the service and interface in the specified provider
        $this->bindInServiceProvider($serviceName, $interfaceName, $bindClass, $serviceDir);

        $this->info("Service and interface created successfully!");
    }

    protected function createServiceFile($path, $serviceName, $interfaceName, $serviceDir)
    {
        $namespace = str_replace('/', '\\', $serviceDir);
        $stub = <<<EOL
<?php

namespace App\\{$namespace};

use App\\{$namespace}\\{$interfaceName};

class {$serviceName} implements {$interfaceName}
{
    // Add your service methods here
}

EOL;

        file_put_contents($path, $stub);
    }

    protected function createInterfaceFile($path, $interfaceName, $serviceDir)
    {
        $namespace = str_replace('/', '\\', $serviceDir);
        $stub = <<<EOL
<?php

namespace App\\{$namespace};

interface {$interfaceName}
{
    // Define your interface methods here
}

EOL;

        file_put_contents($path, $stub);
    }

    protected function bindInServiceProvider($serviceName, $interfaceName, $bindClass, $serviceDir)
    {
        // Convert class name to file path
        $bindClassPath = base_path(str_replace('\\', '/', $bindClass) . '.php');

        // Check if the binding provider file exists
        if (!file_exists($bindClassPath)) {
            $this->createBindingClassFile($bindClass);
        }

        // Get the content of the binding class
        $content = file_get_contents($bindClassPath);
        $namespace = str_replace('/', '\\', $serviceDir);
        $binding = "\$this->app->bind(\\App\\{$namespace}\\{$interfaceName}::class, \\App\\{$namespace}\\{$serviceName}::class);";

        // Check if binding already exists
        if (strpos($content, $binding) !== false) {
            $this->info("Binding already exists in {$bindClass}.");
            return;
        }

        // Use a regex pattern that accounts for return type hints like ": void"
        $pattern = '/public\s+function\s+register\s*\(\)\s*:\s*void\s*{/';

        if (preg_match($pattern, $content)) {
            // Add the binding inside the register() method
            $replacement = "public function register(): void\n    {\n        {$binding}\n";
            $content = preg_replace($pattern, $replacement, $content);

            file_put_contents($bindClassPath, $content);
            $this->info("Service and interface bound in {$bindClass}.");
        } else {
            // If register method not found, append the binding to the class
            $closingTag = '}'; // Assuming the closing tag of the class
            $replacement = "\n    public function register(): void\n    {\n        {$binding}\n    }\n{$closingTag}";
            $content = str_replace($closingTag, $replacement, $content);

            file_put_contents($bindClassPath, $content);
            $this->info("register() method not found; added with binding in {$bindClass}.");
        }
    }

    protected function createBindingClassFile($bindClass)
    {
        // Extract class name from bindClass
        $className = class_basename($bindClass);
        $stub = <<<EOL
<?php

namespace App\\Providers;

use Illuminate\Support\ServiceProvider;

class {$className} extends ServiceProvider 
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Add your bindings here
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

EOL;

        // Write the new binding class to the file
        file_put_contents(base_path(str_replace('\\', '/', $bindClass) . '.php'), $stub);
        $this->info("Binding class {$bindClass} created successfully.");
    }
}
