<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class MakeResourceCommand extends Command
{
    protected $signature = 'make:resources {model : The name of the model}';
    protected $description = 'Generate Form Requests, Repository, Service, and Controller classes for a model';

    public function handle(): void
    {
        $modelName = $this->argument('model');
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            $this->error("Model {$modelName} does not exist!");
            return;
        }

        $this->generateStubs();
        $this->generateResource($modelName);
        $this->info("Resource classes generated successfully for {$modelName}!");
    }

    protected function generateStubs(): void
    {
        $stubs = [
            'form-request.stub' => <<<EOT
<?php

namespace App\\Http\\Requests\\{{modelName}};

use Illuminate\\Foundation\\Http\\FormRequest;

class {{className}} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return {{rules}};
    }
}
EOT,
            'repository.stub' => <<<EOT
<?php

namespace App\\Repositories\\{{modelName}};

use App\\Models\\{{modelName}};
use App\\Repositories\\Contracts\\{{modelName}}RepositoryInterface;

class {{modelName}}Repository extends BaseRepository implements {{modelName}}RepositoryInterface
{
    protected \$entity;

    public function __construct({{modelName}} \$model)
    {
        \$this->entity = \$model;
    }
}
EOT,
            'repository-interface.stub' => <<<EOT
<?php

namespace App\\Repositories\\Contracts;

use App\\Repositories\\Contracts\\RepositoryInterface;

interface {{modelName}}RepositoryInterface extends RepositoryInterface
{
    // Add model-specific repository methods here
}
EOT,
            'service.stub' => <<<EOT
<?php

namespace App\\Services\\{{modelName}};

use App\\Services\\Contracts\\{{modelName}}ServiceInterface;
use App\\Repositories\\Contracts\\{{modelName}}RepositoryInterface;

class {{modelName}}Service extends BaseService implements {{modelName}}ServiceInterface
{
    public function __construct({{modelName}}RepositoryInterface \$repository)
    {
        parent::__construct(\$repository);
    }
}
EOT,
            'service-interface.stub' => <<<EOT
<?php

namespace App\\Services\\Contracts;

use App\\Services\\Contracts\\ServiceInterface;

interface {{modelName}}ServiceInterface extends ServiceInterface
{
    // Add model-specific service methods here
}
EOT,
            'controller.stub' => <<<EOT
<?php

namespace App\\Http\\Controllers;

use App\\Services\\Contracts\\{{modelName}}ServiceInterface;
use App\\Http\\Requests\\{{modelName}}\\Store{{modelName}}Request;
use App\\Http\\Requests\\{{modelName}}\\Update{{modelName}}Request;
use App\\Http\\Requests\\{{modelName}}\\List{{modelName}}Request;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Database\\Eloquent\\ModelNotFoundException;

class {{modelName}}Controller extends Controller
{
    protected {{modelName}}ServiceInterface \$service;

    public function __construct({{modelName}}ServiceInterface \$service)
    {
        \$this->service = \$service;
    }

    public function index(List{{modelName}}Request \$request): JsonResponse
    {
        try {
            \$filters = \$request->validated()['filters'] ?? [];
            \$this->service->setFilters(\$filters);
            \$data = \$this->service->get();
            return response()->json(['data' => \$data], 200);
        } catch (\Exception \$e) {
            return response()->json(['error' => 'Failed to retrieve {{modelLower}}s'], 500);
        }
    }

    public function store(Store{{modelName}}Request \$request): JsonResponse
    {
        try {
            \$data = \$this->service->create(\$request->validated());
            return response()->json(['data' => \$data, 'message' => '{{modelName}} created successfully'], 201);
        } catch (\Exception \$e) {
            return response()->json(['error' => 'Failed to create {{modelLower}}'], 500);
        }
    }

    public function show(\$id): JsonResponse
    {
        try {
            \$data = \$this->service->show(\$id);
            if (!\$data) {
                throw new ModelNotFoundException();
            }
            return response()->json(['data' => \$data], 200);
        } catch (ModelNotFoundException \$e) {
            return response()->json(['error' => '{{modelName}} not found'], 404);
        } catch (\Exception \$e) {
            return response()->json(['error' => 'Failed to retrieve {{modelLower}}'], 500);
        }
    }

    public function update(Update{{modelName}}Request \$request, \$id): JsonResponse
    {
        try {
            \$data = \$this->service->edit(\$request->validated(), \$id);
            if (!\$data) {
                throw new ModelNotFoundException();
            }
            return response()->json(['data' => \$data, 'message' => '{{modelName}} updated successfully'], 200);
        } catch (ModelNotFoundException \$e) {
            return response()->json(['error' => '{{modelName}} not found'], 404);
        } catch (\Exception \$e) {
            return response()->json(['error' => 'Failed to update {{modelLower}}'], 500);
        }
    }

    public function destroy(\$id): JsonResponse
    {
        try {
            \$result = \$this->service->delete(\$id);
            if (!\$result) {
                throw new ModelNotFoundException();
            }
            return response()->json(['message' => '{{modelName}} deleted successfully'], 200);
        } catch (ModelNotFoundException \$e) {
            return response()->json(['error' => '{{modelName}} not found'], 404);
        } catch (\Exception \$e) {
            return response()->json(['error' => 'Failed to delete {{modelLower}}'], 500);
        }
    }
}
EOT,
        ];

        foreach ($stubs as $file => $content) {
            $path = base_path("stubs/{$file}");
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
        }
    }

    protected function generateResource(string $modelName): void
    {
        $model = app("App\\Models\\{$modelName}");
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        // Generate Repository and Interface
        $this->generateRepository($modelName);
        $this->generateRepositoryInterface($modelName);

        // Generate Service and Interface
        $this->generateService($modelName);
        $this->generateServiceInterface($modelName);

        // Generate Form Requests
        $rules = $this->generateValidationRules($columns, $table);
        $this->createFormRequest($modelName, 'Store', $rules['store']);
        $this->createFormRequest($modelName, 'Update', $rules['update']);
        $this->createFormRequest($modelName, 'List', $this->getListRules());

        // Generate Controller
        $this->generateController($modelName);
    }

    protected function generateValidationRules(array $columns, string $table): array
    {
        $storeRules = [];
        $updateRules = [];

        foreach ($columns as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $columnType = Schema::getColumnType($table, $column);
            $rules = [];

            switch ($columnType) {
                case 'string':
                    $rules[] = 'string';
                    $rules[] = 'max:255';
                    break;
                case 'text':
                    $rules[] = 'string';
                    break;
                case 'integer':
                case 'bigint':
                    $rules[] = 'integer';
                    break;
                case 'decimal':
                case 'double':
                    $rules[] = 'numeric';
                    break;
                case 'boolean':
                    $rules[] = 'boolean';
                    break;
                case 'date':
                case 'datetime':
                    $rules[] = 'date';
                    break;
                case 'json':
                    $rules[] = 'json';
                    break;
            }

            $isNullable = $this->isColumnNullable($table, $column);
            if ($isNullable) {
                $rules[] = 'nullable';
            } else {
                $storeRules[$column][] = 'required';
            }

            if ($this->isUniqueColumn($table, $column)) {
                $storeRules[$column][] = "unique:{$table},{$column}";
                $updateRules[$column][] = "unique:{$table},{$column},{\$this->route('id')}";
            }

            if (!empty($rules)) {
                $storeRules[$column] = array_merge($storeRules[$column] ?? [], $rules);
                $updateRules[$column] = array_merge($updateRules[$column] ?? [], $rules);
            }
        }

        return [
            'store' => $storeRules,
            'update' => $updateRules
        ];
    }

    protected function isColumnNullable(string $table, string $column): bool
    {
        $columnInfo = DB::select("
            SELECT is_nullable
            FROM information_schema.columns
            WHERE table_name = ?
            AND column_name = ?
        ", [$table, $column]);

        return !empty($columnInfo) && $columnInfo[0]->is_nullable === 'YES';
    }

    protected function isUniqueColumn(string $table, string $column): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $indexes = DB::select("
                SELECT indexname
                FROM pg_indexes
                WHERE tablename = ?
                AND indexdef LIKE ?
            ", [$table, "%{$column}%"]);

            foreach ($indexes as $index) {
                if (stripos($index->indexname, 'unique') !== false) {
                    return true;
                }
            }
        } elseif ($driver === 'mysql') {
            $indexes = DB::select("
                SHOW INDEXES FROM {$table}
                WHERE Column_name = ?
                AND Non_unique = 0
            ", [$column]);

            return !empty($indexes);
        }

        return false;
    }

    protected function getListRules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string'],
            'order_by' => ['nullable', 'in:asc,desc'],
            'filters' => ['nullable', 'array'],
            'filters.*.key' => ['required_with:filters', 'string'],
            'filters.*.value' => ['required_with:filters', 'nullable'],
            'filters.*.type' => ['required_with:filters', 'in:string,exact_string,array,not_in_array,intarray,intarrayand,number,bool,day,to,date,datefrom,dateto,from,json,between,isNull,isNotNull,custom'],
        ];
    }

    protected function createFormRequest(string $modelName, string $type, array $rules): void
    {
        $className = "{$type}{$modelName}Request";
        $namespace = "App\\Http\\Requests\\{$modelName}";
        $path = app_path("Http/Requests/{$modelName}/{$className}.php");

        $stub = File::get(base_path('stubs/form-request.stub'));
        $stub = str_replace(
            ['{{modelName}}', '{{className}}', '{{rules}}'],
            [$modelName, $className, $this->formatRules($rules)],
            $stub
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
    }

    protected function formatRules(array $rules): string
    {
        if (empty($rules)) {
            return '[]';
        }

        $rulesArray = [];
        foreach ($rules as $field => $fieldRules) {
            $rulesArray[] = "\"{$field}\" => [\"" . implode("\", \"", $fieldRules) . "\"]";
        }

        return "[\n        " . implode(",\n        ", $rulesArray) . "\n    ]";
    }

    protected function generateRepository(string $modelName): void
    {
        $className = "{$modelName}Repository";
        $path = app_path("Repositories/{$modelName}/{$className}.php");

        $stub = File::get(base_path('stubs/repository.stub'));
        $stub = str_replace('{{modelName}}', $modelName, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
    }

    protected function generateRepositoryInterface(string $modelName): void
    {
        $interfaceName = "{$modelName}RepositoryInterface";
        $path = app_path("Repositories/Contracts/{$interfaceName}.php");

        $stub = File::get(base_path('stubs/repository-interface.stub'));
        $stub = str_replace('{{modelName}}', $modelName, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
    }

    protected function generateService(string $modelName): void
    {
        $className = "{$modelName}Service";
        $path = app_path("Services/{$modelName}/{$className}.php");

        $stub = File::get(base_path('stubs/service.stub'));
        $stub = str_replace('{{modelName}}', $modelName, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
    }

    protected function generateServiceInterface(string $modelName): void
    {
        $interfaceName = "{$modelName}ServiceInterface";
        $path = app_path("Services/Contracts/{$interfaceName}.php");

        $stub = File::get(base_path('stubs/service-interface.stub'));
        $stub = str_replace('{{modelName}}', $modelName, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
    }

    protected function generateController(string $modelName): void
    {
        $className = "{$modelName}Controller";
        $path = app_path("Http/Controllers/{$className}.php");
        $modelLower = Str::lower($modelName);

        $stub = File::get(base_path('stubs/controller.stub'));
        $stub = str_replace(
            ['{{modelName}}', '{{modelLower}}'],
            [$modelName, $modelLower],
            $stub
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);
    }
}
