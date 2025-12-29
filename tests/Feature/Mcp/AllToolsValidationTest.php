<?php

declare(strict_types=1);

describe('All MCP Tools Validation', function (): void {
    it('validates all tools have proper structure and required methods', function (): void {
        $toolsPath = app_path('Mcp/Tools');
        $toolFiles = collect(File::allFiles($toolsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Tool.php'))
            ->values();

        expect($toolFiles)->toHaveCount(179, 'Expected exactly 179 tools to be found');

        foreach ($toolFiles as $file) {
            $relativePath = str_replace([app_path('Mcp/Tools/'), '.php', '/'], ['', '', '\\'], $file->getPathname());
            $className = "App\\Mcp\\Tools\\{$relativePath}";

            expect(class_exists($className))
                ->toBeTrue("Class {$className} should exist");

            $reflection = new ReflectionClass($className);

            expect($reflection->hasMethod('name'))
                ->toBeTrue("{$className} should have a name() method");

            expect($reflection->hasMethod('description'))
                ->toBeTrue("{$className} should have a description() method");

            expect($reflection->hasMethod('handle'))
                ->toBeTrue("{$className} should have a handle() method");
        }
    })->group('validation');

    it('validates all tools can be instantiated and called', function (): void {
        $toolsPath = app_path('Mcp/Tools');
        $toolFiles = collect(File::allFiles($toolsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Tool.php'))
            ->values();

        $failures = [];

        foreach ($toolFiles as $file) {
            $relativePath = str_replace([app_path('Mcp/Tools/'), '.php', '/'], ['', '', '\\'], $file->getPathname());
            $className = "App\\Mcp\\Tools\\{$relativePath}";

            try {
                $instance = app($className);

                expect($instance->name())->toBeString();
                expect($instance->description())->toBeString();
            } catch (Throwable $e) {
                $failures[] = "{$className} threw exception: {$e->getMessage()}";
            }
        }

        expect($failures)->toBeEmpty(
            "The following tools had issues:\n" . implode("\n", $failures)
        );
    })->group('validation');

    it('validates all tools belong to correct categories', function (): void {
        $expectedCategories = [
            'Backups', 'Certificates', 'Commands', 'Composite', 'Configuration', 'Credentials', 'Daemons',
            'Databases', 'Deployments', 'Firewall', 'Git', 'Integrations', 'Jobs',
            'Monitors', 'NginxTemplates', 'Php', 'Recipes', 'RedirectRules', 'Regions',
            'SSHKeys', 'SecurityRules', 'Servers', 'Services', 'Sites', 'User',
            'Webhooks', 'Workers',
        ];

        $toolsPath = app_path('Mcp/Tools');
        $actualCategories = collect(File::directories($toolsPath))
            ->map(fn ($dir) => basename($dir))
            ->sort()
            ->values()
            ->all();

        expect($actualCategories)->toBe($expectedCategories);
    })->group('validation');

    it('validates all Saloon requests have createDtoFromResponse method', function (): void {
        $requestsPath = app_path('Integrations/Forge/Requests');
        $requestFiles = collect(File::allFiles($requestsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Request.php'))
            ->values();

        $failures = [];

        foreach ($requestFiles as $file) {
            $relativePath = str_replace(
                [app_path('Integrations/Forge/Requests/'), '.php', '/'],
                ['', '', '\\'],
                $file->getPathname()
            );
            $className = "App\\Integrations\\Forge\\Requests\\{$relativePath}";

            if (! class_exists($className)) {
                $failures[] = "{$className} class not found";

                continue;
            }

            $reflection = new ReflectionClass($className);

            if (! $reflection->hasMethod('createDtoFromResponse')) {
                $failures[] = "{$className} missing createDtoFromResponse method";
            }

            if (! $reflection->hasMethod('resolveEndpoint')) {
                $failures[] = "{$className} missing resolveEndpoint method";
            }
        }

        expect($failures)->toBeEmpty(
            "The following requests had issues:\n" . implode("\n", $failures)
        );
    })->group('validation');

    it('validates all Integrations Data DTOs use snake_case mapper', function (): void {
        $dataPath = app_path('Integrations/Forge/Data');

        if (! is_dir($dataPath)) {
            expect(true)->toBeTrue('Integrations/Forge/Data directory does not exist');

            return;
        }

        $dataFiles = collect(File::allFiles($dataPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Data.php'))
            ->filter(fn ($file) => ! str_contains($file->getFilename(), 'Collection'))
            ->values();

        $failures = [];

        foreach ($dataFiles as $file) {
            $content = file_get_contents($file->getPathname());

            $hasMapInputName = str_contains($content, 'MapInputName');
            $hasMapOutputName = str_contains($content, 'MapOutputName');

            if (! $hasMapInputName && ! $hasMapOutputName) {
                $failures[] = "{$file->getFilename()} missing MapInputName or MapOutputName attribute";
            }

            if (! str_contains($content, 'SnakeCaseMapper')) {
                $failures[] = "{$file->getFilename()} missing SnakeCaseMapper";
            }
        }

        expect($failures)->toBeEmpty(
            "The following DTOs had issues:\n" . implode("\n", $failures)
        );
    })->group('validation');
});
