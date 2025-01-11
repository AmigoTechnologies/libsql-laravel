<?php

namespace Libsql\Laravel;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Database\Query\Builder;

class LibsqlServiceProvider extends PackageServiceProvider
{
	public function boot(): void
	{
		parent::boot();
		// Register the libsql driver
		$this->app->make('db')->extend('libsql', function ($config, $name) {
			$config['name'] = $name;
			$config['driver'] = 'libsql';

			return new LibsqlConnection(
				function () use ($config) {
					return new \Libsql\PDO(
						$config["database"] ?? '',
						password: $config["password"] ?? '',
						options: $config
					);
				},
				database: $config["database"],
				config: $config,
			);
		});

		Blueprint::macro('vectorIndex', function ($column, $indexName) {
			/** @var Blueprint $this **/
			return DB::statement("CREATE INDEX {$indexName} ON {$this->table}(libsql_vector_idx({$column}))");
		});

		Builder::macro('nearest', function ($indexName, $vector, $limit = 10) {
			/** @var Builder $this **/
			return $this->joinSub(
				DB::table(DB::raw("vector_top_k('$indexName', '[" . implode(',', $vector) . "]', $limit)")),
				'v',
				"{$this->from}.rowid",
				'=',
				'v.id'
			);
		});
	}


	public function configurePackage(Package $package): void
	{
		$package->name('libsql-laravel');
	}

	public function register(): void
	{
		parent::register();
	}
}

