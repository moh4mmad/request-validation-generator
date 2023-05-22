<?php

namespace RequestValidationGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateRequestValidationsCommand extends Command
{
    protected $signature = 'generate:request-validations';
    protected $description = 'Generate request validation files based on migration column data types, column lengths, uniqueness, and foreign keys';
    protected $skipTables = ['jobs', 'failed_jobs', 'migrations', 'password_resets', 'personal_access_tokens', 'sessions'];
    protected $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at'];

    public function handle()
    {
        $migrationsPath = database_path('migrations');
        $migrationFiles = File::glob($migrationsPath . '/*.php');

        foreach ($migrationFiles as $migrationFile) {
            $this->generateRequestValidation($migrationFile);
        }

        $this->info('Request validation files generated successfully!');
    }

    protected function generateRequestValidation($migrationFile)
    {
        $migrationContents = file_get_contents($migrationFile);

        // Check if the migration is a "create" migration
        if (!preg_match('/Schema::create\(\'(\w+)\'/', $migrationContents, $matches)) {
            return;
        }

        $tableName = $matches[1];

        // Skip tables that are not meant to have request validations
        if (in_array($tableName, $this->skipTables)) {
            return;
        }

        // Get the columns from the migration
        $columns = [];
        preg_match_all('/\$table->(\w+)\([\'"](\w+)[\'"].*\)/', $migrationContents, $columnMatches, PREG_SET_ORDER);
        foreach ($columnMatches as $columnMatch) {
            // Skip columns that are not meant to have request validations
            if (in_array($columnMatch[2], $this->skipColumns)) {
                continue;
            }
            // Get the column data type and name  
            $columnType = strtolower($columnMatch[1]);
            $columnName = $columnMatch[2];

            // Get the column length, if applicable
            $columns[$columnName] = [
                'type' => $columnType,
                'length' => null,
                'nullable' => false,
                'unique' => false,
                'foreign' => false,
                'foreign_table' => null,
                'foreign_column' => null,
            ];

            // Get the column length, if applicable
            if ($columnType === 'string' && preg_match('/->(length|maxLength)\((\d+)\)/', $columnMatch[0], $lengthMatch)) {
                $columns[$columnName]['length'] = $lengthMatch[2];
            }

            // Check if the column is nullable, unique, or a foreign key

            if (strpos($columnMatch[0], '->nullable()') !== false) {
                $columns[$columnName]['nullable'] = true;
            }

            if (strpos($columnMatch[0], '->unique()') !== false) {
                $columns[$columnName]['unique'] = true;
            }

            if (preg_match('/->foreign\(\'(\w+)\'\)/', $columnMatch[0], $foreignMatch)) {
                $columns[$columnName]['foreign'] = true;
                $columns[$columnName]['foreign_table'] = $foreignMatch[1];

                if (preg_match('/->references\(\'(\w+)\'\)/', $columnMatch[0], $referencesMatch)) {
                    $columns[$columnName]['foreign_column'] = $referencesMatch[1];
                }
            }
        }

        // Generate the request validation file
        $rules = [];
        foreach ($columns as $columnName => $columnInfo) {
            $columnType = $columnInfo['type'];
            $columnLength = $columnInfo['length'];
            $nullable = $columnInfo['nullable'];
            $unique = $columnInfo['unique'];
            $foreign = $columnInfo['foreign'];
            $foreignTable = $columnInfo['foreign_table'];
            $foreignColumn = $columnInfo['foreign_column'];

            // Map column data types to validation rules
            $rule = $this->mapColumnTypeToRule($columnType, $columnLength);

            if (!empty($rule)) {
                if (!$nullable) {
                    $rule[] = 'required';
                }

                if ($unique) {
                    $rule[] = 'unique:' . $tableName . ',' . $columnName;
                }

                if ($foreign && !empty($foreignTable) && !empty($foreignColumn)) {
                    $rule[] = "exists:{$foreignTable},{$foreignColumn}";
                }

                $rules[$columnName] = $rule;
            }
        }

        // Generate the request validation file
        $className = Str::studly(Str::singular($tableName));

        $content = "<?php\n\nnamespace App\Http\Requests\\{$className}Request;\n\nuse Illuminate\Foundation\Http\FormRequest;\n\nclass Request extends FormRequest\n{\n    public function authorize()\n    {\n        return true;\n    }\n\n    public function rules()\n    {\n        return " . $this->formatValidationRules($rules) . ";\n    }\n}";

        $requestValidationsPath = app_path("Http/Requests/{$className}");
        $requestValidationFile = "{$requestValidationsPath}/{$className}Request.php";

        // Create the request validation file
        File::ensureDirectoryExists($requestValidationsPath);
        File::put($requestValidationFile, $content);
    }

    /*
        * Map column data types to validation rules
    */
    protected function mapColumnTypeToRule($columnType, $columnLength)
    {
        $rules = [
            'string' => ['string'],
            'text' => ['string'],
            'integer' => ['integer'],
            'biginteger' => ['integer'],
            'smallinteger' => ['integer'],
            'tinyinteger' => ['integer'],
            'decimal' => ['numeric'],
            'float' => ['numeric'],
            'boolean' => ['boolean'],
            'date' => ['date'],
            'datetime' => ['date'],
            'timestamp' => ['date'],
        ];

        $rule = $rules[$columnType] ?? [];

        if ($columnType === 'string' && $columnLength !== null) {
            $rule[] = "max:{$columnLength}";
        }

        return $rule;
    }

    /* 
        * Format the validation rules
    */
    protected function formatValidationRules($rules)
    {
        $formattedRules = [];

        foreach ($rules as $columnName => $columnRules) {
            $formattedRules[] = "'{$columnName}' => " . $this->formatRuleList($columnRules);
        }

        return "[" . implode(",\n            ", $formattedRules) . "\n        ]";
    }

    /*
        * Format a list of rules
    */
    protected function formatRuleList($rules)
    {
        $formattedRules = [];

        foreach ($rules as $rule) {
            $formattedRules[] = "'{$rule}'";
        }

        return "[" . implode(', ', $formattedRules) . "]";
    }
}
