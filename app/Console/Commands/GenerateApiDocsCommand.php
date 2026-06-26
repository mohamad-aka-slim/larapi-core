<?php

namespace App\Console\Commands;

use App\Support\OpenApiSpecification;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateApiDocsCommand extends Command
{
    protected $signature = 'api:docs
        {--format=json : Output format, json or yaml}
        {--output= : Destination file path. Defaults to config(api.documentation.output_path)}';

    protected $description = 'Generate the OpenAPI Swagger documentation file.';

    public function __construct(
        private readonly Filesystem $files,
        private readonly OpenApiSpecification $openApi,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $format = Str::lower((string) $this->option('format'));

        if (! in_array($format, ['json', 'yaml', 'yml'], true)) {
            $this->components->error('Unsupported format. Use json or yaml.');

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: config('api.documentation.output_path'));

        if ($format !== 'json' && ! $this->option('output')) {
            $output = preg_replace('/\.json$/', '.yaml', $output) ?: $output;
        }

        $this->files->ensureDirectoryExists(dirname($output));

        $contents = $format === 'json'
            ? $this->openApi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            : $this->openApi->toYaml();

        $this->files->put($output, $contents);
        $this->components->info("OpenAPI {$format} documentation generated at [{$output}].");

        return self::SUCCESS;
    }
}
