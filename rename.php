#!/usr/bin/env php
<?php

/**
 * SprykerEco Payment Template Renamer
 *
 * Renames the Payment Template module to a custom payment provider name.
 * Handles file renames, namespace updates, and case conversions automatically.
 */

// Suppress deprecation warnings from vendor packages
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Check if running from CLI
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Try to load Symfony Finder from vendor
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;

        break;
    }
}

use Symfony\Component\Finder\Finder;

if (!class_exists(Finder::class)) {
    echo "Error: Symfony Finder component not found.\n";
    echo "Please run: composer require symfony/finder\n";
    exit(1);
}

// @codingStandardsIgnoreLine
class StripeRenamer
{
    private const VALIDATION_PATTERN = '/^[a-z]+(-[a-z]+)*$/';

    private const MIN_NAME_LENGTH = 2;

    private const TEMPLATE_PASCAL = 'Stripe';

    private const TEMPLATE_CAMEL = 'stripe';

    private const TEMPLATE_KEBAB = 'stripe';

    private const TEMPLATE_SNAKE = 'stripe';

    private const TEMPLATE_SCREAMING = 'STRIPE';

    private const TEMPLATE_NAMESPACE = 'SprykerEco';

    private const DEFAULT_MODULE_NAMESPACE = 'SprykerEco';

    private const DEFAULT_PROJECT_NAMESPACE = 'Pyz';

    private string $providerNameKebab;

    private string $providerNamePascal;

    private string $providerNameCamel;

    private string $providerNameSnake;

    private string $providerNameScreaming;

    private string $namespace;

    private ?string $projectPath = null;

    private bool $projectMode = false;

    private string $sourcePath;

    private string $destinationPath;

    private bool $dryRun = false;

    private bool $inPlace = false;

    private int $filesProcessed = 0;

    private int $filesRenamed = 0;

    private int $contentUpdates = 0;

    public function __construct()
    {
        $this->sourcePath = realpath(__DIR__);
    }

    /**
     * @param array<mixed> $argv
     *
     * @return int
     */
    public function run(array $argv): int
    {
        try {
            $this->parseArguments($argv);

            if (!$this->validateSource()) {
                return 1;
            }

            if (!$this->dryRun && !$this->confirm()) {
                $this->output("Operation cancelled.\n");

                return 0;
            }

            if ($this->dryRun) {
                $this->showDryRunInfo();

                return 0;
            }

            $this->execute();
            $this->showSuccess();

            return 0;
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<mixed> $argv
     *
     * @throws \Exception
     *
     * @return void
     */
    private function parseArguments(array $argv): void
    {
        $providerName = null;
        $namespace = null;
        $projectPath = null;

        // Parse all flags
        foreach ($argv as $key => $arg) {
            if ($arg === '--dry-run') {
                $this->dryRun = true;
                unset($argv[$key]);
            } elseif ($arg === '--in-place') {
                $this->inPlace = true;
                unset($argv[$key]);
            } elseif (strpos($arg, '--namespace=') === 0) {
                $namespace = substr($arg, strlen('--namespace='));
                unset($argv[$key]);
            } elseif (strpos($arg, '--project-path=') === 0) {
                $projectPath = substr($arg, strlen('--project-path='));
                unset($argv[$key]);
            }
        }

        $argv = array_values($argv);

        // Get provider name from arguments or prompt
        if (isset($argv[1])) {
            $providerName = $argv[1];
        } else {
            $providerName = $this->promptForProviderName();
        }

        if (!$this->validateInput($providerName)) {
            throw new Exception('Invalid provider name format.');
        }

        $this->providerNameKebab = $providerName;
        $this->convertCases($providerName);

        // Handle namespace logic
        if ($projectPath !== null) {
            // Project integration mode
            $this->projectMode = true;
            $this->projectPath = rtrim($projectPath, '/\\');
            $this->namespace = $namespace ?? self::DEFAULT_PROJECT_NAMESPACE;

            if ($this->inPlace) {
                throw new Exception('Cannot use --in-place with --project-path. These flags are mutually exclusive.');
            }

            // Validate project path
            if (!$this->validateProjectPath()) {
                throw new Exception('Invalid project path. Must be a valid SprykerEco project with src/ directory.');
            }

            $this->destinationPath = $this->projectPath;
        } else {
            // Module creation mode
            $this->projectMode = false;
            $this->namespace = $namespace ?? self::DEFAULT_MODULE_NAMESPACE;

            // Set destination path
            if ($this->inPlace) {
                $this->destinationPath = $this->sourcePath;
            } else {
                // Convert namespace to kebab-case for directory structure
                $namespaceKebab = $this->convertToKebabCase($this->namespace);

                // Get grandparent directory (e.g., vendor/)
                $grandparentDir = dirname(dirname($this->sourcePath));

                // Build path: vendor/{namespace-kebab}/{module-kebab}
                $this->destinationPath = $grandparentDir . DIRECTORY_SEPARATOR . $namespaceKebab . DIRECTORY_SEPARATOR . $this->providerNameKebab;
            }
        }
    }

    /**
     * Prompt user for provider name interactively
     *
     * @throws \Exception
     */
    private function promptForProviderName(): string
    {
        $this->output("Enter payment provider name (kebab-case, e.g., 'adyen' or 'pay-pal'): ");
        $input = trim(fgets(STDIN));

        if (!$input) {
            throw new Exception('Provider name cannot be empty.');
        }

        return $input;
    }

    /**
     * Validate input format
     */
    private function validateInput(string $input): bool
    {
        if (strlen($input) < self::MIN_NAME_LENGTH) {
            $this->error('Provider name must be at least ' . self::MIN_NAME_LENGTH . ' characters long.');

            return false;
        }

        if (!preg_match(self::VALIDATION_PATTERN, $input)) {
            $this->error('Invalid format. Provider name must be in kebab-case (lowercase letters and hyphens only).');
            $this->error("Examples: 'adyen', 'pay-pal', 'stripe-connect'");
            $this->error("Invalid: 'PayPal', 'pay_pal', '-adyen', 'adyen-'");

            return false;
        }

        return true;
    }

    /**
     * Convert kebab-case to all required case formats
     */
    private function convertCases(string $kebabCase): void
    {
        // Split by hyphen
        $words = explode('-', $kebabCase);

        // PascalCase: capitalize first letter of each word
        $this->providerNamePascal = implode('', array_map('ucfirst', $words));

        // camelCase: capitalize first letter of each word except first
        $camelWords = $words;
        $camelWords[0] = strtolower($camelWords[0]);
        $countWords = count($camelWords);
        for ($i = 1; $i < $countWords; $i++) {
            $camelWords[$i] = ucfirst($camelWords[$i]);
        }
        $this->providerNameCamel = implode('', $camelWords);

        // snake_case: join with underscore
        $this->providerNameSnake = implode('_', $words);

        // SCREAMING_SNAKE_CASE: uppercase snake_case
        $this->providerNameScreaming = strtoupper($this->providerNameSnake);
    }

    /**
     * Convert PascalCase namespace to kebab-case
     * Examples: Acme → acme, MyCompany → my-company, SprykerEco → spryker
     */
    private function convertToKebabCase(string $pascalCase): string
    {
        // Insert hyphen before capital letters (except first character)
        $kebabCase = preg_replace('/([a-z])([A-Z])/', '$1-$2', $pascalCase);

        // Convert to lowercase
        return strtolower($kebabCase);
    }

    /**
     * Validate source directory
     */
    private function validateSource(): bool
    {
        if (!is_dir($this->sourcePath)) {
            $this->error("Source directory does not exist: {$this->sourcePath}");

            return false;
        }

        // Validate that this is a stripe structure
        $requiredPaths = [
            'src/SprykerEco',
            'composer.json',
        ];

        foreach ($requiredPaths as $path) {
            $fullPath = $this->sourcePath . DIRECTORY_SEPARATOR . $path;
            if (!file_exists($fullPath)) {
                $this->error("Invalid stripe structure. Missing: {$path}");

                return false;
            }
        }

        return true;
    }

    /**
     * Validate project path for project integration mode
     */
    private function validateProjectPath(): bool
    {
        if (!is_dir($this->projectPath)) {
            $this->error("Project path does not exist: {$this->projectPath}");

            return false;
        }

        // Check if it's a valid SprykerEco project
        $srcPath = $this->projectPath . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($srcPath)) {
            $this->error('Not a valid SprykerEco project. Missing src/ directory.');

            return false;
        }

        // Check if namespace directory structure exists
        $namespacePath = $srcPath . DIRECTORY_SEPARATOR . $this->namespace;
        if (!is_dir($namespacePath)) {
            $this->error("Namespace directory does not exist: {$namespacePath}");
            $this->output("Hint: Create the directory structure first or check your --namespace parameter.\n");

            return false;
        }

        // Check for required layer directories
        $requiredLayers = ['Zed', 'Yves', 'Client', 'Shared'];
        foreach ($requiredLayers as $layer) {
            $layerPath = $namespacePath . DIRECTORY_SEPARATOR . $layer;
            if (!is_dir($layerPath)) {
                $this->error("Required layer directory does not exist: {$layerPath}");
                $this->output("Hint: Create src/{$this->namespace}/{$layer}/ directory first.\n");

                return false;
            }
        }

        // Check if target module already exists
        foreach ($requiredLayers as $layer) {
            $targetPath = $namespacePath . DIRECTORY_SEPARATOR . $layer . DIRECTORY_SEPARATOR . $this->providerNamePascal;
            if (is_dir($targetPath)) {
                $this->error("Target module already exists: {$targetPath}");
                $this->output("Hint: Remove existing module or choose a different name.\n");

                return false;
            }
        }

        return true;
    }

    /**
     * Check if destination exists and prompt for overwrite
     */
    private function checkDestination(): bool
    {
        // Skip destination check for in-place mode
        if ($this->inPlace) {
            return true;
        }

        if (is_dir($this->destinationPath)) {
            $this->output("Error: Directory '{$this->destinationPath}' already exists.\n");
            $this->output('Overwrite? [y/N]: ');

            $answer = trim(fgets(STDIN));
            if (strtolower($answer) !== 'y') {
                return false;
            }

            // Remove existing directory
            $this->removeDirectory($this->destinationPath);
        }

        return true;
    }

    /**
     * Show confirmation prompt
     */
    private function confirm(): bool
    {
        if (!$this->projectMode && !$this->checkDestination()) {
            return false;
        }

        $fileCount = $this->countFiles();

        $this->output("\nSummary:\n");

        if ($this->projectMode) {
            $this->output("  Mode: Project Integration (split across layers)\n");
            $this->output("  Provider name: {$this->providerNameKebab} (PascalCase: {$this->providerNamePascal})\n");
            $this->output("  Namespace: {$this->namespace}\n");
            $this->output("  Source: {$this->sourcePath}\n");
            $this->output("  Target project: {$this->projectPath}\n");
            $this->output("  Files to process: ~{$fileCount} files\n");
            $this->output("  Operations:\n");
            $this->output("    - Copy Zed layer to src/{$this->namespace}/Zed/{$this->providerNamePascal}/\n");
            $this->output("    - Copy Yves layer to src/{$this->namespace}/Yves/{$this->providerNamePascal}/\n");
            $this->output("    - Copy Client layer to src/{$this->namespace}/Client/{$this->providerNamePascal}/\n");
            $this->output("    - Copy Shared layer to src/{$this->namespace}/Shared/{$this->providerNamePascal}/\n");
            $this->output("    - Update all namespaces to {$this->namespace}\n");
            $this->output("    - Skip composer.json, data/, config/, tests/ (project-level files)\n");
        } else {
            $this->output('  Mode: ' . ($this->inPlace ? 'In-place (modify current directory)' : 'Copy to new directory') . "\n");
            $this->output("  Provider name: {$this->providerNameKebab} (PascalCase: {$this->providerNamePascal})\n");
            $this->output("  Namespace: {$this->namespace}\n");
            if ($this->inPlace) {
                $this->output("  Working directory: {$this->sourcePath}\n");
            } else {
                $this->output("  Source: {$this->sourcePath}\n");
                $this->output("  Destination: {$this->destinationPath}\n");
            }
            $this->output("  Files to process: ~{$fileCount} files\n");
            $this->output("  Operations:\n");
            if (!$this->inPlace) {
                $this->output("    - Copy all files to {$this->destinationPath}\n");
            }
            $this->output("    - Rename files containing 'Stripe'\n");
            $this->output("    - Update content in PHP, XML, and documentation files\n");
            $this->output("    - Update composer.json package name\n");
        }

        $this->output("\nContinue? [y/N]: ");

        $answer = trim(fgets(STDIN));

        return strtolower($answer) === 'y';
    }

    /**
     * Show dry-run information
     */
    private function showDryRunInfo(): void
    {
        $this->output("\n=== DRY RUN MODE - No changes will be made ===\n\n");

        $this->output("Mode:\n");
        if ($this->projectMode) {
            $this->output("  Project Integration (split across layers)\n");
            $this->output("  Namespace: {$this->namespace}\n");
            $this->output("  Project path: {$this->projectPath}\n\n");
        } else {
            $this->output("  Module Creation\n");
            $this->output("  Namespace: {$this->namespace}\n");
            $this->output('  In-place: ' . ($this->inPlace ? 'Yes' : 'No') . "\n\n");
        }

        $this->output("Paths:\n");
        $this->output("  Source: {$this->sourcePath}\n");
        if (!$this->projectMode) {
            $this->output("  Destination: {$this->destinationPath}\n");
        }
        $this->output("\n");

        $this->output("Provider names:\n");
        $this->output("  kebab-case: {$this->providerNameKebab}\n");
        $this->output("  PascalCase: {$this->providerNamePascal}\n");
        $this->output("  camelCase: {$this->providerNameCamel}\n");
        $this->output("  snake_case: {$this->providerNameSnake}\n");
        $this->output("  SCREAMING_SNAKE_CASE: {$this->providerNameScreaming}\n\n");

        $this->showSampleRenames();
    }

    /**
     * Show sample file renames for dry-run
     */
    private function showSampleRenames(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->sourcePath)->name('*Stripe*');

        $renames = [];
        foreach ($finder as $file) {
            $oldName = $file->getFilename();
            $newName = $this->renameFile($oldName);
            if ($oldName !== $newName) {
                $renames[] = "  {$oldName} → {$newName}";
            }
            if (count($renames) >= 10) {
                break;
            }
        }

        if ($renames) {
            $this->output('File renames (showing first ' . count($renames) . "):\n");
            foreach ($renames as $rename) {
                $this->output($rename . "\n");
            }
            $this->output("\n");
        }

        $this->output("Content replacements (will be applied):\n");
        $this->output("  Namespaces: SprykerEco\\Zed\\Stripe → {$this->namespace}\\Zed\\{$this->providerNamePascal}\n");
        $this->output("  Class names: StripeFacade → {$this->providerNamePascal}Facade\n");
        $this->output("  Variables: \$stripeTransfer → \${$this->providerNameCamel}Transfer\n");
        $this->output("  Routes: stripe-redirect → {$this->providerNameKebab}-redirect\n");
        $this->output("  Tables: spy_stripe → spy_{$this->providerNameSnake}\n");
        $this->output("  Constants: STRIPE → {$this->providerNameScreaming}\n");
        if ($this->namespace !== self::TEMPLATE_NAMESPACE) {
            $this->output("  Namespace root: SprykerEco → {$this->namespace}\n");
        }
        $this->output("\n");
    }

    /**
     * Execute the renaming operation
     */
    private function execute(): void
    {
        $this->output("\nStarting renaming process...\n\n");

        if ($this->projectMode) {
            // Project integration mode: split copy across layers
            $this->copyToProjectLayers();
        } else {
            // Module creation mode: normal copy/rename flow
            // Step 1: Copy files (skip for in-place mode)
            if (!$this->inPlace) {
                $this->copyFiles();
            }

            // Step 2: Rename files and directories
            $this->renameFilesAndDirectories();

            // Step 3: Update file contents
            $this->updateFileContents();

            // Step 4: Update composer.json
            $this->updateComposerJson();
        }
    }

    /**
     * Copy all files from source to destination
     *
     * @throws \Exception
     */
    private function copyFiles(): void
    {
        $this->output("Copying files...\n");

        if (!mkdir($this->destinationPath, 0755, true)) {
            throw new Exception("Failed to create destination directory: {$this->destinationPath}");
        }

        $this->recursiveCopy($this->sourcePath, $this->destinationPath);

        $this->output("✓ Files copied successfully\n\n");
    }

    /**
     * Copy files to project layers (project integration mode)
     */
    private function copyToProjectLayers(): void
    {
        $this->output("Copying files to project layers...\n");

        $layers = ['Zed', 'Yves', 'Client', 'Shared'];
        $sourceBase = $this->sourcePath . '/src/SprykerEco';

        foreach ($layers as $layer) {
            $sourceLayerPath = $sourceBase . '/' . $layer . '/Stripe';
            $targetLayerPath = $this->projectPath . '/src/' . $this->namespace . '/' . $layer . '/' . $this->providerNamePascal;

            if (!is_dir($sourceLayerPath)) {
                $this->output("  ⚠ Skipping {$layer} layer (not found in template)\n");

                continue;
            }

            $this->output("  Copying {$layer} layer...\n");

            // Create target directory
            if (!is_dir($targetLayerPath)) {
                mkdir($targetLayerPath, 0755, true);
            }

            // Copy layer files
            $this->recursiveCopyLayer($sourceLayerPath, $targetLayerPath);

            // Rename files and update content in this layer
            $this->renameFilesInPath($targetLayerPath);
            $this->updateFileContentsInPath($targetLayerPath);
        }

        $this->output("\n✓ All layers copied and processed\n");
    }

    /**
     * Recursively copy layer directory with filtering
     */
    private function recursiveCopyLayer(string $source, string $destination): void
    {
        $dir = opendir($source);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
                $this->recursiveCopyLayer($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
                chmod($destPath, fileperms($sourcePath));
                $this->filesProcessed++;
            }
        }

        closedir($dir);
    }

    /**
     * Recursively copy directory
     */
    private function recursiveCopy(string $source, string $destination): void
    {
        $dir = opendir($source);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Skip vendor, .git, and other non-essential directories
            if (in_array($file, ['vendor', '.git', '.idea', 'node_modules'], true)) {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
                $this->recursiveCopy($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
                chmod($destPath, fileperms($sourcePath));
                $this->filesProcessed++;
            }
        }

        closedir($dir);
    }

    /**
     * Rename files and directories containing 'Stripe'
     */
    private function renameFilesAndDirectories(): void
    {
        $this->renameFilesInPath($this->destinationPath);
    }

    /**
     * Rename files in specific path
     */
    private function renameFilesInPath(string $path): void
    {
        // First, rename files (search for all case variations)
        $finder = new Finder();
        $finder->files()->in($path)->name(['*Stripe*', '*stripe*', '*stripe*', '*stripe*']);

        $filesToRename = [];
        foreach ($finder as $file) {
            $filesToRename[] = $file->getRealPath();
        }

        // Remove duplicates
        $filesToRename = array_unique($filesToRename);

        foreach ($filesToRename as $oldPath) {
            $directory = dirname($oldPath);
            $oldName = basename($oldPath);
            $newName = $this->renameFile($oldName);

            if ($oldName !== $newName) {
                $newPath = $directory . DIRECTORY_SEPARATOR . $newName;
                rename($oldPath, $newPath);
                $this->filesRenamed++;
            }
        }

        // Then, rename directories (bottom-up to avoid path issues)
        $finder = new Finder();
        $finder->directories()->in($path)->name(['*Stripe*', '*stripe*', '*stripe*', '*stripe*']);

        $dirsToRename = [];
        foreach ($finder as $dir) {
            $dirsToRename[] = $dir->getRealPath();
        }

        // Remove duplicates
        $dirsToRename = array_unique($dirsToRename);

        // Sort by depth (deepest first)
        usort($dirsToRename, fn ($a, $b) => substr_count($b, DIRECTORY_SEPARATOR) - substr_count($a, DIRECTORY_SEPARATOR));

        foreach ($dirsToRename as $oldPath) {
            $parent = dirname($oldPath);
            $oldName = basename($oldPath);
            $newName = $this->renameFile($oldName);

            if ($oldName !== $newName) {
                $newPath = $parent . DIRECTORY_SEPARATOR . $newName;
                rename($oldPath, $newPath);
                $this->filesRenamed++;
            }
        }
    }

    /**
     * Rename a single file/directory name
     */
    private function renameFile(string $name): string
    {
        $name = str_replace(self::TEMPLATE_PASCAL, $this->providerNamePascal, $name);
        $name = str_replace(self::TEMPLATE_CAMEL, $this->providerNameCamel, $name);
        $name = str_replace(self::TEMPLATE_KEBAB, $this->providerNameKebab, $name);
        $name = str_replace(self::TEMPLATE_SNAKE, $this->providerNameSnake, $name);
        $name = str_replace(self::TEMPLATE_SCREAMING, $this->providerNameScreaming, $name);

        return $name;
    }

    /**
     * Update file contents
     */
    private function updateFileContents(): void
    {
        $this->updateFileContentsInPath($this->destinationPath);
    }

    /**
     * Update file contents in specific path
     */
    private function updateFileContentsInPath(string $path): void
    {
        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name(['*.php', '*.xml', '*.md', '*.yml', '*.yaml', '*.json', '*.csv'])
            ->exclude(['vendor', 'node_modules', '.git']);

        foreach ($finder as $file) {
            // Skip composer.json (handled separately in module mode)
            if ($file->getFilename() === 'composer.json' && !$this->projectMode) {
                continue;
            }

            $this->updateFileContent($file->getRealPath());
        }
    }

    /**
     * Update a single file's content
     */
    private function updateFileContent(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Apply replacements in order (most specific first to avoid partial replacements)

        // 1. PascalCase - replaces Stripe and StripeFacade, StripeConfig, etc.
        $content = str_replace(self::TEMPLATE_PASCAL, $this->providerNamePascal, $content);

        // 2. camelCase - replaces stripe and stripeTransfer, stripeConfig, etc.
        $content = str_replace(self::TEMPLATE_CAMEL, $this->providerNameCamel, $content);

        // 3. kebab-case strings
        $content = str_replace(self::TEMPLATE_KEBAB, $this->providerNameKebab, $content);

        // 4. snake_case strings
        $content = str_replace(self::TEMPLATE_SNAKE, $this->providerNameSnake, $content);

        // 5. SCREAMING_SNAKE_CASE strings
        $content = str_replace(self::TEMPLATE_SCREAMING, $this->providerNameScreaming, $content);

        // 6. Namespace replacement (if different from template)
        if ($this->namespace !== self::TEMPLATE_NAMESPACE) {
            $content = str_replace(self::TEMPLATE_NAMESPACE, $this->namespace, $content);
            // Also update test namespace
            $content = str_replace(self::TEMPLATE_NAMESPACE . 'Test', $this->namespace . 'Test', $content);
        }

        // Write back if changed
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->contentUpdates++;
        }
    }

    /**
     * Update composer.json
     *
     * @throws \Exception
     */
    private function updateComposerJson(): void
    {
        // Skip in project mode - project has its own composer.json
        if ($this->projectMode) {
            return;
        }

        $this->output("Updating composer.json...\n");

        $composerPath = $this->destinationPath . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerPath)) {
            $this->output("⚠ composer.json not found, skipping\n\n");

            return;
        }

        $composerContent = file_get_contents($composerPath);
        $composer = json_decode($composerContent, true);

        if (!$composer) {
            throw new Exception('Failed to parse composer.json');
        }

        // Update package name with namespace (convert to kebab-case)
        $namespaceKebab = $this->convertToKebabCase($this->namespace);
        $composer['name'] = $namespaceKebab . '/' . $this->providerNameKebab;

        // Update description
        if (isset($composer['description'])) {
            $composer['description'] = str_replace(
                'Payment Template',
                $this->providerNamePascal,
                $composer['description'],
            );
        }

        // Update autoload PSR-4 namespace if custom namespace is used
        if ($this->namespace !== self::TEMPLATE_NAMESPACE) {
            if (isset($composer['autoload']['psr-4'])) {
                $newAutoload = [];
                foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                    // Replace SprykerEco with custom namespace
                    $newNamespace = str_replace(self::TEMPLATE_NAMESPACE . '\\', $this->namespace . '\\', $namespace);
                    $newPath = str_replace(self::TEMPLATE_NAMESPACE, $this->namespace, $path);
                    $newAutoload[$newNamespace] = $newPath;
                }
                $composer['autoload']['psr-4'] = $newAutoload;
            }

            // Also update autoload-dev if exists
            if (isset($composer['autoload-dev']['psr-4'])) {
                $newAutoloadDev = [];
                foreach ($composer['autoload-dev']['psr-4'] as $namespace => $path) {
                    $newNamespace = str_replace(self::TEMPLATE_NAMESPACE . 'Test\\', $this->namespace . 'Test\\', $namespace);
                    $newPath = str_replace(self::TEMPLATE_NAMESPACE . 'Test', $this->namespace . 'Test', $path);
                    $newAutoloadDev[$newNamespace] = $newPath;
                }
                $composer['autoload-dev']['psr-4'] = $newAutoloadDev;
            }
        }

        // Write back with pretty print
        $newContent = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($composerPath, $newContent);

        $this->output("✓ composer.json updated\n\n");
    }

    /**
     * Count files in source directory
     */
    private function countFiles(): int
    {
        $finder = new Finder();
        $finder->files()->in($this->sourcePath)->exclude(['vendor', 'node_modules', '.git']);

        return iterator_count($finder);
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Show success message
     */
    private function showSuccess(): void
    {
        if ($this->projectMode) {
            $this->output("\n✓ Successfully integrated {$this->providerNamePascal} payment module into project!\n");
            $this->output("  Namespace: {$this->namespace}\n");
            $this->output("  Project: {$this->projectPath}\n");
            $this->output("  Files processed: {$this->filesProcessed}\n");
            $this->output("  Files renamed: {$this->filesRenamed}\n");
            $this->output("  Content updates: {$this->contentUpdates}\n\n");

            $this->output("Module integrated at:\n");
            $this->output("  - src/{$this->namespace}/Zed/{$this->providerNamePascal}/\n");
            $this->output("  - src/{$this->namespace}/Yves/{$this->providerNamePascal}/\n");
            $this->output("  - src/{$this->namespace}/Client/{$this->providerNamePascal}/\n");
            $this->output("  - src/{$this->namespace}/Shared/{$this->providerNamePascal}/\n\n");

            $this->output("Next steps:\n");
            $this->output("  1. Review changes: cd {$this->projectPath} && git status\n");
            $this->output("  2. Follow IMPLEMENTATION.md for PSP-specific implementation\n");
            $this->output("  3. Register plugins in your project's dependency providers\n");
            $this->output("  4. Run: vendor/bin/console transfer:generate\n");
            $this->output("  5. Run: vendor/bin/console propel:install\n");
            $this->output("  6. Commit: git add . && git commit -m \"Add {$this->providerNamePascal} payment integration\"\n\n");

            $this->output("Note: Template files remain at {$this->sourcePath}\n");
            $this->output("      You can safely delete this directory after reviewing the integration.\n");
        } elseif ($this->inPlace) {
            $this->output("\n✓ Successfully renamed payment module to: {$this->providerNamePascal}\n");
            $this->output("  Namespace: {$this->namespace}\n");
            $this->output("  Location: {$this->destinationPath}\n");
            $this->output("  Files renamed: {$this->filesRenamed}\n");
            $this->output("  Content updates: {$this->contentUpdates}\n\n");

            $this->output("Next steps:\n");
            $this->output("  1. Review changes: git status\n");
            $this->output("  2. Stage changes: git add .\n");
            $this->output("  3. Commit: git commit -m \"Rename from Stripe to {$this->providerNamePascal}\"\n");
            $this->output("  4. Push: git push\n");
        } else {
            $this->output("\n✓ Successfully created payment module: {$this->providerNamePascal}\n");
            $this->output("  Namespace: {$this->namespace}\n");
            $this->output("  Location: {$this->destinationPath}\n");
            $this->output("  Files processed: {$this->filesProcessed}\n");
            $this->output("  Files renamed: {$this->filesRenamed}\n");
            $this->output("  Content updates: {$this->contentUpdates}\n\n");

            $this->output("Next steps:\n");
            $this->output("  1. cd {$this->destinationPath}\n");
            $this->output("  2. git init\n");
            $this->output("  3. git add .\n");
            $this->output("  4. git commit -m \"Initial commit: {$this->providerNamePascal} payment module\"\n");
            $this->output("  5. git remote add origin <your-repo-url>\n");
            $this->output("  6. git push -u origin main\n");
        }
    }

    /**
     * Output message to stdout
     */
    private function output(string $message): void
    {
        echo $message;
    }

    /**
     * Output error message to stderr
     */
    private function error(string $message): void
    {
        fwrite(STDERR, $message . "\n");
    }
}

// Execute
$renamer = new StripeRenamer();
exit($renamer->run($argv));
