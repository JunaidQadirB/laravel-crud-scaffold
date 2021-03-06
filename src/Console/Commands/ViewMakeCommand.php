<?php

namespace MoonBear\LaravelCrudScaffold\Console\Commands;


use Config;
use Illuminate\Support\Str;
use MoonBear\LaravelCrudScaffold\Console\Contracts\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ViewMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mbt:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new View';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    private $fileName = 'index';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        /*  if (parent::handle() === false && ! $this->option('force')) {
              return;
          }*/
        if (!$this->option('index') && !$this->option('create') && !$this->option('edit') && !$this->option('all')) {
            $this->input->setOption('index', true);
        }
        $this->createView();

    }

    /**
     * Create a view for the model.
     *
     * @return void
     */
    protected function createView()
    {
        $viewDirSlug = Str::slug(Str::plural(str_to_words($this->argument('name')), 2));

        $viewPath = Config::get('view.paths')[0];
        $dir = $this->option('dir');
        $path = $viewPath . '/' . $viewDirSlug;

        if ($dir) {
            $path = $viewPath . '/' . $dir . '/' . $viewDirSlug;
        }


        $this->createViewDirectory();

//        $this->input->setOption('all', true);

        if ($this->option('all')) {
            $this->input->setOption('index', true);
            $this->input->setOption('create', true);
            $this->input->setOption('edit', true);
            $this->input->setOption('show', true);
        }

        if ($this->option('index')) {
            $this->buildView('index', $path);
            $this->createDeleteView($path);
        }

        if ($this->option('create')) {
            $this->buildView('create', $path);
        }

        if ($this->option('edit')) {
            $this->buildView('edit', $path);
        }

        if ($this->option('show')) {
            $this->buildView('show', $path);
        }
    }

    /**
     *
     */
    protected function createViewDirectory()
    {
        $name = $this->argument('name');
        $viewDirSlug = Str::slug(Str::plural(str_to_words($name), 2));
        $viewPath = Config::get('view.paths')[0];
        $dir = $this->option('dir');
        $path = $viewPath . '/' . $viewDirSlug;

        if ($dir) {
            $path = $viewPath . '/' . $dir . '/' . $viewDirSlug;
        }

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        } else {
            $this->warn($viewDirSlug . ' exists. Ignoring');
        }
    }

    protected function buildView($type, $path)
    {
        $name = $this->argument('name');
        $this->fileName = $type;
        $stub = $this->files->get($this->getStub());
        $viewLabel = Str::plural(str_to_words($name), 2);
        $viewName = Str::camel($viewLabel);
        $stub = $this->replacePlaceholders($stub, $name, $path);
        $target = $path . '/' . $type . '.blade.php';

        if (file_exists($target) && !$this->option('force')) {
            $this->error("File already exists. Cannot overwrite {$target}.");
        } else {
            file_put_contents($target, $stub);
            $this->info("View successfully created in {$target}");
        }
        if ($type == 'index') {
            /**
             * Create the _form partial form the stub
             */
            $formPartial = $path . '/_form.blade.php';
            $formStub = $this->files->get($this->getStub('_form'));

            if (file_exists($formPartial) && !$this->option('force')) {
//            $this->error("File already exists. Cannot overwrite {$formPartial}.");
            } else {
                file_put_contents($formPartial, $formStub);
                $this->info("View successfully created in {$formPartial}");
            }
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @param null|string $fileName
     * @return string
     */
    protected function getStub($fileName = null)
    {
        if (isset($fileName)) {
            $this->fileName = $fileName;
        }
        $stubsPath = "stubs/view/{$this->fileName}.stub";
        $stubs = $this->option('stubs');

        if ($stubs) {
            $stubsPath = $stubs . '/' . $this->fileName . ".stub";
        }
        return resource_path($stubsPath);
    }

    /**
     * Replace all placeholders
     *
     * @param $stub
     * @param $name
     * @param null $path
     *
     * @return mixed
     */
    protected function replacePlaceholders($stub, $name, $path = null)
    {
        $modelSlug = Str::slug(Str::plural(str_to_words($name), 2));

        $viewLabel = str_to_words($name);
        $stub = str_replace('$label$', $viewLabel, $stub);

        $viewLabelPlural = Str::plural(str_to_words($name));
        $stub = str_replace('$labelPlural$', $viewLabelPlural, $stub);

        $viewName = Str::camel($name);
        $stub = str_replace('$name$', $viewName, $stub);

        $stub = str_replace('$model$', $name, $stub);
        $stub = str_replace('$modelSlug$', $modelSlug, $stub);

        $dir = $this->option('dir');
        if ($dir) {
            $dir = str_replace('/', '.', $dir);
            $stub = str_replace('$dir$', $dir . '.', $stub);
        } else {
            $stub = str_replace('$dir$', '', $stub);
        }
        $stub = str_replace('$rows$', '$' . Str::camel(Str::plural($name, 2)), $stub);
        $stub = str_replace('$row$', '$' . Str::camel($name), $stub);

        return $stub;
    }

    protected function createDeleteView($path)
    {
        if (!file_exists($path . '/modals')) {
            mkdir($path . '/modals');
        }
        $this->buildView('delete', $path . '/modals');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Generate an index, create, and an edit view for the model',
            ],

            ['index', 'i', InputOption::VALUE_NONE, 'Create a only the index view for the model'],

            ['create', 'c', InputOption::VALUE_NONE, 'Create only the create view for the model'],

            ['edit', 'e', InputOption::VALUE_NONE, 'Create only the edit view for the model'],

            ['show', 's', InputOption::VALUE_NONE, 'Create only the show view for the model'],

            ['force', 'f', InputOption::VALUE_NONE, 'Create the file even if the file already exists.'],

            ['dir', 'd', InputOption::VALUE_OPTIONAL, 'Create the file inside this directory within the view.'],

            ['stubs', 'b', InputOption::VALUE_OPTIONAL, 'Use stubs from the specified directory.'],
        ];
    }
}
