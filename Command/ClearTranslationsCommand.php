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
class ClearTranslationsCommand extends Base {

    protected function configure() {
        parent::configure();

        $this->setName('locale:editor:clear')
                ->setDescription('Clear the translations database of all existing translations.')
                ->addOption("force");
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $this->input = $input;
        if ($this->input->getOption('force')) {
            $this->getContainer()
                    ->get('server_grove_translation_editor.storage_manager')
                    ->getDB()->drop();
            $this->output->writeln('<info>All translations removed!</info>');
        } else {
            $this->output->writeln(
'<error>If you are SURE you want to erase all non exported translations now, 
restart this command with the "--force" option.</error>');
        }
    }

}

