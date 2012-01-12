<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

/**
 * Command for exporting translations into files
 */
class ExportCommand extends Base {

    protected function configure() {
        parent::configure();

        $this
                ->setName('locale:editor:export')
                ->setDescription('Export translations into files')
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
            $output->writeln("Exporting translations to <info>$filename</info>...");
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
        $output->writeln(sprintf("Found %d files, exporting...", count($files)));

        foreach ($files as $filename) {
            $this->export($filename);
        }
    }

    public function export($filename) {
        $fname = basename($filename);
        $this->output->writeln("Exporting to <info>" . $filename . "</info>...");

        list($name, $locale, $type) = explode('.', $fname);

        $path = pathinfo($filename);
        $dirs = explode('/', $path['dirname']);
        $bundle = $dirs[count($dirs) - 4] . $dirs[count($dirs) - 3];

        switch ($type) {
            case 'yml':
                $data = $this->getContainer()
                                ->get('server_grove_translation_editor.storage_manager')
                                ->getCollection()->findOne(array('name' => $bundle));
                if (!$data) {
                    $this->output->writeln("Could not find data for this locale");
                    return;
                }

                foreach ($data['domains'] as $domain) {
                    foreach ($domain as $locale) {
                        $result = $locale['entries'];
                        $this->output->writeln("  Writing " . count($locale['entries']) . " entries to $filename");
                        if (!$this->input->getOption('dry-run')) {
                            file_put_contents(
                                    $locale['filename'], 
                                    \Symfony\Component\Yaml\Yaml::dump($result)
                            );
                        }
                    }
                }



                break;
            case 'xliff':
                $this->output->writeln("  Skipping, not implemented");
                break;
        }
    }

}

