<?php

namespace Vendor\CustomCommands\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeRepositoryCommand extends Command
{
    // Update the signature to include the --bind and --dir options
    protected $signature = 'make:repository {name} {--bind=App\Providers\AppServiceProvider} {--dir= : The directory under Repositories where the files will be stored}';

    // The console command description
    protected $description = 'Create a new repository and its interface, and bind them in a specified provider';

    // Execute the console command
    public function handle()
    {
        $name = $this->argument('name');
        $repositoryName = Str::studly($name);
        $interfaceName = "{$repositoryName}Interface";
        $bindClass = $this->option('bind');
        $dir = $this->option('dir');

        // Prepend the default namespace if the bind class doesn't have one
        if (!str_contains($bindClass, '\\')) {
            $bindClass = "App\\Providers\\{$bindClass}";
        }

        // Define the directory path under Repositories
        $repositoryDir = $dir ? "Repositories/{$dir}" : "Repositories";
        $repositoryPath = app_path("{$repositoryDir}/{$repositoryName}.php");
        $interfacePath = app_path("{$repositoryDir}/{$interfaceName}.php");

        // Create directories if they don't exist
        if (!file_exists(app_path($repositoryDir))) {
            mkdir(app_path($repositoryDir), 0755, true);
        }

        // Generate the repository file
        $this->createRepositoryFile($repositoryPath, $repositoryName, $interfaceName, $repositoryDir);
        // Generate the interface file
        $this->createInterfaceFile($interfacePath, $interfaceName, $repositoryDir);
        // Bind the repository and interface in the specified provider
        $this->bindInServiceProvider($repositoryName, $interfaceName, $bindClass, $repositoryDir);

        $this->info("Repository and interface created successfully!");
    }

    protected function createRepositoryFile($path, $repositoryName, $interfaceName, $repositoryDir)
    {
        $namespace = str_replace('/', '\\', $repositoryDir);
        $stub = <<<EOL
<?php

namespace App\\{$namespace};

use App\\{$namespace}\\{$interfaceName};

class {$repositoryName} implements {$interfaceName}
{
    // Add your repository methods here
}

EOL;

        file_put_contents($path, $stub);
    }

    protected function createInterfaceFile($path, $interfaceName, $repositoryDir)
    {
        $namespace = str_replace('/', '\\', $repositoryDir);
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

    protected function bindInServiceProvider($repositoryName, $interfaceName, $bindClass, $repositoryDir)
    {
        // Convert class name to file path
        $bindClassPath = base_path(str_replace('\\', '/', $bindClass) . '.php');

        // Check if the binding provider file exists
        if (!file_exists($bindClassPath)) {
            $this->createBindingClassFile($bindClass);
        }

        // Get the content of the binding class
        $content = file_get_contents($bindClassPath);
        $namespace = str_replace('/', '\\', $repositoryDir);
        $binding = "\$this->app->bind(\\App\\{$namespace}\\{$interfaceName}::class, \\App\\{$namespace}\\{$repositoryName}::class);";

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
            $this->info("Repository and interface bound in {$bindClass}.");
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
