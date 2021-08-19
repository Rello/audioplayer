<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2021 Marcel Scherello
 */
 
namespace OCA\audioplayer\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Scan extends Command {
	private $userManager;
	private $scanner;
	public function __construct(
			\OCP\IUserManager $userManager, 
			$scanner
		) {
		$this->userManager = $userManager;
		$this->scanner = $scanner;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('audioplayer:scan')
            ->setDescription('scan for new audio files; use -v for debugging')
			->addArgument(
					'user_id',
					InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
					'scan all audio files of the given user(s)'
			)
			->addOption(
					'all',
					null,
					InputOption::VALUE_NONE,
					'scan all audio files of all known users'
			)
		;
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
        # restrict the verbosity level to VERBOSITY_VERY_VERBOSE
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        if ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}

		if (count($users) === 0) {
			$output->writeln("<error>Please specify a valid user id to scan, \"--all\" to scan for all users<error>");
            return 1;
		}
		
		foreach ($users as $userId) {
			if (is_object($userId)) $user = $userId;
			else $user = $this->userManager->get($userId);

			if ($user === null) {
				$output->writeln("<error>User $userId does not exist</error>");
			} else {
				$userId = $user->getUID();
				$output->writeln("<info>Start scan for $userId</info>");
                $this->scanner->scanForAudios($userId, $output);
			}
		}
        return 0;
    }
}
