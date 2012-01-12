<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * Command for importing translation files
 */
class ImportCommand extends Base {

    protected function configure() {
        parent::configure();

        $this
                ->setName('locale:editor:import')
                ->setDescription('Import translation files into MongoDB for using through /translations/editor')
                ->addArgument('filename')
                ->addOption("dry-run")
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        $filename = $input->getArgument('filename');

        $files = array();

        if (!empty($filename) && is_dir($filename)) {
            $output->writeln("Importing translations from <info>$filename</info>...");
            $finder = new Finder();
            $finder->files()->in($filename)->name('*');

            foreach ($finder as $file) {
                $output->writeln("Found <info>" . $file->getRealpath() . "</info>...");
                $files[] = $file->getRealpath();
            }
        } else {
            $dir = $this->getContainer()->getParameter('kernel.root_dir') . '/../src';

            $output->writeln("Scanning " . $dir . "...");
            $finder = new Finder();
            $finder->directories()->in($dir)->name('translations');

            foreach ($finder as $dir) {
                $finder2 = new Finder();
                $finder2->files()->in($dir->getRealpath())->name('*');
                foreach ($finder2 as $file) {
                    $output->writeln("Found <info>" . $file->getRealpath() . "</info>...");
                    $files[] = $file->getRealpath();
                }
            }
        }

        if (!count($files)) {
            $output->writeln("<error>No files found.</error>");
            return;
        }
        $output->writeln(sprintf("Found %d files, importing...", count($files)));

        foreach ($files as $filename) {
            $this->import($filename);
        }
    }

    public function import($filename) {
        $fname = basename($filename);

        list($name, $locale, $type) = explode('.', $fname);

        $path = pathinfo($filename);
        $dirs = explode('/', $path['dirname']);
        $bundle = $dirs[count($dirs) - 4].$dirs[count($dirs) - 3];

        $this->output->writeln("Processing <info>" . $bundle . ': ' . $fname . "</info>...");

        $this->setIndexes();

        switch ($type) {
            case 'yml':
                $yaml = new Parser();
                $value = $yaml->parse(file_get_contents($filename));

                $data = $this->getContainer()
                                ->get('server_grove_translation_editor.storage_manager')
                                ->getCollection()->findOne(array('name' => $bundle));
                if (!$data) {
                    $data = array(
                        'name' => $bundle,
                        'domains' => array(),
                    );
                }
                
                if(!isset($data['domains'][$name])){
                    $data['domains'][$name] = array();
                }

                if (!isset($data['domains'][$name][$locale])) {
                    $data['domains'][$name][$locale] = array(
                        'filename' => $filename,
                        'type' => $type,
                        'entries' => $value,
                    );
                }
                
                $this->output->writeln("  Found " . count($value) . " entries...");

                if (!$this->input->getOption('dry-run')) {
                    $this->updateValue($data);
                }
                break;
            case 'xliff':
                $this->output->writeln("  Skipping, not implemented");
                break;
        }
    }

    protected function setIndexes() {
        $collection = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection();
        $collection->ensureIndex(array("filename" => 1, 'locale' => 1));
    }

    protected function updateValue($data) {
        $collection = $collection = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection();

        $criteria = array(
            'name' => $data['name'],
        );

        $mdata = array(
            '$set' => $data,
        );

        return $collection->update($criteria, $data, array('upsert' => true));
    }

}

